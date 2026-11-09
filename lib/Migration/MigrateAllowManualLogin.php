<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 *
 * Nextcloud RoundCube App is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * Nextcloud RoundCube App is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with Nextcloud RoundCube App. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\RoundCube\Migration;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

use OCA\RoundCube\Service\Config;

/**
 * Convert the legacy boolean admin setting "allowManualLogin" to the
 * "loginMode" setting and remove the old key.
 */
class MigrateAllowManualLogin implements IRepairStep
{
  /**
   * @param string $appName
   *
   * @param IConfig $config
   */
  public function __construct(
    private string $appName,
    private IConfig $config,
  ) {
  }

  /** {@inheritdoc} */
  public function getName():string
  {
    return 'Migrate the setting "allowManualLogin" to "loginMode"';
  }

  /** {@inheritdoc} */
  public function run(IOutput $output):void
  {
    $appName = $this->appName;
    $legacy = $this->config->getAppValue($appName, Config::ALLOW_MANUAL_LOGIN, '');
    if ($legacy === '') {
      return;
    }

    $mode = $this->config->getAppValue($appName, Config::LOGIN_MODE, '');
    if (!in_array($mode, Config::LOGIN_MODES, true) && filter_var($legacy, FILTER_VALIDATE_BOOLEAN)) {
      $this->config->setAppValue($appName, Config::LOGIN_MODE, Config::LOGIN_MODE_FORM_ONLY);
      $output->info('Login mode set to "' . Config::LOGIN_MODE_FORM_ONLY . '".');
    }
    $this->config->deleteAppValue($appName, Config::ALLOW_MANUAL_LOGIN);
  }
}
