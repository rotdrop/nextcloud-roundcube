<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Doctrine\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;

/**
 * Abstract base class with functionality needed by the classes in
 * this namespace. The consuming project's entity manager must extend
 * this class.
 */
abstract class AbstractEntityManager extends EntityManagerDecorator implements SingleEntityNamespaceManager
{
  /**
   * @var string
   * The name of the soft-deleteable filter.
   */
  public const SOFT_DELETEABLE_FILTER = 'soft-deleteable';
  public const BASE_FILTER_SET = [
    self::SOFT_DELETEABLE_FILTER => SoftDeleteableFilter::class,
  ];

  /**
   * Add a basic set of filters to ensure consistent naming.
   *
   * @param Configuration $configuration
   *
   * @return array<string, string> The array of enabled filter classes keyed
   * by the chosen filter name.
   */
  protected function filterConfiguration(Configuration $configuration): array
  {
    foreach (self::BASE_FILTER_SET as $name => $className) {
      $configuration->addFilter($name, $className);
    }
    return self::BASE_FILTER_SET;
  }

  /**
   * Toggle a filter without triggering an exception from the FilterCollection
   * if the filter does not exist.
   *
   * @param string $filterName The name of the filter.
   *
   * @param ?bool $state The state to set. Defaults to \true. If null nothing is done.
   *
   * @return ?bool The old state of the filter or \null if the filter is not available.
   *
   * @example "EntitySerializer/EnititySerializer.php" 174 4 Enable the filter and remember its state.
   * @example "EntitySerializer/EnititySerializer.php" 331 3 Reset the filter to its remembered state.
   */
  public function setFilterEnabled(
    string $filterName,
    ?bool $state = true,
  ): ?bool {
    if (!$this->getFilters()->has($filterName)) {
      return null;
    }
    $oldState = $this->getFilters()->isEnabled($filterName);
    if ($state && !$oldState) {
      $this->getFilters()->enable($filterName);
    } elseif (!$state && $oldState) {
      $this->getFilters()->disable($filterName);
    }
    return $oldState;
  }
}
