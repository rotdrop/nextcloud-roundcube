<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
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

use ArrayAccess;
use BadMethodCallException;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use ReflectionClass;
use UnexpectedValueException;

use OCP\AppFramework\IAppContainer;

/**
 * Convert a database entity "virtually" to a flat array without recursions.
 */
class EntityArrayAdapter implements ArrayAccess, Iterator, JsonSerializable
{
  private int $fetchedDepth = 0;

  private ?EntityResponse $data = null;

  private string $entityName;

  private ?EntitySerializer $entitySerializer = null;

  private ?array $keys = null;

  /**
   * Array of overridden data which has to be maintained as EntityResponse
   * has only readonly properties.
   */
  private array $overriddenData = [];

  /**
   * @param mixed $entity An instance of a database entity.
   *
   * @param int $depth Fetch associations up to the given recursion depth.
   *
   * @param ?self $root Non null only on inferior instances.
   *
   * @param ?string $flatIdentifier
   *
   * @param ?EntitySerializer $entitySerializer Extra param in order to aid
   * unit-testing with (partly) mocked objects. The object will be cloned
   * s.t. that each top-level array-adapter has its own caching instance.
   *
   * @param ?IAppContainer $appContainer
   */
  protected function __construct(
    private mixed $entity,
    private int $depth = 2,
    private ?self $root = null,
    private ?string $flatIdentifier = null,
    ?EntitySerializer $entitySerializer = null,
    ?IAppContainer $appContainer = null,
  ) {
    if ($root === null) {
      $this->entitySerializer = clone ($entitySerializer ?? \OCP\Server::get(EntitySerializer::class));
      $fqn = get_class($entity);
      $prefix = new ReflectionClass($fqn)->getNamespaceName();
      $this->entityName = substr($fqn, strlen($prefix) + 1);
      $this->entitySerializer->setCommonPrefix($prefix);
    } else {
      if (!is_string($entity)) {
        throw new InvalidArgumentException('Inferior instances must receive an entity-name, not an entity.');
      }
      if ($flatIdentifier === null) {
        throw new InvalidArgumentException('Inferior instances must receive an entity identifier.');
      }
      $this->entityName = $this->entity;
      $this->entity = null;
    }
  }

  /**
   * Generate a new top-level instance.
   *
   * @param mixed $entity An instance of a database entity.
   *
   * @param int $depth Fetch associations up to the given recursion depth.
   *
   * @param ?EntitySerializer $entitySerializer Extra param in order to aid
   * unit-testing with (partly) mocked objects. The object will be cloned
   * s.t. that each top-level array-adapter has its own caching instance.
   *
   * @return self
   */
  public static function create(
    mixed $entity,
    int $depth = 2,
    ?EntitySerializer $entitySerializer = null,
  ): self {
    return new self(
      entity: $entity,
      depth: $depth,
      entitySerializer: $entitySerializer,
    );
  }

  /**
   * Ensure the cached data is there up to the configured depth.
   *
   * @return EntityResponse $this->data.
   */
  private function ensureData(): EntityResponse
  {
    if ($this->root === null) {
      if ($this->data === null || $this->fetchedDepth < $this->depth) {
        $this->entitySerializer->addEntity($this->entity, $this->depth);
        $this->data = $this->entitySerializer->export();
        $this->flatIdentifier = $this->data->entities[$this->entityName][0];
        $this->fetchedDepth = $this->depth;
      }
      $data = $this->data;
    } else {
      $data = $this->root->ensureData();
    }
    // keys do not change once set, so keep them in order to be able to
    // forward the iterator stuff to the keys array.
    $this->keys = $this->keys ?? array_keys($data->repositories[$this->entityName][$this->flatIdentifier]);
    return $data;
  }

  /**
   * Set a new depth. Data will be refetched at the next array access.
   *
   * @param int $depth
   *
   * @return self
   */
  public function setDepth(int $depth): self
  {
    $this->depth = $depth;
    // must be promoted to the cached values
    foreach ($this->overriddenData as $key => $overridden) {
      if ($depth === 0) {
        unset($this->overriddenData[$key]);
        continue;
      }
      if ($overridden instanceof self) {
        $overridden->setDepth($depth - 1);
      } else {
        foreach ($overridden as $instance) {
          $instance->setDepth($depth - 1);
        }
      }
    }
    return $this;
  }

  /** @return int */
  public function getDepth(): int
  {
    return $this->depth;
  }

  /** {@inheritdoc}*/
  public function offsetExists(mixed $offset): bool
  {
    $this->ensureData();
    return in_array($offset, $this->keys);
  }

  /** {@inheritdoc}*/
  public function offsetGet(mixed $offset): mixed
  {
    $data = $this->ensureData();
    $flatEntity = $data->repositories[$this->entityName][$this->flatIdentifier] ?? null;
    if (!in_array($offset, $this->keys)) {
      throw new BadMethodCallException('Offset "' . $offset . '" does not exist on "' . $this->entityName . '".' . print_r($this->keys, true));
    }
    if (array_key_exists($offset, $this->overriddenData)) {
      return $this->overriddenData[$offset];
    }
    $value = $flatEntity[$offset];
    if ($this->depth > 0 && ($value instanceof EntityReference)) {

      // This cannot happen:
      //
      // if (!isset($data->repositories[$value->entityClassName][$value->flatIdentifier])) {
      //   throw new UnexpectedValueException('Offset "' . $offset . '" has not been fetched from the database at depth "' . $this->depth . '".');
      // }

      $result = new self(
        entity: $value->entityClassName,
        flatIdentifier: $value->flatIdentifier,
        root: $this->root ?? $this,
        depth: $this->depth - 1,
      );
      $this->overriddenData[$offset] = $result;
      return $result;
    } elseif ($value instanceof EntityReferenceCollection) {
      if ($this->depth === 0) {
        // even on depth 0 return an empty array if applicable as it simplifies the exported object.
        return count($value->entities) === 0 ? [] : $value;
      }
      $flatCollection = [];
      $baseClass = $value->entityClassName;

      // This cannot happen:
      //
      // $fetched = array_reduce(
      //   $value->entities,
      //   function(bool $carry, EntityReference $entityReference) use ($data, $baseClass) {
      //     return $carry && isset($data->repositories[$entityReference->entityClassName ?? $baseClass][$entityReference->flatIdentifier]);
      //   },
      //   true,
      // );
      // if (!$fetched) {
      //   throw new UnexpectedValueException('Offset "' . $offset . '" has not been fetched from the database at depth "' . $this->depth . '".');
      // }

      /** @var EntityReference $entityReference */
      foreach ($value->entities as $key => $entityReference) {
        $flatCollection[$key] = new self(
          entity: $entityReference->entityClassName ?? $baseClass,
          flatIdentifier: $entityReference->flatIdentifier,
          root: $this->root ?? $this,
          depth: $this->depth - 1,
        );
      }
      $this->overriddenData[$offset] = $flatCollection;
      return $flatCollection;
    } else {
      // plain value or already handled.
      return $value;
    }
  }

  /** {@inheritdoc}*/
  public function offsetSet(mixed $offset, mixed $value): void
  {
    throw new BadMethodCallException('Trying to set read-only data');
  }

  /** {@inheritdoc}*/
  public function offsetUnset(mixed $offset): void
  {
    throw new BadMethodCallException('Trying to unset read-only data');
  }

  /*************** Iterator interface **************/

  /** {@inheritdoc} */
  public function current(): mixed
  {
    $this->data ?? $this->ensureData();
    $key = current($this->keys);
    if ($key !== false) {
      return $this[$key];
    }
    return false;
  }

  /** {@inheritdoc} */
  public function key(): mixed
  {
    $this->ensureData();
    return current($this->keys);
  }

  /** {@inheritdoc} */
  public function next(): void
  {
    $this->ensureData();
    next($this->keys);
  }

  /** {@inheritdoc}*/
  public function rewind(): void
  {
    if ($this->keys) {
      reset($this->keys);
    }
  }

  /** {@inheritdoc} */
  public function valid(): bool
  {
    $this->ensureData();
    return current($this->keys) !== false;
  }

  /**************** JsonSerializable ****************/

  /** {@inheritdoc} */
  public function jsonSerialize(): mixed
  {
    $result = [
      '__DEPTH__' => $this->depth,
    ];
    foreach ($this as $key => $value) {
      $result[$key] = $value;
    }
    return $result;
  }
}
