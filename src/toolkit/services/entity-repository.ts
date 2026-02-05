/**
 * Loose collection of TypeScript stuff for reuse in my Nextcloud apps.
 *
 * @author Claus-Justus Heine
 * @copyright 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

import { appName } from '../../config.ts';
import {
  reactive,
  set as vueSet,
} from 'vue';
import axios from '@nextcloud/axios';
import { translate as t } from '@nextcloud/l10n';
// eslint-disable-next-line n/no-missing-import
import type { OCSResponse } from '@nextcloud/typings/ocs';
import { generateOcsUrl } from '../util/generate-url.ts';
import { type EntityId, type EntityMap } from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntityMetadata.ts';
import { type EntityResponse } from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntitySerializer.ts';
import { EnumOrderByOptions } from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM.ts';
import { QUERY_OPTIONS_KEY, QUERY_OPTION_WILDCARDS } from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/Constants.ts';
import entityFactory, { type FrontEndEntity } from './entity-factory.ts';
import { AppError } from '../types/errors.ts';
import type { NonNegInt, NumberTuple, ObjectEntries } from '../types/type-traits.ts';

type EntityRepository<E extends keyof EntityMap> = {
  [Identifier: string]: FrontEndEntity<E>;
};

export const repositories = reactive<{ [E in keyof EntityMap]?: EntityRepository<E> }>({});
export const find = <N extends keyof EntityMap>(entityName: N, identifier: string) => {
  return repositories[entityName]?.[identifier] as FrontEndEntity<N>|undefined;
};

export const loadEntities = async <const N extends keyof EntityMap, D extends NumberTuple = NonNegInt<0> >(
  url: string,
  queryParams: Record<string|number, null|number|string|(number|string)[]|Record<string|number, unknown> >,
) => {
  const response = await axios.get<OCSResponse<EntityResponse<N> > >(url, { params: queryParams });
  const responseRepositories = response.data.ocs.data.repositories;
  for (const entityName of Object.keys(responseRepositories) as N[]) {
    for (const [identifier, entityDto] of Object.entries(responseRepositories[entityName])) {
      const entity = await entityFactory<N, D>(entityName, entityDto);
      if (repositories[entityName] === undefined) {
        vueSet(repositories, entityName, {});
      }
      vueSet(repositories[entityName] as object, identifier, entity);
    }
  }
  const entities = response.data.ocs.data.entities;
  const result = Object.fromEntries(
    (Object.entries(entities) as ObjectEntries<typeof entities>).map(
      ([entityName, identifiers]) => [
        entityName,
        Object.fromEntries(
          identifiers!.map(
            identifier => [identifier, find(entityName, identifier)!],
          ) as [string, FrontEndEntity<typeof entityName, D>][],
        ),
      ],
    ) as ObjectEntries<{
      [K in N]: N extends K ? Record<string, FrontEndEntity<K, D> > : undefined|Record<string, FrontEndEntity<K, D> >;
    }>,
  );
  return result;
};

export type FindByRecord = {
  [QUERY_OPTIONS_KEY]: { [QUERY_OPTION_WILDCARDS]: boolean },
} | {
  [K: string]: null|string|number|(string|number)[];
} | {
  [K: number]: Record<string, null|string|number|(string|number)[]>;
};

export type SearchArguments<
  N extends keyof EntityMap,
  D extends number = 1,
  L extends null|number = null,
  O extends number = 0,
> = {
  entityName: N;
  findBy: FindByRecord,
  orderBy?: Record<string, EnumOrderByOptions>,
  depth?: D,
  limit?: L,
  offset?: O,
};

/**
 * Search for entities of the given name. In order to separate "new"
 * and legacy code the search is wrapped by an event handler. The
 * event listener is required to handle all errors
 *
 * @param root0 Why the heck is this necessary?
 *
 * @param root0.entityName The name of the entity class to search for.
 *
 * @param root0.findBy Search criteria. Basically everything which is
 * understood by the PHP server code FindByTrait.
 *
 * @param root0.orderBy Sort criteria. Basically everything which is
 * understood by the PHP server code FindByTrait.
 *
 * @param root0.depth The "depth" of the entity mesh which is
 * fetched. Defaults to 1, meaning that direct associations will be
 * fetched, but associations of associations will only be represented
 * by their join-column values.
 *
 * @param root0.limit Limit the number of search results. Defaults to unlimited.
 *
 * @param root0.offset Start fetching at the given offset if limit is also set.
 */
export const search = async <
  N extends keyof EntityMap,
  D extends number = 0,
  L extends null|number = null,
  O extends number = 0,
>({
  entityName,
  findBy,
  orderBy = undefined,
  depth = 0 as D,
  limit = null as L,
  offset = 0 as O,
}: SearchArguments<N, D, L, O>) => {
  const url = generateOcsUrl(`v1/entities/${entityName}/${depth}`);
  const queryParams = {
    findBy: btoa(JSON.stringify(findBy)),
    orderBy: orderBy ? btoa(JSON.stringify(orderBy)) : null,
    limit,
    offset,
  };
  try {
    return await loadEntities<N, NonNegInt<D> >(url, queryParams);
  } catch (e) {
    throw new AppError(
      { entityName, findBy, depth, limit, offset },
      t(appName, 'Unable to search for entities "{entityName}" with identifier "{criteria}".', {
        entityName, criteria: JSON.stringify(findBy),
      }),
      { cause: e },
    );
  }
};

export type FetchArguments<
  N extends keyof EntityMap,
  D extends number = 0,
> = {
  entityName: N;
  identifier: EntityId<N>,
  depth?: D,
};

export const fetch = async <N extends keyof EntityMap, D extends number = 0>({
  entityName,
  identifier,
  depth = 0 as D,
}: FetchArguments<N, D>) => {
  const url = generateOcsUrl(`v1/entities/${entityName}/${depth}`);
  const queryParams = {
    find: btoa(JSON.stringify(identifier)),
  };
  try {
    return await loadEntities<N, NonNegInt<D> >(url, queryParams);
  } catch (e) {
    throw new AppError(
      { entityName, identifier, depth },
      t(appName, 'Unable to fetch entity "{entityName}" with identifier "{identifier}".', { entityName, identifier: JSON.stringify(identifier) }),
      { cause: e },
    );
  }
};
