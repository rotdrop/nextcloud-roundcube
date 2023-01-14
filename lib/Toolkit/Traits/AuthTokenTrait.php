<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Traits;

use Throwable;
use DateTimeImmutable;

use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Authentication\LoginCredentials\IStore as CredentialsStore;
use OC\Authentication\Token\IProvider as TokenProvider;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IToken;

/**
 * Trait for obtaining a cookie secured auth-token.
 */
trait AuthTokenTrait
{
  use LoggerTrait;

  /** @var TokenProvider */
  protected $tokenProvider;

  /** @var CredentialsStore */
  protected $credentialsStore;

  /** @var IRequest */
  protected $request;

  /** @var ISecureRandom */
  protected $secureRandom;

  /**
   * Fetch the login credentials from the credentials store and generate a
   * login-token from it.
   *
   * @param string $cookieName The name of the client cookie providing the
   * token password.
   *
   * @param string $tokenName Fancy token name.
   *
   * @param int $tokenExpiry Faked token activity to set in order to prevent
   * expiry before that time. Unix timestamp.*
   *
   * @return array The generated or fetched auth token and token-passphrase as
   * ```
   * [ 'token' => TOKEN, 'passphrase' => PASSPHRASE ]
   * ```
   */
  protected function getAuthToken(string $cookieName, string $tokenName, int $tokenExpiry):array
  {
    try {
      $credentials = $this->credentialsStore->getLoginCredentials();
      $passphrase = $this->getAuthCookie($cookieName, $tokenExpiry);
      try {
        $token = $this->tokenProvider->getToken($passphrase);
      } catch (\OC\Authentication\Exceptions\InvalidTokenException $e) {
        $token = $this->tokenProvider->generateToken(
          $passphrase,
          $credentials->getUID(),
          $credentials->getLoginName(),
          $credentials->getPassword(),
          $tokenName,
        );
      }
      $token->setLastActivity($tokenExpiry); // this may be in the future, but for the moment prevents the cleanup
      $this->tokenProvider->updateToken($token);
      return [ 'token' => $token, 'passphrase' => $passphrase ];
    } catch (Throwable $t) {
      if (!empty($this->logger)) {
        $this->logException($t);
      }
      return [ 'token' => null, 'passphrase' => null ];
    }
  }

  /**
   * Get or generate a cookie, e.g. to use it as a passphrase for a login
   * token.
   *
   * @param string $cookieName
   *
   * @param int $expires
   *
   * @return string The value of the cookie.
   */
  protected function getAuthCookie(string $cookieName, int $expires):string
  {
    $passphrase = $this->request->getCookie($cookieName);
    if ($passphrase === null) {
      $passphrase = $this->secureRandom->generate(128);
      $secureCookie = $this->request->getServerProtocol() === 'https';
      $webRoot = \OC::$WEBROOT;
      if ($webRoot === '') {
        $webRoot = '/';
      }
      setcookie(
        $cookieName,
        $passphrase,
        [
          'expires' => $expires,
          'path' => $webRoot,
          'domain' => '',
          'secure' => $secureCookie,
          'httponly' => true,
          'samesite' => 'Lax',
        ]
      );
    }
    return $passphrase;
  }

  /**
   * @param string $cookieName Unset the cookie with the given name.
   *
   * @return void
   */
  protected function deleteAuthCookie(string $cookieName):void
  {
    $secureCookie = $this->request->getServerProtocol() === 'https';
    $webRoot = \OC::$WEBROOT;
    if ($webRoot === '') {
      $webRoot = '/';
    }
    $now = (new DateTimeImmutable)->getTimestamp();
    setcookie($cookieName, '', $now - 3600, $webRoot, '', $secureCookie, true);
  }

  /**
   * Delete any auth token secured with the given passphrase.
   *
   * @param string $passphrase Token passphrase.
   *
   * @return void
   */
  protected function deleteAuthToken(string $passphrase):void
  {
    try {
      $token = $this->tokenProvider->getToken($passphrase);
      $this->tokenProvider->invalidateToken($token);
    } catch (\Throwable $t) {
      // ignore
      if (!empty($this->logger)) {
        $this->logException($t);
      }
    }
  }

  /**
   * Try to fetch the login-credentials contained in the auth token which is
   * secured with the given password.
   *
   * @param string $passphrase
   *
   * @return array
   * ```
   * [ 'loginUID' => LOGIN_UID, 'loginPassword' => LOGIN_PASSWORD ]
   * ```
   */
  protected function getLoginCredentialsFromToken(string $passphrase):array
  {
    $loginUID = null;
    $loginPassword = null;
    try {
      $token = $this->tokenProvider->getToken($passphrase);
      $loginUID = $token->getUID();
      $loginPassword = $this->tokenProvider->getPassword($token, $passphrase);

      $expiry = max($token->getLastActivity(), (new DateTimeImmutable)->getTimestamp() + 1800);
      $token->setLastActivity($expiry);
      $this->tokenProvider->updateToken($token);
    } catch (InvalidTokenException $e) {
      // ignore
      if (!empty($this->logger)) {
        $this->logException($e);
      }
    } catch (Throwable $t) {
      // ignore
      $this->logException($t);
    }
    return [ 'loginUID' => $loginUID, 'loginPassword' => $loginPassword ];
  }
}
