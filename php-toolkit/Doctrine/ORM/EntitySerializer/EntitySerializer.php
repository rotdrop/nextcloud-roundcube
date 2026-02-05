<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025, 2026 Claus-Justus Heine
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

namespace OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer;

use JsonSerializable;
use Stringable;
use Throwable;
use UnexpectedValueException;

use OCP\IL10N;
use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Doctrine\ORM\Utility\PersisterHelper;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;

use OCA\RotDrop\Toolkit\Exceptions;
use OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager as EntityManager;

/**
 * The goal is to serialize entities (... to JSON ...)  such that the JS
 * frontend can reconstruct the association structure without duplicated
 * data. For now this is meant for read-only access. "serialize" in this
 * context means to compute a flat loop-free array representation which can
 * then be safely fed into json_serialize.
 */
class EntitySerializer
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  private IdentifierFlattener $identifierFlattener;

  private array $entityDepths = [];

  private array $entities = [];

  private array $repositories = [];

  private ?string $commonPrefix = null;

  /** {@inheritdoc} */
  public function __construct(
    protected EntityManager $entityManager,
    protected IL10n $l,
    protected LoggerInterface $logger,
  ) {
    $this->identifierFlattener = new IdentifierFlattener(
      $this->entityManager->getUnitOfWork(),
      $this->entityManager->getMetadataFactory(),
    );
  }

  /**
   * @param ?string $commonPrefix
   *
   * @return self
   */
  public function setCommonPrefix(?string $commonPrefix): self
  {
    $this->commonPrefix = $commonPrefix;
    if (!str_ends_with($this->commonPrefix, '\\')) {
      $this->commonPrefix .= '\\';
    }

    return $this;
  }

  /** @return ?string */
  public function getCommonPrefix(): ?string
  {
    return $this->commonPrefix;
  }

  /**
   * Clear the data built up by previous calls to self::addEntity().
   *
   * @return void
   */
  public function reset(): void
  {
    $this->entityDepths = [];
    $this->entities = [];
    $this->repositories = [];
    $this->commonPrefix = null;
  }

  /**
   * Export the collected entities as DTO.
   *
   * @return EntityResponse
   */
  public function export(): EntityResponse
  {
    return new EntityResponse(
      $this->entities,
      $this->repositories,
    );
  }

  /**
   * @param ClassMetadataInterface $classMetaData
   *
   * @param array $id
   *
   * @return string
   */
  private function flattenIdentifier(ClassMetadataInterface $classMetaData, array $id): string
  {
    if (method_exists($classMetaData, 'getWrappedObject')) {
      $classMetaData = $classMetaData->getWrappedObject();
    }
    $ids = $this->identifierFlattener->flattenIdentifier($classMetaData, $id);

    return implode(':', $ids);
  }

  /**
   * @param string $entityName
   *
   * @return string
   */
  private function stripCommonPrefix(string $entityName): string
  {
    if ($this->commonPrefix === null) {
      return $entityName;
    }
    if (str_starts_with($entityName, $this->commonPrefix)) {
      return substr($entityName, strlen($this->commonPrefix));
    }
  }

  /**
   * Add one Doctrine ORM entity to the serialization structure.
   *
   * @param mixed $entity An entity instance known to the entity manager.
   *
   * @param int $depth Recursion depth, defaults to 1 meaning: fetch the
   * top-level entity and all associated entities (including collections) but
   * do not fetch associated entities of associated entities.
   *
   * @param bool $principal Whether to add the entity the the principle
   * entities, or just include it as part of a repository.
   *
   * @return void
   *
   * @throws EntitySerializationException
   */
  public function addEntity(mixed $entity, int $depth = 1, bool $principal = true): void
  {
    // disable the filter here in order to have all entities available
    $softDeleteableState = $this->entityManager->setFilterEnabled(EntityManager::SOFT_DELETEABLE_FILTER, false);
    try {
      $metaData = $this->entityManager->getClassMetadata(get_class($entity));
      $entityClassName = $this->stripCommonPrefix($metaData->getName());
      $id = $metaData->getIdentifierValues($entity);
      if (empty($id)) {
        throw new Exceptions\DatabaseMissingIdentifierException(
          $this->l->t('Unable to determine the identifier values for an instance of "%s".', get_class($entity)),
          entityClassName: get_class($entity),
        );
      }
      $flatIdentifier = $this->flattenIdentifier($metaData, $id);
      $existing = false;
      if (!empty($this->repositories[$entityClassName][$flatIdentifier])) {
        if ($principal) {
          if (empty($this->entities[$entityClassName])
              || !in_array($flatIdentifier, $this->entities[$entityClassName])) {
            $this->entities[$entityClassName][] = $flatIdentifier;
          }
        }
        if (($this->entityDepths[$entityClassName][$flatIdentifier] ?? -1) >= $depth) {
          return;
        }
        $existing = true;
      }

      // must be set before recursing
      $this->entityDepths[$entityClassName][$flatIdentifier] = $depth;

      $flatEntity = [];

      // ordinary non-associative fields
      /** @var Mapping\FieldMapping $mapping */
      foreach (array_keys($metaData->fieldMappings) as $field) {
        $flatEntity[$field] = $metaData->getFieldValue($entity, $field);
      }
      /** @var Mapping\AssociationMapping $associationMapping */
      foreach ($metaData->associationMappings as $field => $associationMapping) {
        switch (true) {
          case $associationMapping instanceof Mapping\ToOneAssociationMapping:
            $targetEntity = $metaData->getFieldValue($entity, $field);
            if ($targetEntity === null) {
              $flatEntity[$field] = null;
              break;
            }
            $targetMetaData = $this->entityManager->getClassMetadata(get_class($targetEntity));
            $targetClassName = $this->stripCommonPrefix($targetMetaData->getName());
            $targetId = $targetMetaData->getIdentifierValues($targetEntity);
            if (empty($targetId)) {
              throw new Exceptions\DatabaseMissingIdentifierException(
                $this->l->t('Unable to determine the identifier values for an instance of "%s".', $targetClassName),
                entityClassName: $targetClassName,
              );
            }
            $flatTargetIdentifier = $this->flattenIdentifier($targetMetaData, $targetId);
            $flatEntity[$field] = new EntityReference(
              flatIdentifier: $flatTargetIdentifier,
              entityClassName: $targetClassName,
            );
            if ($depth > 0
                && (empty($this->repositories[$targetClassName][$flatTargetIdentifier])
                    || ($this->entityDepths[$targetClassName][$flatTargetIdentifier] ?? -1) < ($depth - 1))) {
              $this->addEntity($targetEntity, $depth - 1, false);
            }
            break;
          case $associationMapping instanceof Mapping\ToManyAssociationMapping:
            $targetCollection = $metaData->getFieldValue($entity, $field);
            if (null === $targetCollection) {
              throw new UnexpectedValueException($this->l->t('Collection "%1$s" in entity of type "%2$s" is null.', [
                $field, $entityClassName,
              ]));
            }
            $targetClassName = $this->stripCommonPrefix($associationMapping->targetEntity);
            $flatCollection = [];
            $keyConvert = fn(mixed $value, mixed $metaData) => $value;
            if ($associationMapping->isIndexed()) {
              $targetMetaData = $this->entityManager->getClassMetadata($associationMapping->targetEntity);
              $indexField = $associationMapping->indexBy();
              $keyConvert = function(mixed $value, mixed $metaData) use ($indexField, $targetMetaData, $field, $entityClassName) {
                $fieldType = PersisterHelper::getTypeOfColumn(
                  $indexField,
                  $targetMetaData->getWrappedObject(),
                  $this->entityManager->getWrappedObject(),
                );
                $phpValue = $this->entityManager->getConnection()->convertToPHPValue($value, $fieldType);
                if (!is_string($phpValue) && !is_integer($phpValue)) {
                  $phpValue = (string)$phpValue;
                }
                return $phpValue;
              };
            }
            foreach ($targetCollection as $key => $targetEntity) {
              /** @var Mapping\ClassMetadata $targetMetaData */
              $targetMetaData = $this->entityManager->getClassMetadata(get_class($targetEntity));
              $key = $keyConvert($key, $targetMetaData);
              $targetId = $targetMetaData->getIdentifierValues($targetEntity);
              if (empty($targetId)) {
                throw new Exceptions\DatabaseMissingIdentifierException(
                  $this->l->t('Unable to determine the identifier values for an instance of "%s".', $targetClassName),
                  entityClassName: $targetClassName,
                );
              }
              $entityName = $this->stripCommonPrefix($targetMetaData->getName());
              if ($entityName == $targetClassName) {
                $entityName = null;
              }
              $flatTargetIdentifier = $this->flattenIdentifier($targetMetaData, $targetId);
              $flatCollection[$key] = new EntityReference(
                flatIdentifier: $flatTargetIdentifier,
                entityClassName: $entityName,
              );
              if ($depth > 0
                  && (empty($this->repositories[$targetClassName][$flatTargetIdentifier])
                      || ($this->entityDepths[$targetClassName][$flatTargetIdentifier] ?? -1) < $depth - 1)) {
                $this->addEntity($targetEntity, $depth - 1, false);
              }
            }
            $flatEntity[$field] = new EntityReferenceCollection(
              entityClassName: $targetClassName,
              entities: $flatCollection,
            );
            break;
        }
      }
      if ($entity instanceof JsonSerializable) {
        // add further properties if advertised by the entity. This is now
        // somewhat inefficient, but perhaps we will find a better way some
        // day ....
        $jsonData = $entity->jsonSerialize();
        $missingProperties = array_diff(array_keys($jsonData), array_keys($flatEntity));
        foreach ($missingProperties as $property) {
          $flatEntity[$property] = $jsonData[$property];
        }
        foreach ($jsonData as $key => $value) {
          if (is_scalar($value) && $value !== $flatEntity[$key]) {
            $flatEntity[$key] = $value;
          }
        }
      }
      ksort($flatEntity);
      $this->repositories[$entityClassName][$flatIdentifier] = $flatEntity;
      if ($principal) {
        if (!$existing) {
          $this->entities[$entityClassName][] = $flatIdentifier;
        }
      }
    } catch (Throwable $t) {
      $this->entityManager->setFilterEnabled(EntityManager::SOFT_DELETEABLE_FILTER, $softDeleteableState);
      throw new Exceptions\EntitySerializationException(
        $this->l->t('Unable to compute a serialization for an instance of "%s".', get_class($entity)),
        $entity,
        previous: $t,
      );
    }
    $this->entityManager->setFilterEnabled(EntityManager::SOFT_DELETEABLE_FILTER, $softDeleteableState);
  }
}
