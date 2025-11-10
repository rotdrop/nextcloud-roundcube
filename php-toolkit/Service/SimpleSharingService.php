<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

use OCP\Files\Node as FileSystemNode;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

use OCA\RotDrop\Toolkit\Traits\Constants;

/**
 * Support class for the creating cloud shared, currently only web-links can
 * be generated.
 */
class SimpleSharingService
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  private const INDEX_PHP = Constants::PATH_SEPARATOR . 'index.php';
  public const LINK_SHARE_PREFIX = Constants::PATH_SEPARATOR . 's' . Constants::PATH_SEPARATOR;

  /** {@inheritdoc} */
  public function __construct(
    private IShareManager $shareManager,
    private IURLGenerator $urlGenerator,
    private IUserSession $userSession,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Create a link-share for the given file-system node. If the node is
   * already shared with the requested permissions then just return the old
   * share.
   *
   * @param FileSystemNode $node The cloud file-system node which shall be shared.
   *
   * @param null|string $shareOwner User-id of the owner.
   *
   * @param int $sharePerms Permissions for the link. Defaults to PERMISSION_CREATE.
   *
   * @param null|false|DateTimeInterface $expirationDate Optional expiration
   * date for the link. If null then create a link which does not expire. If
   * \false then just ignore the expiration date. Otherwise do an exact match
   * on the given date. Default to null.
   *
   * @param null|false|string $password Optional password. If \false ignore,
   * if null create a passwordless share. The share password is updated even
   * if $noCreate is \true.
   *
   * @param bool $noCreate Do not create a new share, but return an existing
   * share if it exists.
   *
   * @param null|string $newShareOwner If given try to modify the share to use
   * the new owner. The share-owner is corrected event if $noCreate is \true.
   *
   * @return null|array The absolute URLs for the share or null.
   * ```
   * [ 'files_sharing': URL, 'webdav' => DAV_URL, 'share' => ISHARE_INSTANCE ]
   * ```
   */
  public function linkShare(
    FileSystemNode $node,
    ?string $shareOwner = null,
    int $sharePerms = \OCP\Constants::PERMISSION_CREATE,
    mixed $expirationDate = null,
    ?string $password = null,
    bool $noCreate = false,
    ?string $newShareOwner = null,
  ):?array {
    $this->logDebug('shared folder id ' . $node->getId());

    $shareType = IShare::TYPE_LINK;

    if ($shareOwner === null) {
      $shareOwner = $this->userSession->getUser()?->getUID();
    }

    if ($expirationDate instanceof DateTimeInterface) {
      // make sure it is UTC midnight
      $expirationDate = new DateTimeImmutable($expirationDate->format('Y-m-d'));
    }

    $share = null;

    try {
      /** @var IShare $share */
      foreach ($this->shareManager->getSharesBy($shareOwner, $shareType, $node, false, -1) as $share) {
        // check permissions
        if ($share->getPermissions() !== $sharePerms) {
          $share = null;
          continue;
        }

        if ($expirationDate !== false) {
          $expirationTimeStamp = $expirationDate === null ? -1 : $expirationDate->getTimestamp();

          // check expiration time
          $shareExpirationDate = $share->getExpirationDate();

          $shareExpirationStamp = $shareExpirationDate === null ? -1 : $shareExpirationDate->getTimestamp();

          if ($shareExpirationStamp != $expirationTimeStamp) {
            $share = null;
            continue;
          }
        }

        // check permissions
        if ($share->getPermissions() !== $sharePerms) {
          $share = null;
          continue;
        }

        if ($newShareOwner !== null && $newShareOwner !== $shareOwner) {
          $share->setShareOwner($newShareOwner);
          $share->setSharedBy($newShareOwner);
          $this->shareManager->updateShare($share);
        }


        if ($password !== false
            && $share->getPassword() !== $password // both null is ok
            && !$this->shareManager->checkPassword($share, $password)) {
          $share->setPassword($password);
          $this->shareManager->updateShare($share);
        }

        break;
      }

      if ($share === null) {
        if ($noCreate) {
          return null;
        }

        // None found, generate a new one

        if ($newShareOwner !== null && $newShareOwner !== $shareOwner) {
          $shareOwner = $newShareOwner;
        }

        /** @var IShare $share */
        $share = $this->shareManager->newShare();
        $share->setNode($node);
        $share->setPermissions($sharePerms);
        $share->setShareType($shareType);
        $share->setShareOwner($shareOwner);
        $share->setSharedBy($shareOwner);
        if ($password !== false) {
          $share->setPassword($password);
        }
        if ($expirationDate !== false) {
          $share->setExpirationDate($expirationDate);
        }

        $share = $this->shareManager->createShare($share);
        if ($share->getShareOwner() != $shareOwner || $share->getSharedBy() != $shareOwner) {
          // the manager insist on $node->getOwner() on created, but for the
          // time being allows modification later on.
          $share->setShareOwner($shareOwner);
          $share->setSharedBy($shareOwner);
          $this->shareManager->updateShare($share);
        }
      }
    } catch (Throwable $t) {
      $this->logException('Unable to find, modify or generate link-share for "' . $node->getPath() . '".');
    }

    $token = $share->getToken();
    $filesSharing = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $token]);
    $dav = $this->urlGenerator->getAbsoluteURL('/public.php/dav/files/' . $token);

    return [
      'files_sharing' => $filesSharing,
      'dav' => $dav,
      'share' => $share,
    ];
  }

  /**
   * Expire all shares of the respective user of the respective type of the given file-system node by
   * setting their expiration time to the current or the given time.
   *
   * @param FileSystemNode $node
   *
   * @param null|string $shareOwner
   *
   * @param null|DateTimeInterface $expirationDate Optional expiration date for the link.
   *
   * @param int $shareType Defaults to IShare::TYPE_LINK.
   *
   * @return int The number of changed shares
   */
  public function expire(
    FileSystemNode $node,
    ?string $shareOwner = null,
    ?\DateTimeInterface $expirationDate = null,
    int $shareType = IShare::TYPE_LINK,
  ):int {
    if ($shareOwner === null) {
      $shareOwner = $this->userSession->getUser()?->getUID();
    }

    if ($expirationDate === null) {
      $expirationDate = new DateTimeImmutable;
    }

    $numChanged = 0;

    /** @var IShare $share */
    foreach ($this->shareManager->getSharesBy($shareOwner, $shareType, $node, false, -1) as $share) {
      $shareExpirationDate = $share->getExpirationDate();
      if ($shareExpirationDate === null || $shareExpirationDate > $expirationDate) {
        $share->setExpirationDate($expirationDate);
        $this->shareManager->updateShare($share);
        ++$numChanged;
      }
    }
    return $numChanged;
  }

  /**
   * Delete all shares of the respective user of the respective type of the given file-system node by
   * setting their expiration time to the current time.
   *
   * @param FileSystemNode $node
   *
   * @param null|string $shareOwner
   *
   * @param int $shareType Defaults to IShare::TYPE_LINK.
   *
   * @return int The number of deleted shares
   */
  public function delete(FileSystemNode $node, ?string $shareOwner, int $shareType = IShare::TYPE_LINK)
  {
    if ($shareOwner === null) {
      $shareOwner = $this->userSession->getUser()?->getUID();
    }

    $numDeleted = 0;

    /** @var IShare $share */
    foreach ($this->shareManager->getSharesBy($shareOwner, $shareType, $node, false, -1) as $share) {
      $this->shareManager->deleteShare($share);
      ++$numDeleted;
    }
    return $numDeleted;
  }

  /**
   * Delete the given share.
   *
   * @param string $token The share token or the web-url to the share which
   * carries the token as last path component.
   *
   * @return bool Execution status.
   */
  public function deleteLinkShare(string $token): bool
  {
    $share = $this->getShareFromUrl($token);
    if ($share === null) {
      return false;
    }
    $this->shareManager->deleteShare($share);
    return true;
  }

  /**
   * Expire just the given share.
   *
   * @param string $token The share token or the web-url to the share which
   * carries the token as last path component.
   *
   * @param null|DateTimeInterface $expirationDate Optional expiration date
   * for the link. If null then it is only made sure the share is expired from
   * the current date on, if non-null the expiration date is actually set to
   * the requested value.
   *
   * @return null|DateTimeInterface The actual expiration date of the share or
   * null if the share cannot be found.
   */
  public function expireLinkShare(string $token, ?\DateTimeInterface $expirationDate = null):?DateTimeInterface
  {
    $share = $this->getShareFromUrl($token);
    if ($share === null) {
      return null;
    }

    if ($expirationDate === null) {
      $now = new DateTimeImmutable;
      $shareExpirationDate = $share->getExpirationDate();
      if ($shareExpirationDate === null || $shareExpirationDate > $now) {
        $expirationDate = $now;
      }
    }

    if ($expirationDate !== null) {
      $shareExpirationDate = $expirationDate;
      $share->setExpirationDate($expirationDate);
      $this->shareManager->updateShare($share);
    }

    return $shareExpirationDate;
  }

  /**
   * @param string $token The share token or the web-url to the share which
   * carries the token as last path component.
   *
   * @return null|false|\DateTimeInterface The expiration date of the given share or null.
   */
  public function getLinkExpirationDate(string $token):mixed
  {
    $share = $this->getShareFromUrl($token);
    if ($share === null) {
      return false;
    }
    $shareExpirationDate = $share->getExpirationDate();

    return $shareExpirationDate;
  }

  /**
   * @param string $url Share token or the url to it. Allowed urls have the form
   * https://NEXTCLOUD_BASE_URI/s/TOKEN/FURTHER/PATH/COMPOINENTS.
   *
   * @return null|IShare The share if is found, null otherwise.
   */
  public function getShareFromUrl(string $url):?IShare
  {
    $baseUrl = $this->urlGenerator->getBaseUrl();
    if (str_starts_with($url, $baseUrl)) {
      $url = substr($url, strlen($baseUrl));
    }
    $webRoot = $this->urlGenerator->getWebroot();
    if (str_starts_with($url, $webRoot)) {
      $url = substr($url, strlen($webRoot));
    }
    if (str_starts_with($url, self::INDEX_PHP)) {
      $url = substr($url, strlen(self::INDEX_PHP));
    }
    $urlParts = parse_url($url);
    $urlPath = $urlParts['path'];
    if (str_starts_with($urlPath, self::LINK_SHARE_PREFIX)) {
      $token = explode(Constants::PATH_SEPARATOR, substr($urlPath, strlen(self::LINK_SHARE_PREFIX)))[0];
    } else {
      $token = $urlPath;
    }

    $share = $this->shareManager->getShareByToken($token);

    return $share;
  }

  /**
   * Create a simple group-share (default read-only)
   *
   * @param FileSystemNode $node
   *
   * @param string $groupId
   *
   * @param null|string $target The targer location in the file-space of the
   * consumer.
   *
   * @param int $permissions Default read-only.
   *
   * @param bool $sharedByOwner Pretend this was shared by the owner of the
   * node instead of the currently logged in user.
   *
   * @return bool
   */
  public function groupShareNode(
    FileSystemNode $node,
    string $groupId,
    ?string $target = null,
    int $permissions = \OCP\Constants::PERMISSION_READ,
    bool $sharedByOwner = false,
  ):bool {
    $ownerId = ($node->getOwner() ?? $this->userSession->getUser())?->getUID();
    $sharedById = $sharedByOwner ? $ownerId : $this->userSession->getUser()?->getUID();

    /** @var IShare $share */
    foreach ($this->shareManager->getSharesBy(userId: $ownerId, shareType: IShare::TYPE_GROUP, reshares: true) as $share) {
      if ($share->getNodeId() === $node->getId() && $share->getSharedWith() == $groupId) {
        // check permissions
        if ($share->getPermissions() !== $permissions) {
          $share->setPermissions($permissions);
        }
        if ($share->getSharedBy() !== $sharedById) {
          $share->setSharedBy($sharedById);
        }
        if ($share->getShareOwner() !== $ownerId) {
          $share->setShareOwner($ownerId);
        }
        $this->shareManager->updateShare($share);
        return $share->getPermissions() === $permissions
          && $share->getShareOwner() === $ownerId
          && $share->getSharedBy() === $sharedById;
      }
    }

    // Otherwise it should be legal to attempt a new share ...
    $share = $this->shareManager->newShare();
    $share->setNode($node);
    $share->setSharedWith($groupId);
    $share->setPermissions($permissions);
    $share->setShareType(IShare::TYPE_GROUP);
    $share->setShareOwner($ownerId);
    $share->setSharedBy($sharedById);

    try {
      $share = $this->shareManager->createShare($share);
      if ($share->getShareOwner() != $ownerId || $share->getSharedBy() != $sharedById) {
        // the manager insist on $node->getOwner() on created, but for the
        // time being allows modification later on.
        $share->setShareOwner($ownerId);
        $share->setSharedBy($sharedById);
        $this->shareManager->updateShare($share);
      }


      return true;
    } catch (Throwable $t) {
      $this->logException($t);
      return false;
    }
  }
}
