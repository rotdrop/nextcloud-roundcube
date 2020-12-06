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
use OCP\IUserSession;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IL10N;

use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\Authentication\LoginCredentials\ICredentials;

use OCA\RoundCube\Service\Constants;

class Personal implements ISettings
{
  const TEMPLATE = 'tpl.personalSettings';

  /** @var \OCP\IUser */
  private $user;

  /** @var string */
  private $appName;

  /** @var \OCP\IConfig */
  private $config;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  private $credentialsStore;

  public function __construct(
    $appName
    , IUserSession $userSession
    , IConfig $config
    , IURLGenerator $urlGenerator
    , ICredentialsStore $credentialsStore
  ) {
    $this->appName = $appName;
    $this->user = $userSession->getUser();
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
    $this->credentialsStore = $credentialsStore;
  }

  public function getForm() {
    $emailAddressChoice = $this->config->getAppValue($this->appName, 'emailAddressChoice');
    $emailDefaultDomain = $this->config->getAppValue($this->appName, 'emailDefaultDomain');
    switch ($emailAddressChoice) {
      case 'userIdEmail':
        $userEmail = $this->user->getUID();
        if (strpos($userEmail, '@') === false) {
          $userEmail .= '@'.$emailDefaultDomain;
        }
        break;
      case 'userPreferencesEmail':
        $userEmail = $this->getEMailAddress;
        break;
      case 'userChosenEmail':
        $userEmail = $this->getAppValue($this->appName, 'emailAddress');
        break;
    }

    $forceSSO = $this->config->getAppValue($this->appName, 'forceSSO');
    if ($forceSSO != 'on') {
      $emailPassword = $this->config->getAppValue($this->appName, 'emailPassword');
    } else {
      $emailPassword = '';
    }

    // $credentials = $this->credentialsStore->getLoginCredentials();
    // $emailPassword = $credentials->getUID()
    //                .':'.$credentials->getLoginName()
    //                .':'.$credentials->getPassword();

    $templateParameters = [
      'appName' => $this->appName,
      'userId' => $this->user->getUID(),
      'userEmail' => $this->user->getEMailAddress(),
      'urlGenerator' => $this->urlGenerator,
      'emailAddressChoice' => $emailAddressChoice,
      'emailDefaultDomain' => $emailDefaultDomain,
      'emailAddress' => $userEmail,
      'emailPassword' => $emailPassword,
      'forceSSO' => $forceSSO,
    ];

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
