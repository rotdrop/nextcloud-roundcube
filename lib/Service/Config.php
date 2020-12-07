<?php
/**
 * nextCloud - RoundCube mail plugin
 *
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
    $this->user = $userSession->getUser();
    $this->userId = $this->user->getUID();
    try {
      $this->credentials = $credentialsStore->getLoginCredentials();
      $this->userPassword = $this->credentials->getPassword();
    } catch (\Throwable $t) {
      $this->logException($t);
      $this->credentials = null;
      $this->userPassword = null;
    }
    $this->config = $config;
    $this->crypto = $crypto;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function getAppValue(string $key, $default = null) {
    return $this->config->getAppValue($this->appName, $key, $default);
  }

  public function setAppValue(string $key, $value) {
    return $this->config->setAppValue($this->appName, $key, $value);
  }

  public function getPersonalValue(string $key, $default = null, $password = null)
  {
    if (empty($password)) {
      $password = $this->userPassword;
    }
    $value = $this->config->getUserValue($this->userId, $this->appName, $key, $default);
    if (!empty($value) && $value !== $default) {
      try {
        $value = $this->crypto->decrypt($value, $password);
      } catch (\Throwable $t) {
        $this->logException($t);
        $value = $default;
      }
    }
    return $value;
  }

  public function setPersonalValue(string $key, $value, $password = null)
  {
    if (empty($password)) {
      $password = $this->userPassword;
    }
    $value = $this->crypto->encrypt($value, $this->userPassword);
    return $this->config->setUserValue($this->userId, $this->appName, $key, $value);
  }

  public function recryptPersonalValues($oldPassword, $newPassword)
  {
    $keys = $this->config->getUserKeys($this->userId, $this->appName);
    foreach ($keys as $key) {
      $value = $this->getPersonalValue($key, null, $oldPassword);
      if ($value !== null) {
        $this->setPersonalValue($key, $value, $newPassword);
      }
    }
  }
}
