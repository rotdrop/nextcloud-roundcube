<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RoundCube\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

use OCA\RoundCube\Service\AssetService;

/** Personal settings. */
class Personal implements ISettings
{
  private const TEMPLATE = 'settings/personal';
  private const ASSET_NAME = 'personal-settings';

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private string $appName,
    private AssetService $assetService,
  ) {
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** {@inheritdoc} */
  public function getForm()
  {
    Util::addScript($this->appName, $this->assetService->getJSAsset(self::ASSET_NAME)['asset']);
    Util::addStyle($this->appName, $this->assetService->getCSSAsset(self::ASSET_NAME)['asset']);
    
    return new TemplateResponse($this->appName, self::TEMPLATE, [ 'appName' => $this->appName ]);
  }

  /** {@inheritdoc} */
  public function getSection()
  {
    return $this->appName;
  }

  /** {@inheritdoc} */
  public function getPriority()
  {
    // @@TODO could be made a configure option.
    return 50;
  }
}
