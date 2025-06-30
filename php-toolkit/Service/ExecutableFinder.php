<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Service;

use Symfony\Component\Process\ExecutableFinder as ExecutableFinderBackend;

use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;
use OCP\IMemcacheTTL;
use OCP\ICacheFactory;

use OCA\RotDrop\Toolkit\Exceptions;

/**
 * Find an executable and cache the result.
 */
class ExecutableFinder
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  /**
   * @var int
   *
   * TTL for memory cache, 15 minutes.
   */
  private const CACHE_TTL = 1800;

  /** @var IMemcacheTTL */
  protected $memoryCache;

  /**
   * @var array
   *
   * Cache of found executables for the current request.
   */
  protected $executables = [];

  /**
   * @param ICacheFactory $cacheFactory
   *
   * @param ExecutableFinderBackend $executableFinder
   *
   * @param IL10N $l
   *
   * @param ILogger $logger
   *
   * @param string $appName
   */
  public function __construct(
    ICacheFactory $cacheFactory,
    protected ExecutableFinderBackend $executableFinder,
    protected IL10N $l,
    protected ILogger $logger,
    protected string $appName,
  ) {
    $this->memoryCache = $cacheFactory->createLocking();
    if (!($this->memoryCache instanceof IMemcacheTTL)) {
      $this->memoryCache = $cacheFactory->createLocal();
    }
  }

  /**
   * @param string $key
   *
   * @param mixed $value
   *
   * @return void
   */
  private function setCacheValue(string $key, mixed $value):void
  {
    $this->memoryCache->set($key, $value);
    if ($this->memoryCache instanceof IMemcacheTTL) {
      $this->memoryCache->setTTL($key, self::CACHE_TTL);
    }
  }

  /**
   * @param string $key
   *
   * @return mixed
   */
  private function getCacheValue(string $key):mixed
  {
    if ($this->memoryCache->hasKey($key)) {
      return $this->memoryCache->get($key);
    }
    return null;
  }

  /**
   * Try to find the given executable.
   *
   * @param string $program The program to search for. This must be the
   * basename of a Un*x program.
   *
   * @param bool $force Do not lookup the cache entry, really search. Default
   * \false.
   *
   * @return string The full path to $program.
   *
   * @throws Exceptions\EnduserNotificationException
   */
  public function find(string $program, bool $force = false):string
  {
    if (empty($this->executables[$program])) {
      $cacheKey = $this->cacheKey($program);
      if (!$force) {
        $this->executables[$program] = $this->getCacheValue($cacheKey);
      }
      if (empty($this->executables[$program])) {
        $executable = $this->executableFinder->find($program);
        if (empty($executable)) {
          $this->executables[$program] = [
            'exception' => new Exceptions\EnduserNotificationException(
              $this->l->t('Please install the "%s" program on the server.', $program)),
            'path' => null,
          ];
          $this->memoryCache->remove($cacheKey);
        } else {
          $this->executables[$program] = [
            'exception' => null,
            'path' => $executable,
          ];
          $this->setCacheValue($cacheKey, $this->executables[$program]);
        }
      }
    }
    if (empty($this->executables[$program]['path'])) {
      throw $this->executables[$program]['exception'];
    }
    return $this->executables[$program]['path'];
  }

  /**
   * Clear the memory cache of found executables.
   *
   * @return void
   */
  public function clearCache():void
  {
    $this->memoryCache->clear($this->cacheKey(''));
  }

  /**
   * @param string $path
   *
   * @return string Cache-key for given path.
   */
  private function cacheKey(string $path)
  {
    return $this->appName . ':' . 'executables:' . $path;
  }
}
