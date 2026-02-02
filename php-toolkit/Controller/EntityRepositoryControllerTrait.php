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

namespace OCA\RotDrop\Toolkit\Controller;

use Throwable;

use OCP\AppFramework\Http\Attribute as CoreAttributes;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

use OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntitySerializer;
use OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager;
use OCA\RotDrop\Toolkit\Exceptions;

/**
 * Export entities to the frontend. This is a trait as it seem controllers
 * have to live in the Controller namespace.
 */
trait EntityRepositoryControllerTrait
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  private AbstractEntityManager $entityManager;

  private EntitySerializer $entitySerializer;

  protected IL10N $l;

  /**
   * Parameters are subemitted via query-string, except for the entity name.
   *
   * @param string $entityName
   *
   * @param ?string $find Base64 encoded JSON entity identifier which decodes
   * to the an array KEY => VALUE, passed to
   * \OCA\RotDrop\Database\Doctrine\ORM\Repositories\EntityRepository::find(). The
   * parameters $find and $findBy are mutually exclusive, but one of $find or
   * $findBy has to be given.
   *
   * @param ?string $findBy Base64 encoded array of search criteria as
   * understood by
   * \OCA\RotDrop\Doctrine\ORM\EntityRepository::findBy().
   * The parameters $find and $findBy are mutually exclusive, but one of $find
   * or $findBy has to be given.
   *
   * @param ?string $sortBy Base64 encoded array of sort criteria as
   * understood by
   * \OCA\RotDrop\Doctrine\ORM\EntityRepository::findBy().
   *
   * @param ?int $limit
   *
   * @param int $offset
   *
   * @param int $depth
   *
   * @return DataResponse
   */
  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\ApiRoute(
    verb: 'GET',
    url: '/v1/entities/{entityName}/{depth}',
    defaults: ['depth' => 0],
  )]
  public function getEntities(
    string $entityName,
    ?string $find = null,
    ?string $findBy = null,
    ?string $sortBy = null,
    ?int $limit = null,
    int $offset = 0,
    int $depth = 0,
  ): DataResponse {
    if (($find === null) === ($findBy === null)) {
      throw new OCS\OCSBadRequestException(
        $this->l->t(
          'Exactly one of query-parameters "%1$s" and "%2$s" have to be specified.',
          ['find', 'findBy'],
        ),
      );
    }
    $this->entitySerializer->reset();
    $shortNames = !str_contains($entityName, '\\');
    if ($shortNames) {
      $entityNameSpace = $this->entityManager->getEntityNamespace();
      $entityName = $entityNameSpace . '\\' . $entityName;
      $this->entitySerializer->setCommonPrefix($entityNameSpace);
    }
    try {
      $repository = $this->entityManager->getRepository($entityName);
      if ($findBy) {
        $criteria = json_decode(base64_decode($findBy), associative: true);
        $entities = $repository->findBy($criteria, limit: $limit, offset: $offset);
      } else {
        $identifier = json_decode(base64_decode($find), associative: true);
        $entity = $repository->find($identifier);
        if ($entity === null) {
          throw new Exceptions\DatabaseEntityNotFoundException(
            $this->l->t(
              'Unable to find the entity "%1$s" identified by "%2$s".',
              [$entityName, print_r($identifier, true)],
            ),
            entityClassName: $entityName,
            identifier: $identifier,
          );
        }
        $entities = [ $entity ];
      }
      foreach ($entities as $entity) {
        $this->entitySerializer->addEntity($entity, $depth);
      }
    } catch (Exceptions\DatabaseEntityNotFoundException $e) {
      throw new OCS\OCSNotFoundException(previous: $e);
    } catch (Throwable $t) {
      throw new OCS\OCSBadRequestException(previous: $t);
    }
    return new DataResponse($this->entitySerializer->export());
  }
}
