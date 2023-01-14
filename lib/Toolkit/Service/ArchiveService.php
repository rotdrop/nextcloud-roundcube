<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use DateTimeInterface;

use wapmorgan\UnifiedArchive\ArchiveEntry;
use wapmorgan\UnifiedArchive\Exceptions as BackendExceptions;

use OCP\IL10N;
use Psr\Log\LoggerInterface as ILogger;
use OCP\Files\File;
use OCP\Util as CloudUtil;

use OCA\RotDrop\Toolkit\Backend\ArchiveFormats;
use OCA\RotDrop\Toolkit\Backend\ArchiveBackend;
use OCA\RotDrop\Toolkit\Exceptions;

/**
 * Wrapper around the actual archive backend class in order to interface with
 * the virtual storage and actual archive extraction controllers.
 */
class ArchiveService
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;
  use \OCA\RotDrop\Toolkit\Traits\UtilTrait;

  /**
   * @var string
   * Internal format of the underlying archive backend.
   */
  public const ARCHIVE_INFO_FORMAT = 'format';

  /**
   * @var string
   * Mime-type of the archive file.
   */
  public const ARCHIVE_INFO_MIME_TYPE = 'mimeType';

  /**
   * @var string
   *
   * The size of the archive file (not neccessarily the sum of the size of the
   * archive members).
   */
  public const ARCHIVE_INFO_SIZE = 'size';

  /**
   * @var string
   *
   * The sum of the compressed size of the archive members.
   */
  public const ARCHIVE_INFO_COMPRESSED_SIZE = 'compressedSize';

  /**
   * @var string
   *
   * The sum of the uncompressed size of the archive members.
   */
  public const ARCHIVE_INFO_ORIGINAL_SIZE = 'originalSize';

  /**
   * @var string
   *
   * The number of archive members (files) in the archive.
   */
  public const ARCHIVE_INFO_NUMBER_OF_FILES = 'numberOfFiles';

  /**
   * @var string
   *
   * Some archive formats support optional creator supplied comments.
   */
  public const ARCHIVE_INFO_COMMENT = 'comment';

  /**
   * @var string
   *
   * Propose a mount point name based on the archive name.
   */
  public const ARCHIVE_INFO_DEFAULT_MOUNT_POINT = 'defaultMountPoint';

  /**
   * @var string
   *
   * Compute the common path prefix of the archive members.
   */
  public const ARCHIVE_INFO_COMMON_PATH_PREFIX = 'commonPathPrefix';

  /**
   * @var string
   *
   * The basename of the backend driver class.
   */
  public const ARCHIVE_INFO_BACKEND_DRIVER = 'backendDriver';

  /**
   * @var array All array keys contained in the info-array obtained from
   * archiveInfo().
   */
  public const ARCHIVE_INFO_KEYS = [
    self::ARCHIVE_INFO_COMMENT,
    self::ARCHIVE_INFO_COMPRESSED_SIZE,
    self::ARCHIVE_INFO_FORMAT,
    self::ARCHIVE_INFO_MIME_TYPE,
    self::ARCHIVE_INFO_NUMBER_OF_FILES,
    self::ARCHIVE_INFO_ORIGINAL_SIZE,
    self::ARCHIVE_INFO_SIZE,
    self::ARCHIVE_INFO_COMMON_PATH_PREFIX,
    self::ARCHIVE_INFO_DEFAULT_MOUNT_POINT,
    self::ARCHIVE_INFO_BACKEND_DRIVER,
  ];

  /** @var null|int */
  private $sizeLimit = null;

  /** @var ArchiveBackend */
  private $archiver;

  /** @var File */
  private $fileNode;

  /** @var array */
  private $archiveFiles;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    ILogger $logger,
    ?IL10N $l = null,
  ) {
    $this->logger = $logger;
    $this->l = $l;
    $this->archiver = null;
    $this->fileNode = null;
  }
  // phpcs:enable

  /**
   * Set the localization to use.
   *
   * @param IL10N $l10n
   *
   * @return ArchiveService $this.
   *
   * @todo Maybe generate a dummy support app instead.
   */
  public function setL10N(IL10N $l10n):ArchiveService
  {
    $this->l = $l10n;

    return $this;
  }

  /**
   * Guard against undefined $this->l.
   *
   * @param string $formatString
   *
   * @param mixed $parameters
   *
   * @return string
   */
  protected function t(string $formatString, mixed $parameters = null):string
  {
    if (!empty($this->l)) {
      return $this->l->t($formatString, $parameters);
    }
    return vsprintf($formatString, $parameters);
  }

  /**
   * Set the size limit for the uncompressed size of the archives. Archives
   * with larger uncompressed size will not be handled.
   *
   * @param null|int $sizeLimit Size-limit. Pass null to disable.
   *
   * @return ArchiveService Return $this for chaining.
   */
  public function setSizeLimit(?int $sizeLimit):ArchiveService
  {
    $this->sizeLimit = $sizeLimit;
    return $this;
  }

  /**
   * Return the currently configured size-limit.
   *
   * @return null|int
   */
  public function getSizeLimit():?int
  {
    return $this->sizeLimit;
  }

  /**
   * Return the local operating system path of the given file-node.
   *
   * @param File $fileNode
   *
   * @return string
   */
  private static function getLocalPath(File $fileNode):string
  {
    return $fileNode->getStorage()->getLocalFile($fileNode->getInternalPath());
  }

  /**
   * Check whether the given file can be opened.
   *
   * @param File $fileNode
   *
   * @return bool
   */
  public function canOpen(File $fileNode):bool
  {
    return ArchiveBackend::canOpen(self::getLocalPath($fileNode));
  }

  /**
   * Close, i.e. unconfigure. This method is error agnostic, it simply unsets
   * the initial state variables.
   *
   * @return void
   */
  public function close():void
  {
    $this->archiver = null;
    $this->fileNode = null;
    $this->archiveFiles = null;
  }

  /**
   * @param File $fileNode
   *
   * @param null|int $sizeLimit
   *
   * @param null|string $password
   *
   * @return null|ArchiveService
   */
  public function open(File $fileNode, ?int $sizeLimit = null, ?string $password = null):?ArchiveService
  {
    if (!$this->canOpen($fileNode)) {
      throw new Exceptions\ArchiveCannotOpenException($this->t('Unable to open archive file %s (%s)', [
        $fileNode->getPath(), self::getLocalPath($fileNode),
      ]));
    }
    $this->archiver = ArchiveBackend::open(self::getLocalPath($fileNode), password: $password);
    if (empty($this->archiver)) {
      throw new Exceptions\ArchiveCannotOpenException($this->t('Unable to open archive file %s (%s)', [
        $fileNode->getPath(), self::getLocalPath($fileNode),
      ]));
    }
    if ($sizeLimit === null) {
      $sizeLimit = $this->sizeLimit;
    }
    $this->fileNode = $fileNode;
    $archiveInfo = $this->getArchiveInfo();
    $archiveSize = $archiveInfo['originalSize'];
    if ($sizeLimit !== null && $archiveSize > $sizeLimit) {
      $this->archiver = null;
      $this->fileNode = null;
      throw new Exceptions\ArchiveTooLargeException(
        $this->t('Uncompressed size of archive "%1$s" is too large: %2$s > %3$s', [
          $fileNode->getInternalPath(), CloudUtil::humanFileSize($archiveSize), CloudUtil::humanFileSize($sizeLimit),
        ]),
        $sizeLimit,
        $archiveInfo,
      );
    }

    return $this;
  }

  /** @return array Archive information, meta-data. */
  public function getArchiveInfo():array
  {
    if (empty($this->archiver)) {
      throw new Exceptions\ArchiveNotOpenException(
        $this->t('There is no archive file associated with this archiver instance.'));
    }
    // getComment() throws if not supported (API documents differently)
    try {
      $archiveComment = $this->archiver->getComment();
    } catch (BackendExceptions\UnsupportedOperationException $e) {
      // just ignored
      $archiveComment = null;
    }

    // $this->logInfo('MIME ' .  $this->fileNode->getMimeType());

    return [
      self::ARCHIVE_INFO_FORMAT => $this->archiver->getFormat(),
      self::ARCHIVE_INFO_MIME_TYPE => $this->fileNode->getMimeType(),
      self::ARCHIVE_INFO_SIZE => $this->archiver->getSize(),
      self::ARCHIVE_INFO_COMPRESSED_SIZE => $this->archiver->getCompressedSize(),
      self::ARCHIVE_INFO_ORIGINAL_SIZE => $this->archiver->getOriginalSize(),
      self::ARCHIVE_INFO_NUMBER_OF_FILES => $this->archiver->countFiles(),
      self::ARCHIVE_INFO_COMMENT => $archiveComment,
      self::ARCHIVE_INFO_DEFAULT_MOUNT_POINT => self::getArchiveFolderName($this->fileNode->getName()),
      self::ARCHIVE_INFO_COMMON_PATH_PREFIX => $this->getCommonDirectoryPrefix(),
      self::ARCHIVE_INFO_BACKEND_DRIVER => $this->getClassBaseName($this->archiver->getDriverType()),
    ];
  }

  /**
   * Return a proposal for the extraction destination. Currently, this simply
   * strips double extensions like FOO.tag.N -> FOO.
   *
   * @param string $archiveFileName
   *
   * @return string
   */
  public static function getArchiveFolderName(string $archiveFileName):?string
  {
    // double to account for "nested" archive types
    $archiveFolderName = pathinfo($archiveFileName, PATHINFO_FILENAME);
    $secondExtension = pathinfo($archiveFolderName, PATHINFO_EXTENSION);

    // as a rule of thumb we only strip the second extension if it contains no
    // spaces and is no longer that 4 characters.
    if (strlen($secondExtension) <= 4 && str_replace(' ', '', $secondExtension) === $secondExtension) {
      $archiveFolderName = pathinfo($archiveFolderName, PATHINFO_FILENAME);
    }

    return $archiveFolderName;
  }

  /**
   * Return the name of the top-level folder for the case that there is only a
   * single folder at folder nesting level 0.
   *
   * @return null|string
   */
  public function getCommonDirectoryPrefix():?string
  {
    return $this->getCommonPath(array_keys($this->getFiles()), leadingSlash: false);
  }

  /**
   * @return array<string, ArchiveEntry>
   */
  public function getFiles():array
  {
    if (empty($this->archiver)) {
      throw new Exceptions\ArchiveNotOpenException(
        $this->t('There is no archive file associated with this archiver instance.'));
    }
    foreach ($this->archiver->getFileNames() as $fileName) {
      $fileData = $this->archiver->getFileData($fileName);
      // work around a bug in UnifiedArchive
      if ($fileData->modificationTime instanceof DateTimeInterface) {
        $fileData->modificationTime = $fileData->modificationTime->getTimestamp();
      }
      $this->archiveFiles[$fileName] = $fileData;
    }
    return $this->archiveFiles;
  }

  /**
   * @param string $fileName
   *
   * @return null|string
   */
  public function getFileContent(string $fileName):?string
  {
    if (empty($this->archiver)) {
      throw new Exceptions\ArchiveNotOpenException(
        $this->t('There is no archive file associated with this archiver instance.'));
    }
    return $this->archiver->getFileContent($fileName);
  }

  /**
   * @param string $fileName
   *
   * @return null|resource
   */
  public function getFileStream(string $fileName)
  {
    if (empty($this->archiver)) {
      throw new Exceptions\ArchiveNotOpenException(
        $this->t('There is no archive file associated with this archiver instance.'));
    }
    return $this->archiver->getFileStream($fileName);
  }
}
