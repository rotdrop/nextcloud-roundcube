<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\AppInfo;

use RuntimeException;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;

use OCA\RotDrop\Toolkit\Service\AppInfoService;

// phpcs:disable PSR1.Files.SideEffects
require_once __DIR__ . '/../Service/AppInfoService.php';

/**
 * Some convenience methods. The consuming app must call the bootstrap() and
 * register() functions of this base class.
 */
abstract class AbstractApplication extends App implements IBootstrap
{
  protected static ?IAppContainer $appContainer = null;

  protected static ?string $appName;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    self::getAppName();
    parent::__construct(self::$appName);
    self::$appContainer = $this->getContainer();
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * Reads off the app-name from the info.xml file.
   *
   * @return string
   */
  public static function getAppName(): string
  {
    return self::$appName ?? (self::$appName = AppInfoService::getAppInfoAppName());
  }

  /**
   * Static query of a service through the app container.
   *
   * @return ?IAppContainer
   */
  public static function getAppContainer(): ?IAppContainer
  {
    return self::$appContainer ?? null;
  }

  /**
   * Static query of a service through the app container.
   *
   * @param string $service
   *
   * @return mixed
   */
  public static function get(string $service): mixed
  {
    if (!(self::$appContainer instanceof IAppContainer)) {
      throw new Exception('Dependency injection not possible, app-container is empty.');
    }
    return self::$appContainer->get($service);
  }

  /**
   * {@inheritdoc}
   *
   * Called earlier than boot, so anything initialized in the
   * "boot()" method must not be used here.
   */
  public function register(IRegistrationContext $context): void
  {
    $appFolderPath = AppInfoService::getAppFolderPath();
    if ((include_once $appFolderPath . '/vendor/autoload.php') === false) {
      throw new Exception('Cannot include autoload. Did you run install dependencies using composer?');
    }
  }
}
