<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020-2025 Claus-Justus Heine
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

use Psr\Log\LoggerInterface as ILogger;
use Psr\Log\LogLevel;

use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Security\ICrypto;

/** Helper class for handling config values. */
class Config
{
  use \OCA\RoundCube\Scoped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;

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
  public const ENABLE_TLS_CLIENT_CERTIFICATES = 'enableTLSClientCertificates';
  public const ENABLE_TLS_CLIENT_CERTIFICATES_DEFAULT = false;
  public const CLIENT_TLS_KEY_FILE = 'clientTLSKeyFile';
  public const CLIENT_TLS_KEY_FILE_DEFAULT = '';
  public const CLIENT_TLS_CERTIFICATE_FILE = 'clientTLSCertificateFile';
  public const CLIENT_TLS_CERTIFICATE_FILE_DEFAULT = '';
  public const CLIENT_TLS_KEY_PASSWORD = 'clientTLSKeyPassword';
  public const CLIENT_TLS_KEY_PASSWORD_DEFAULT = '';
  public const PERSONAL_ENCRYPTION = 'personalEncryption';
  public const PERSONAL_ENCRYPTION_DEFAULT = false;
  public const CARDDAV_PROVISIONG_TAG = 'cardDavProvisioningTag';
  public const CARDDAV_PROVISIONG_TAG_DEFAULT = '';
  public const ALLOW_MANUAL_LOGIN = 'allowManualLogin';
  public const ALLOW_MANUAL_LOGIN_DEFAULT = false;
  public const LOGIN_MODE = 'loginMode';
  public const LOGIN_MODE_AUTO = 'auto';
  public const LOGIN_MODE_AUTO_WITH_FALLBACK = 'autoWithFallback';
  public const LOGIN_MODE_FORM_ONLY = 'formOnly';
  public const LOGIN_MODE_DEFAULT = self::LOGIN_MODE_AUTO;
  public const LOGIN_MODES = [
    self::LOGIN_MODE_AUTO,
    self::LOGIN_MODE_AUTO_WITH_FALLBACK,
    self::LOGIN_MODE_FORM_ONLY,
  ];

  const SETTINGS = [
    self::EXTERNAL_LOCATION => self::EXTERNAL_LOCATION_DEFAULT,
    self::EMAIL_DEFAULT_DOMAIN => self::EMAIL_DEFAULT_DOMAIN_DEFAULT,
    self::EMAIL_ADDRESS_CHOICE => self::EMAIL_ADDRESS_CHOICE_DEFAULT,
    self::FORCE_SSO => self::FORCE_SSO_DEFAULT,
    self::SHOW_TOP_LINE => self::SHOW_TOP_LINE_DEFAULT,
    self::ENABLE_SSL_VERIFY => self::ENABLE_SSL_VERIFY_DEFAULT,
    self::ENABLE_TLS_CLIENT_CERTIFICATES => self::ENABLE_TLS_CLIENT_CERTIFICATES_DEFAULT,
    self::CLIENT_TLS_KEY_FILE => self::CLIENT_TLS_KEY_FILE_DEFAULT,
    self::CLIENT_TLS_CERTIFICATE_FILE => self::CLIENT_TLS_CERTIFICATE_FILE_DEFAULT,
    self::CLIENT_TLS_KEY_PASSWORD => self::CLIENT_TLS_KEY_PASSWORD_DEFAULT,
    self::PERSONAL_ENCRYPTION => self::PERSONAL_ENCRYPTION_DEFAULT,
    self::CARDDAV_PROVISIONG_TAG => self::CARDDAV_PROVISIONG_TAG_DEFAULT,
    self::ALLOW_MANUAL_LOGIN => self::ALLOW_MANUAL_LOGIN_DEFAULT,
    self::LOGIN_MODE => self::LOGIN_MODE_DEFAULT,
  ];

  /** @var \OCP\IUser */
  private $user;

  /** @var string */
  private $userId;

  /** @var string */
  private $userPassword;

  /** @var ICredentials */
  private $credentials;

  /**
   * @var bool
   *
   * Whether to encrypt personal data with the user's password.
   */
  private $personalEncryption;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    IUserSession $userSession,
    ICredentialsStore $credentialsStore,
    private string $appName,
    private IConfig $config,
    private ICrypto $crypto,
    protected ILogger $logger,
  ) {
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
   * Effective login mode. The legacy boolean "allowManualLogin" maps to
   * "formOnly" as long as no explicit mode has been saved.
   *
   * @return string One of the LOGIN_MODE_* constants.
   */
  public function loginMode():string
  {
    $mode = $this->config->getAppValue($this->appName, self::LOGIN_MODE, '');
    if (in_array($mode, self::LOGIN_MODES, true)) {
      return $mode;
    }
    $legacy = $this->config->getAppValue($this->appName, self::ALLOW_MANUAL_LOGIN, '');
    return filter_var($legacy, FILTER_VALIDATE_BOOLEAN)
      ? self::LOGIN_MODE_FORM_ONLY
      : self::LOGIN_MODE_DEFAULT;
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
   * Resolve the email address of the configured account for the current
   * user, according to the configured address-choice strategy.
   *
   * @return null|string The email address, or null when it cannot be
   * determined.
   */
  public function emailAddress()
  {
    $emailAddressChoice = $this->getAppValue(self::EMAIL_ADDRESS_CHOICE, self::EMAIL_ADDRESS_CHOICE_USER_PREFERENCES);
    $emailDefaultDomain = $this->getAppValue(self::EMAIL_DEFAULT_DOMAIN, self::EMAIL_DEFAULT_DOMAIN_DEFAULT);
    switch ($emailAddressChoice) {
      case self::EMAIL_ADDRESS_CHOICE_USER_ID:
        $userEmail = $this->user->getUID();
        if (strpos($userEmail, '@') === false && !empty($emailDefaultDomain)) {
          $userEmail .= '@'.$emailDefaultDomain;
        }
        return $userEmail;
      case self::EMAIL_ADDRESS_CHOICE_USER_PREFERENCES:
        return $this->user->getEMailAddress();
      case self::EMAIL_ADDRESS_CHOICE_USER_CHOSEN:
        return $this->getPersonalValue('emailAddress', '');
      case self::EMAIL_ADDRESS_CHOICE_FIXED_SINGLE_ADDRESS:
        return $this->getAppValue(self::FIXED_SINGLE_EMAIL_ADDRESS, self::FIXED_SINGLE_EMAIL_ADDRESS_DEFAULT);
    }
    return null;
  }

  /**
   * Return the login credentials for the configured email account for
   * the current user.
   *
   * @return array|false
   * ```
   * [
   *   userId: BLAH@FOO.BAR,
   *   password: PASSWORD,
   * ]
   * ```
   */
  public function emailCredentials()
  {
    $userEmail = $this->emailAddress();
    if (empty($userEmail)) {
      $this->logError('Unable to obtain email credentials for user ' . $this->userId);
      return false;
    }
    $userPassword = null;
    $emailAddressChoice = $this->getAppValue(self::EMAIL_ADDRESS_CHOICE, self::EMAIL_ADDRESS_CHOICE_USER_PREFERENCES);
    if ($emailAddressChoice === self::EMAIL_ADDRESS_CHOICE_FIXED_SINGLE_ADDRESS) {
      $userPassword = $this->getAppValue(self::FIXED_SINGLE_EMAIL_PASSWORD, self::FIXED_SINGLE_EMAIL_PASSWORD_DEFAULT, encrypted: true);
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
