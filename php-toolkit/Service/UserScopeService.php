<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023, 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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
use DateTimeImmutable;
use InvalidArgumentException;

use OC\Authentication\Token\IProvider as TokenProvider;
use OC\Authentication\Token\IToken;

use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Authentication\LoginCredentials\IStore as CredentialsStore;
use OCP\IUserSession;
use OCP\IUserManager;

use OCA\RotDrop\Toolkit\Traits;

/** Set the current user and optionally also its login credentials from auth tokens. */
class UserScopeService
{
  use Traits\LoggerTrait;
  use Traits\AuthTokenTrait {
    getAuthToken as private getAuthTokenInternal;
    getAuthCookie as private getAuthCookieInternal;
    deleteAuthCookie as private deleteAuthCookieInternal;
    deleteAuthToken as public;
    getLoginCredentialsFromToken as public;
  }

  public const DEFAULT_COOKIE_NAME = 'oc_rotdrop_auth_cookie';
  public const DEFAULT_TOKEN_NAME = 'oc_rotdrop';
  public const DEFAULT_LIFETIME = 1800;

  /** @var string */
  protected $cookieName = self::DEFAULT_COOKIE_NAME;

  /** @var string */
  protected $tokenName = self::DEFAULT_TOKEN_NAME;

  /** @var string */
  protected int $lifeTime = self::DEFAULT_LIFETIME;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    protected LoggerInterface $logger,
    protected IRequest $request,
    protected CredentialsStore $credentialsStorage,
    protected TokenProvider $tokenProvider,
    protected ISecureRandom $secureRandom,
    protected IUserSession $userSession,
    protected IUserManager $userManager,
  ) {
  }
  // phpcs:enable

  /**
   * Generate and auth-token which seals the login credentials.
   *
   * @return array The generated or fetched auth token and token-passphrase as
   * ```
   * [ 'token' => TOKEN, 'passphrase' => PASSPHRASE ]
   * ```
   */
  public function getAuthToken():array
  {
    $expiry = (new DateTimeImmutable)->getTimestamp() + $this->lifeTime;
    return $this->getAuthTokenInternal($this->cookieName, $this->tokenName, $expiry);
  }

  /** @return null|string The token-passphrase from the auth-cookie or null. */
  public function getPassphraseFromCookie():?string
  {
    return $this->request->getCookie($this->cookieName);
  }

  /**
   * Delete the token and its cookie, e.g. at logout time.
   *
   * @return void
   */
  public function deleteAuthCookie():void
  {
    $passphrase = $this->request->getCookie($this->cookieName);
    if (empty($passphrase)) {
      return;
    }
    $this->deleteAuthToken($passphrase);
    $this->deleteAuthCookieInternal($this->cookieName);
  }

  /**
   * @param null|string $uid
   *
   * @param null|string $loginUid
   *
   * @param null|string $loginPassword
   *
   * @return void
   */
  public function setUserScope(
    ?string $uid = null,
    ?string $loginUid = null,
    ?string $loginPassword = null,
  ):void {
    if ($uid === null) {
      return;
    }

    if (!empty($loginPassword) && $loginUid == $uid) {
      $this->userSession->login($loginUid, $loginPassword);
      return; // setUser() already done by login
    }

    $user = $this->userManager->get($uid);
    if ($user === null) {
      throw new InvalidArgumentException('No user found for the uid ' . $uid);
    }

    $this->userSession->setUser($user);
  }
}
