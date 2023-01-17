<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2023 Claus-Justus Heine
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
use OCP\Settings\IDelegatedSettings;
use OCP\IURLGenerator;
use OCP\IConfig;
use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;

use OCA\RoundCube\Constants;
use OCA\RoundCube\Service\AssetService;
use OCA\RoundCube\Service\Config;

/** Admin settings. */
class Admin implements IDelegatedSettings
{
  const TEMPLATE = 'tpl.adminSettings';

  /** @var string */
  private $appName;

  /** @var \OCP\IConfig */
  private $config;

  /** @var AssetService */
  private $assetService;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    Config $config,
    AssetService $assetService,
    IURLGenerator $urlGenerator,
  ) {
    $this->appName = $appName;
    $this->config = $config;
    $this->assetService = $assetService;
    $this->urlGenerator = $urlGenerator;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** {@inheritdoc} */
  public function getForm()
  {
    $templateParameters = [
      'appName' => $this->appName,
      'webPrefix' => $this->appName,
      'ocServer' => $this->urlGenerator->getAbsoluteURL("/"),
      'urlGenerator' => $this->urlGenerator,
      'assets' => [
        Constants::JS => $this->assetService->getJSAsset(self::TEMPLATE),
        Constants::CSS => $this->assetService->getCSSAsset(self::TEMPLATE),
      ],
    ];
    foreach (array_keys(Config::SETTINGS) as $setting) {
      $templateParameters[$setting] = $this->config->getAppValue($setting);
    }
    return new TemplateResponse(
      $this->appName,
      self::TEMPLATE,
      $templateParameters);
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

  /** {@inheritdoc} */
  public function getName():?string
  {
    return null;
  }

  /** {@inheritdoc} */
  public function getAuthorizedAppConfig():array
  {
    return [];
  }
}
