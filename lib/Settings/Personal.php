<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020-2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use Psr\Log\LoggerInterface as ILogger;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use OCP\Settings\ISettings;

use OCA\RoundCube\Constants;
use OCA\RoundCube\Service\AssetService;
use OCA\RoundCube\Service\Config;

/** Personal settings. */
class Personal implements ISettings
{
  const TEMPLATE = 'settings/personal';
  const ASSET_NAME = 'personal-settings';
  const SETTINGS = [
    'emailAddress',
    'emailPassword',
  ];

  /** @var IUser */
  private $user;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    IUserSession $userSession,
    private string $appName,
    private Config $config,
    private AssetService $assetService,
    private IURLGenerator $urlGenerator,
  ) {
    $this->user = $userSession->getUser();
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** {@inheritdoc} */
  public function getForm()
  {
    $emailAddressChoice = $this->config->getAppValue('emailAddressChoice');
    $emailDefaultDomain = $this->config->getAppValue('emailDefaultDomain');
    switch ($emailAddressChoice) {
      case 'userIdEmail':
        $userEmail = $this->user->getUID();
        if (strpos($userEmail, '@') === false) {
          $userEmail .= '@'.$emailDefaultDomain;
        }
        break;
      case 'userPreferencesEmail':
        $userEmail = $this->user->getEMailAddress();
        break;
      case 'userChosenEmail':
        $userEmail = $this->config->getPersonalValue('emailAddress');
        break;
    }

    $forceSSO = $this->config->getAppValue('forceSSO');
    if ($forceSSO != 'on') {
      $emailPassword = $this->config->getPersonalValue('emailPassword');
    } else {
      $emailPassword = '';
    }

    $templateParameters = [
      'appName' => $this->appName,
      'webPrefix' => $this->appName,
      'userId' => $this->user->getUID(),
      'userEmail' => $this->user->getEMailAddress(),
      'urlGenerator' => $this->urlGenerator,
      'emailAddressChoice' => $emailAddressChoice,
      'emailDefaultDomain' => $emailDefaultDomain,
      'emailAddress' => $userEmail,
      'emailPassword' => $emailPassword,
      'forceSSO' => $forceSSO,
      'assets' => [
        Constants::JS => $this->assetService->getJSAsset(self::ASSET_NAME),
        Constants::CSS => $this->assetService->getCSSAsset(self::ASSET_NAME),
      ],
    ];

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
}
