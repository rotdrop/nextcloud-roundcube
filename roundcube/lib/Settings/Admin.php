<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @author Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\RoundCube\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IL10N;

class Admin implements ISettings
{

  const TEMPLATE = 'tpl.adminSettings';
  const SETTINGS = [
    'externalLocation' => '',
    'userIdEmail' => false,
    'emailDefaultDomain' => '',
    'userPreferencesEmail' => true,
    'userChosenEmail' => false,
    'showTopLine' => false,
    'enableSSLVerify' => true,
    'authenticationRefreshInterval' => 600,
  ];

  /** @var string */
  private $appName;

  /** @var \OCP\IConfig */
  private $config;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  public function __construct(
    $appName
    , IConfig $config
    , IURLGenerator $urlGenerator
  ) {
    $this->appName = $appName;
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
  }

  public function getForm() {
    $templateParameters = [
      'appName' => $this->appName,
      'ocServer' => $this->urlGenerator->getAbsoluteURL("/"),
      'urlGenerator' => $this->urlGenerator,
    ];
    foreach (self::SETTINGS as $setting => $default) {
      $templateParameters[$setting] = $this->config->getAppValue($this->appName, $setting, $default);
    }
    return new TemplateResponse(
      $this->appName,
      self::TEMPLATE,
      $templateParameters);
  }

  /**
   * @return string the section ID, e.g. 'sharing'
   * @since 9.1
   */
  public function getSection() {
    return $this->appName;
  }

  /**
   * @return int whether the form should be rather on the top or bottom of
   * the admin section. The forms are arranged in ascending order of the
   * priority values. It is required to return a value between 0 and 100.
   *
   * E.g.: 70
   * @since 9.1
   */
  public function getPriority() {
    // @@TODO could be made a configure option.
    return 50;
  }
}
