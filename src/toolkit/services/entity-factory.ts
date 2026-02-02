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
  EntityDto,
  EntityFieldMapping,
  EntityFieldMappingType,
  EntityFieldNullable,
  EntityFieldMetadata,
  EntityFieldNames,
  EntityAssociationFieldType,
  EntityMap,
  EntityNames,
} from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntityMetadata.ts';
import type {
  EntityReference,
  EntityReferenceCollection,
} from '../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntitySerializer.ts';
import * as EntityRepository from './entity-repository.ts';
import type { DecToZero, NonNegInt, NullableIf, NumberTuple, Zero } from '../types/type-traits.ts';

export type FrontEndEntity<N extends EntityNames, D extends NumberTuple = NonNegInt<0> > = {
  [K in EntityFieldNames<N>]: EntityFieldMapping<N, K> extends 'owned'
    ? K extends keyof EntityMap[N]
      ? EntityMap[N][K]
      : never
    : EntityFieldMapping<N, K> extends 'to-one'
      ? Zero extends D
        ? NullableIf<EntityFieldNullable<N, K>, Promise<FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D> > > >
        : NullableIf<EntityFieldNullable<N, K>, FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D> > >
      : Zero extends D
        ? Record<string|number, Promise<FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D> > > >
        : Record<string|number, FrontEndEntity<EntityAssociationFieldType<N, K>, DecToZero<D> > >;
};

const entityFactory = async <E extends keyof EntityMap, D extends NumberTuple = Zero>(entityName: E, entityDto: EntityDto<E>): Promise<FrontEndEntity<E, D> > => {
  const metadata: { [K in keyof EntityMap[E]]: EntityFieldMetadata<E> } =
    (await import(`../../../build/ts-types/php-modules/Toolkit/Doctrine/ORM/EntityMetadata/${entityName}Metadata.ts`)).default;

  const dtoStructure = Object.fromEntries(Object.keys(entityDto).map(key => [key, true]));
  const entity: FrontEndEntity<E, D> = <FrontEndEntity<E, D> >{};
  for (const fieldName of Object.keys(metadata)) {
    delete dtoStructure[fieldName];
    const fieldInfo: EntityFieldMetadata<E> = metadata[fieldName];
    switch (fieldInfo.mapping as EntityFieldMappingType) {
      case 'to-one': {
        const reference: null|EntityReference<E> = entityDto[fieldName];
        if (reference) {
          const targetEntity = reference.entityClassName as keyof EntityMap;
          const identifier = reference.flatIdentifier;
          Object.defineProperty(
            entity,
            fieldName, {
              get: () => {
                const result = EntityRepository.find(targetEntity, identifier);
                if (result !== undefined) {
                  return result;
                }
                // @todo: this will not work for composite keys and complicated foreign keys
                return EntityRepository.fetch({
                  entityName: targetEntity,
                  identifier,
                }).then(() => Promise.resolve(EntityRepository.find(targetEntity, identifier)));
              },
            },
          );
        } else {
          entity[fieldName] = null;
        }
        break;
      }
      case 'to-many': {
        const collection: EntityReferenceCollection<E> = entityDto[fieldName];
        const proxy = new Proxy(
          collection.entities, {
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
          fieldName, {
            get: () => proxy,
          },
        );
        break;
      }
      case 'owned':
        entity[fieldName] = entityDto[fieldName];
        break;
    }
  }
  // also include any extra data
  for (const extra of Object.keys(dtoStructure)) {
    entity[extra] = entityDto[extra];
  }
  return entity;
};

export default entityFactory;
