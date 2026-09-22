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

import type {
  EntityAssociationFieldType,
  EntityDto,
  EntityFieldMapping,
  EntityFieldMetadata,
  EntityFieldNames,
  EntityFieldNullable,
  EntityId,
  EntityMap,
  EntityNames,
  ExtraFieldNames,
} from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntityMetadata.ts';
import type {
  EntityReference,
  EntityReferenceCollection,
} from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntitySerializer.ts';
import type { DecToZero, NonNegInt, NullableIf, NumberTuple, Zero } from '../types/type-traits.ts';

import * as EntityRepository from './entity-repository.ts';

export type FrontEndEntity<N extends EntityNames, D extends NumberTuple = NonNegInt<0>> = {
  [K in keyof EntityMap[N]['entity']]: K extends keyof EntityMap[N]['metadata']
    ? EntityFieldMapping<N, K> extends 'owned'
      ? K extends keyof EntityMap[N]['entity']
        ? EntityMap[N]['entity'][K]
        : never
      : EntityFieldMapping<N, K> extends 'to-one'
        ? Zero extends D
          ? NullableIf<EntityFieldNullable<N, K>, Promise<FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D>>>>
          : NullableIf<EntityFieldNullable<N, K>, FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D>>>
        : Zero extends D
          ? Record<string|number, Promise<FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D>>>>
          : Record<string|number, FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D>>>
    : EntityMap[N]['entity'][K];
};

const entityFactory = async <E extends EntityNames, D extends NumberTuple = Zero>(entityName: E, entityDto: EntityDto<E>): Promise<FrontEndEntity<E, D>> => {
  // const metadata: EntityMap[E]['metadata'] =
  const metadata: { [K in EntityFieldNames<E>]: EntityFieldMetadata<E, K>; } =
    (await import(`../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntityMetadata/${entityName}Metadata.ts`)).default;

  const dtoStructure = Object.fromEntries(Object.keys(entityDto).map((key) => [key, true])) as Record<keyof EntityDto<E>, true>;
  const entity: FrontEndEntity<E, D> = <FrontEndEntity<E, D> >{};
  for (const fieldName of Object.keys(metadata) as (EntityFieldNames<E>)[]) {
    delete dtoStructure[fieldName];
    const fieldInfo = metadata[fieldName];
    switch (fieldInfo.mapping) {
      case 'to-one': {
        const reference = entityDto[fieldName] as null|EntityReference<E>;
        if (reference) {
          const targetEntity = reference.entityClassName;
          const identifier = reference.flatIdentifier;
          Object.defineProperty(
            entity,
            fieldName,
            {
              get: () => {
                const result = EntityRepository.find(targetEntity!, identifier);
                if (result !== undefined) {
                  return result;
                }
                // @todo: this will not work for composite keys and complicated foreign keys
                return EntityRepository.fetch({
                  entityName: targetEntity,
                  identifier: { id: identifier } as EntityId<E>,
                }).then(() => Promise.resolve(EntityRepository.find(targetEntity!, identifier)));
              },
            },
          );
        } else {
          // @ts-expect-error 2322 Null is allowed here but difficult to deduce via type hints.
          entity[fieldName] = null;
        }
        break;
      }
      case 'to-many': {
        const collection = entityDto[fieldName] as EntityReferenceCollection<E>;
        const proxy = new Proxy(
          collection.entities,
          {
            get: (
              entities: EntityReferenceCollection<E>['entities'],
              field: string,
              _receiver: unknown,
            ) => {
              if (entities[field] === undefined) {
                return undefined;
              }
              const entityReference = entities[field];
              const className = entityReference.entityClassName ?? collection.entityClassName;
              const result = EntityRepository.find(className, entityReference.flatIdentifier);
              if (result !== undefined) {
                return result;
              }
              return EntityRepository.fetch({
                entityName: className,
                // @todo: this will not work for composite keys and complicated foreign keys
                identifier: entityReference.flatIdentifier,
              }).then(() => Promise.resolve(EntityRepository.find(className, entityReference.flatIdentifier)));
            },
          },
        );
        Object.defineProperty(
          entity,
          fieldName,
          {
            get: () => proxy,
          },
        );
        break;
      }
      case 'owned':
        // @ts-expect-error 2322 This is ok, but proper type-deductions are really a nightmare.
        entity[fieldName] = entityDto[fieldName];
        break;
    }
  }
  // also include any extra data
  for (const extra of Object.keys(dtoStructure) as ExtraFieldNames<E>[]) {
    // @ts-expect-error 2719 Obscure. I really tried hard to satisfy TS, but it did not work out.
    entity[extra] = entityDto[extra];
  }
  return entity;
};

export default entityFactory;
