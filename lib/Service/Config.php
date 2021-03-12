<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RoundCube\Service;

use OCP\IConfig;
use OCP\IUserSession;
use OCP\ILogger;
use OCP\IL10N;
use OCP\Security\ICrypto;
use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\Authentication\LoginCredentials\ICredentials;

class Config
{
  use \OCA\RoundCube\Traits\LoggerTrait;

  const EMAIL_ADDRESS = [
    'userIdEmail',
    'userPreferencesEmail',
    'userChosenEmail',
  ];
  const SETTINGS = [
    'externalLocation' => '',
    'emailDefaultDomain' => '',
    'emailAddressChoice' => 'userPreferencesEmail',
    'forceSSO' => false,
    'showTopLine' => false,
    'enableSSLVerify' => true,
    'personalEncryption' => true,
  ];

  /** @var \OCP\IUser */
  private $user;

  private $userId;

  private $userPassword;

  /** @var \OCP\IConfig */
  private $config;

  /** @var \OCP\Authentication\LoginCredentials\ICredentials */
  private $credentials;

  /** @var \OCP\ICrypto */
  private $crypto;

  /**
   * @var bool
   *
   * Whether to encrypt personal data with the user's password.
   */
  private $personalEncryption;

  public function __construct(
    $appName
    , IUserSession $userSession
    , IConfig $config
    , ICredentialsStore $credentialsStore
    , ICrypto $crypto
    , ILogger $logger
    , IL10N $l10n
  ) {
    $this->appName = $appName;
    $this->config = $config;
    $this->crypto = $crypto;
    $this->logger = $logger;
    $this->l = $l10n;
    $this->personalEncryption = $this->getAppValue('personalEncryption');
    try {
      $this->user = $userSession->getUser();
      $this->userId = $this->user->getUID();
    } catch (\Throwable $t) {
      $this->logException($t);
      $this->user = null;
      $this->userId = null;
    }
    $this->credentials = null;
    $this->userPassword = null;
    try {
      $this->credentials = $credentialsStore->getLoginCredentials();
      $this->userPassword = $this->credentials->getPassword();
    } catch (\Throwable $t) {
      $this->logException($t);
    }
  }

  public function getAppValue(string $key, $default = null) {
    if (empty($default) && isset(self::SETTINGS[$key])) {
      $default = self::SETTINGS[$key];
    }
    return $this->config->getAppValue($this->appName, $key, $default);
  }

  public function setAppValue(string $key, $value) {
    return $this->config->setAppValue($this->appName, $key, $value);
  }

  public function getPersonalValue(string $key, $default = null, $password = null, $userId = null)
  {
    if (!$this->personalEncryption) {
      $password = '';
    } else if (empty($password)) {
      $password = $this->userPassword;
    }
    $userId = $userId?:$this->userId;
    $value = $this->config->getUserValue($userId, $this->appName, $key, $default);
    if (!empty($value) && $value !== $default) {
      try {
        $decrypted = $this->crypto->decrypt($value, $password);
        $value = $decrypted;
      } catch (\Throwable $t) {
        try {
          if ($this->personalEncryption && !empty($password)) {
            // retry with server password
            $this->logInfo("Retry decrypt of $key with server passphrase");
            $decrypted = $this->crypto->decrypt($value);
            $this->setPersonalValue($key, $decrypted);
            $value = $decrypted;
          } else if (!$this->personalEncryption && !empty($this->userPassword)) {
            $this->logInfo("Retry decrypt of $key with user password");
            $decrypted = $this->crypto->decrypt($value, $this->userPassword);
            $this->setPersonalValue($key, $decrypted);
            $value = $decrypted;
          }
        } catch (\Throwable $t) {
          $this->logException($t);
          $value = $default;
        }
      }
    }
    return $value;
  }

  public function setPersonalValue(string $key, $value, $password = null, $userId = null)
  {
    if (!$this->personalEncryption) {
      $password = '';
    } else if (empty($password)) {
      $password = $this->userPassword;
    }
    $userId = $userId?:$this->userId;
    $value = $this->crypto->encrypt($value, $password);
    return $this->config->setUserValue($userId, $this->appName, $key, $value);
  }

  public function recryptPersonalValues($newPassword)
  {
    $this->logInfo("Re-encrypting personal values.");
    if ($this->personalEncryption && empty($this->userPassword)) {
      $this->logError('Unable to re-crypt configuration values without old password.');
      return;
    }
    $keys = $this->config->getUserKeys($this->userId, $this->appName);
    foreach ($keys as $key) {
      $value = $this->getPersonalValue($key);
      if ($value !== null) {
        $this->setPersonalValue($key, $value, $newPassword);
      } else {
        $this->logWarn("Empty value for config entry `$key'.");
        //$this->config->deleteUserValue($this->userId, $this->appName, $key);
      }
    }
  }

  /**
   * Return the login credentials for the configured email account for
   * the current user.
   *
   * @return array
   * ```
   * [
   *   userId: BLAH@FOO.BAR,
   *   password: PASSWORD,
   * ]
   * ```
   */
  public function emailCredentials()
  {
    $emailAddressChoice = $this->getAppValue('emailAddressChoice', 'userPreferencesEmail');
    switch ($emailAddressChoice) {
    case 'userIdEmail':
      $userEmail = $this->user->getUID();
      if (strpos($userEmail, '@') === false) {
        $emailDefaultDomain = $this->getAppValue('emailDefaultDomain', '');
        $userEmail .= '@'.$emailDefaultDomain;
      }
      break;
    case 'userPreferencesEmail':
      $userEmail = $this->user->getEMailAddress();
      break;
    case 'userChosenEmail':
      $userEmail = $this->getPersonalValue('emailAddress', '');
      break;
    }
    if (empty($userEmail)) {
      $this->logError('Unable to obtain email credentials for user '.$this->userId);
      return false;
    }
    $forceSSO = $this->getAppValue('forceSSO', false);
    if (!$forceSSO) {
      $userPassword = $this->getPersonalValue('emailPassword');
    } else {
      $userPassword = $this->userPassword;
    }
    if (empty($userPassword)) {
      $this->logError('Unable to obtain email credentials for user '.$this->userId);
      return false;
    }
    return [
      'userId' => $userEmail,
      'password' => $userPassword,
    ];
  }

  public function setEmailCredentials($userId, $emailUser, $emailPassword)
  {
    $emailAddressChoice = $this->getAppValue('emailAddressChoice', 'userPreferencesEmail');
    switch ($emailAddressChoice) {
    case 'userIdEmail':
    case 'userPreferencesEmail':
      throw new \Exception($this->l->t('Cannot set email credentials for chosen address choice "%s".', $emailAddressChoice));
    case 'userChosenEmail':
      break;
    }
    $forceSSO = $this->getAppValue('forceSSO', false);
    if ($forceSSO) {
      throw new \Exception($this->l->t('Cannot set email credentials if SSO is enforced.'));
    }
    if ($this->personalEncryption) {
      // @todo replace by asymmetric (public/private key pair) encryption
      throw new \Exception($this->l->t('Cannot set email credentials if personal encryption is required.'));
    }
    $this->setPersonalValue('emailAddress', $emailUser, null, $userId);
    $this->setPersonalValue('emailPassword', $emailPassword, null, $userId);
  }

}
