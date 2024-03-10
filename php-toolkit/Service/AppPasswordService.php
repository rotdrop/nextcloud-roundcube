<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\RotDrop\Toolkit\Service;

use Throwable;

use Psr\Log\LoggerInterface;

use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\Authentication\LoginCredentials\ICredentials as LoginCredentials;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OC\Authentication\Token\IProvider as TokenProvider;
use OC\Authentication\Token\IToken;

/**
 * Service class for generating and perhaps maintaining app-passwords. The use
 * case is to automatically distribute passwords as needed, e.g. enable access
 * of the CardDAV Roundcube plugin to the CardDAV service of Nextcloud.
 */
class AppPasswordService
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  /**
   * @param IUserSession $userSession
   *
   * @param LoggerInterface $logger
   *
   * @param TokenProvider $tokenProvider
   *
   * @param ISecureRandom $random
   *
   * @param ICredentialsStore $credentialsStore
   */
  public function __construct(
    protected IUserSession $userSession,
    protected LoggerInterface $logger,
    protected TokenProvider $tokenProvider,
    protected ISecureRandom $random,
    protected ICredentialsStore $credentialsStore,
  ) {
  }

  /**
   * Generate a new app-password.
   *
   * @param string $name
   *
   * @return null|array
   */
  public function generateAppPassword(string $name):?array
  {
    $user = $this->userSession->getUser();
    if (empty($user)) {
      return null;
    }
    $userId = $user->getUID();
    try {
      /** @var LoginCredentials $loginCredentials */
      $loginCredentials = $this->credentialsStore->getLoginCredentials();
      $loginName = $loginCredentials->getLoginName();
      $password = $loginCredentials->getPassword();
    } catch (Throwable $t) {
      $this->logException($t);
      return null;
    }
    if ($loginName != $userId) {
      // when can this happen?
      $this->logInfo('loginName: "' . $loginName . '", user-id: "' . $userId . '"');
    }
    $token = $this->generateRandomDeviceToken();
    $deviceToken = $this->tokenProvider->generateToken(
      $token, $userId, $loginName, $password, $name, IToken::PERMANENT_TOKEN,
    );

    return [
      'token' => $token,
      'loginName' => $loginName,
      'deviceToken' => $deviceToken,
    ];
  }

  /**
   * Return a 25 digit device password. Borrowed from the settings app.
   *
   * Example: AbCdE-fGhJk-MnPqR-sTwXy-23456
   *
   * @return string
   */
  private function generateRandomDeviceToken()
  {
    $groups = [];
    for ($i = 0; $i < 5; $i++) {
      $groups[] = $this->random->generate(5, ISecureRandom::CHAR_HUMAN_READABLE);
    }
    return implode('-', $groups);
  }
}
