<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2022, 2023 Claus-Justus Heine
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

namespace OCA\RoundCube\Service;

use Exception;

use OCP\IConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface as ILogger;
use Psr\Log\LogLevel;
use OCP\IL10N;
use OCP\Security\ICrypto;
use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\Authentication\LoginCredentials\ICredentials;

/** Helper class for handling config values. */
class Config
{
  use \OCA\RoundCube\Toolkit\Traits\LoggerTrait;

  public const EXTERNAL_LOCATION = 'externalLocation';
  public const EXTERNAL_LOCATION_DEFAULT = null;
  public const EMAIL_DEFAULT_DOMAIN = 'emailDefaultDomain';
  public const EMAIL_DEFAULT_DOMAIN_DEFAULT = null;
  public const FIXED_SINGLE_EMAIL_ADDRESS = 'fixedSingleEmailAddress';
  public const FIXED_SINGLE_EMAIL_ADDRESS_DEFAULT = null;
  public const FIXED_SINGLE_EMAIL_PASSWORD = 'fixedSingleEmailPassword';
  public const FIXED_SINGLE_EMAIL_PASSWORD_DEFAULT = null;
  public const EMAIL_ADDRESS_CHOICE = 'emailAddressChoice';
  public const EMAIL_ADDRESS_CHOICE_USER_ID = 'userIdEmail';
  public const EMAIL_ADDRESS_CHOICE_USER_PREFERENCES = 'userPreferencesEmail';
  public const EMAIL_ADDRESS_CHOICE_USER_CHOSEN = 'userChosenEmail';
  public const EMAIL_ADDRESS_CHOICE_FIXED_SINGLE_ADDRESS = 'fixedSingleAddress';
  public const EMAIL_ADDRESS_CHOICE_DEFAULT = self::EMAIL_ADDRESS_CHOICE_USER_CHOSEN;
  public const EMAIL_ADDRESS_CHOICES = [
    self::EMAIL_ADDRESS_CHOICE_USER_ID,
    self::EMAIL_ADDRESS_CHOICE_USER_PREFERENCES,
    self::EMAIL_ADDRESS_CHOICE_USER_CHOSEN,
    self::EMAIL_ADDRESS_CHOICE_FIXED_SINGLE_ADDRESS,
  ];
  public const FORCE_SSO = 'forceSSO';
  public const FORCE_SSO_DEFAULT = false;
  public const SHOW_TOP_LINE = 'showTopLine';
  public const SHOW_TOP_LINE_DEFAULT = false;
  public const ENABLE_SSL_VERIFY = 'enableSSLVerify';
  public const ENABLE_SSL_VERIFY_DEFAULT = true;
  public const PERSONAL_ENCRYPTION = 'personalEncryption';
  public const PERSONAL_ENCRYPTION_DEFAULT = false;

  const SETTINGS = [
    self::EXTERNAL_LOCATION => self::EXTERNAL_LOCATION_DEFAULT,
    self::EMAIL_DEFAULT_DOMAIN => self::EMAIL_DEFAULT_DOMAIN_DEFAULT,
    self::EMAIL_ADDRESS_CHOICE => self::EMAIL_ADDRESS_CHOICE_DEFAULT,
    self::FORCE_SSO => self::FORCE_SSO_DEFAULT,
    self::SHOW_TOP_LINE => self::SHOW_TOP_LINE_DEFAULT,
    self::ENABLE_SSL_VERIFY => self::ENABLE_SSL_VERIFY_DEFAULT,
    self::PERSONAL_ENCRYPTION => self::PERSONAL_ENCRYPTION_DEFAULT,
  ];

  /** @var \OCP\IUser */
  private $user;

  /** @var string */
  private $userId;

  /** @var string */
  private $userPassword;

  /** @var IConfig */
  private $config;

  /** @var ICredentials */
  private $credentials;

  /** @var ICrypto */
  private $crypto;

  /**
   * @var bool
   *
   * Whether to encrypt personal data with the user's password.
   */
  private $personalEncryption;

  /**
   * Explicitly declare the properties.
   */
  private $appName;
  private $l; // Assuming $l is for localization (IL10N)

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IUserSession $userSession,
    IConfig $config,
    ICredentialsStore $credentialsStore,
    ICrypto $crypto,
    ILogger $logger,
    IL10N $l10n,
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
      $this->logException($t, 'Unable to get user from session.', level: LogLevel::DEBUG);
      $this->user = null;
      $this->userId = null;
    }
    $this->credentials = null;
    $this->userPassword = null;
    try {
      $this->credentials = $credentialsStore->getLoginCredentials();
      $this->userPassword = $this->credentials->getPassword();
    } catch (\Throwable $t) {
      $this->logException($t, 'Unable to get credentials from credentials-store.', level: LogLevel::DEBUG);
    }
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

/**
   * @param string $key
   *
   * @param mixed $default
   *
   * @param bool $encrypted
   *
   * @return mixed
   */
  public function getAppValue(string $key, mixed $default = null, bool $encrypted = false):mixed
  {
    if (empty($default) && isset(self::SETTINGS[$key])) {
      $default = self::SETTINGS[$key];
    }
    $value = $this->config->getAppValue($this->appName, $key, $default);
    if ($value !== $default && $encrypted) {
      $value = $this->crypto->decrypt($value);
    }
    return $value;
  }

  /**
   * @param string $key
   *
   * @return void
   */
  public function deleteAppValue(string $key):void
  {
    $this->config->deleteAppValue($this->appName, $key);
  }

  /**
   * @param string $key
   *
   * @param mixed $value
   *
   * @param bool $encrypted
   *
   * @return void
   */
  public function setAppValue(string $key, mixed $value, bool $encrypted = false):void
  {
    if ($encrypted) {
      $value = $this->crypto->encrypt($value);
    }
    $this->config->setAppValue($this->appName, $key, $value);
  }

  /**
   * @param string $key
   *
   * @param mixed $default
   *
   * @param null|string $password
   *
   * @param null|string $userId
   *
   * @return mixed
   */
  public function getPersonalValue(
    string $key,
    mixed $default = null,
    ?string $password = null,
    ?string $userId = null,
  ):mixed {
    if (!$this->personalEncryption) {
      $password = '';
    } elseif (empty($password)) {
      $password = $this->userPassword;
    }
    $userId = $userId ?? $this->userId;
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
          } elseif (!$this->personalEncryption && !empty($this->userPassword)) {
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

  /**
   * @param string $key
   *
   * @param mixed $value
   *
   * @param null|string $password
   *
   * @param null|string $userId
   *
   * @return void
   */
  public function setPersonalValue(
    string $key,
    mixed $value,
    ?string $password = null,
    ?string $userId = null,
  ):void {
    if (!$this->personalEncryption) {
      $password = '';
    } elseif (empty($password)) {
      $password = $this->userPassword;
    }
    $userId = $userId ?? $this->userId;
    $value = $this->crypto->encrypt($value, $password);
    $this->config->setUserValue($userId, $this->appName, $key, $value);
  }

  /**
   * @param string $key
   *
   * @param null|string $userId
   *
   * @return void
   */
  public function deletePersonalValue(string $key, ?string $userId = null):void
  {
    $userId = $userId ?? $this->userId;
    $this->config->deleteUserValue($userId, $this->appName, $key);
  }

  /**
   * @param null|string $newPassword
   *
     @return void
   */
  public function recryptPersonalValues(?string $newPassword):void
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
    $userEmail = null;
    $userPassword = null;
    $emailAddressChoice = $this->getAppValue('emailAddressChoice', 'userPreferencesEmail');
    switch ($emailAddressChoice) {
      case self::EMAIL_ADDRESS_CHOICE_USER_ID:
        $userEmail = $this->user->getUID();
        if (strpos($userEmail, '@') === false) {
          $emailDefaultDomain = $this->getAppValue('emailDefaultDomain', '');
          $userEmail .= '@'.$emailDefaultDomain;
        }
        break;
      case self::EMAIL_ADDRESS_CHOICE_USER_PREFERENCES:
        $userEmail = $this->user->getEMailAddress();
        break;
      case self::EMAIL_ADDRESS_CHOICE_USER_CHOSEN:
        $userEmail = $this->getPersonalValue('emailAddress', '');
        break;
      case self::EMAIL_ADDRESS_CHOICE_FIXED_SINGLE_ADDRESS:
        $userEmail = $this->getAppValue(self::FIXED_SINGLE_EMAIL_ADDRESS, self::FIXED_SINGLE_EMAIL_ADDRESS_DEFAULT);
        $userPassword = $this->getAppValue(self::FIXED_SINGLE_EMAIL_PASSWORD, self::FIXED_SINGLE_EMAIL_PASSWORD_DEFAULT, encrypted: true);
        break;
    }
    if (empty($userEmail)) {
      $this->logError('Unable to obtain email credentials for user ' . $this->userId);
      return false;
    }
    if (empty($userPassword)) {
      $forceSSO = $this->getAppValue('forceSSO', false);
      if (!$forceSSO) {
        $userPassword = $this->getPersonalValue('emailPassword');
      } else {
        $userPassword = $this->userPassword;
      }
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
}
