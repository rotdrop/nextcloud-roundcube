<?php
/**
 * Calendar Controller for RoundCube App.
 *
 * Handles calendar event operations with proper handling of
 * Nextcloud's soft-deleted events (orphaned UIDs).
 *
 * @author Laurent Dinclaux <laurent@gecka.nc>
 * @copyright 2026 Gecka
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\RoundCube\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Controller for calendar event operations.
 */
class CalendarController extends Controller
{
  private IUserSession $userSession;
  private IDBConnection $db;
  private LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param string $appName
   * @param IRequest $request
   * @param IUserSession $userSession
   * @param IDBConnection $db
   * @param LoggerInterface $logger
   */
  public function __construct(
    string $appName,
    IRequest $request,
    IUserSession $userSession,
    IDBConnection $db,
    LoggerInterface $logger
  ) {
    parent::__construct($appName, $request);
    $this->userSession = $userSession;
    $this->db = $db;
    $this->logger = $logger;
  }

  /**
   * Add an event to a calendar.
   * Handles orphaned UIDs from soft-deleted events by purging them first.
   *
   * @param string $calendarUri The calendar URI.
   * @param string $icsContent The ICS content.
   *
   * @return JSONResponse
   *
   * @NoAdminRequired
   */
  public function addEvent(string $calendarUri, string $icsContent): JSONResponse
  {
    $user = $this->userSession->getUser();
    if ($user === null) {
      return new JSONResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
    }

    $userId = $user->getUID();

    // Extract calendar name from URI if it's a full path
    // e.g., /remote.php/dav/calendars/user/personal/ -> personal
    $calendarName = $this->extractCalendarName($calendarUri);
    if ($calendarName === null) {
      return new JSONResponse(['error' => 'Invalid calendar URI'], Http::STATUS_BAD_REQUEST);
    }

    // Get calendar ID from name
    $calendarId = $this->getCalendarIdByName($userId, $calendarName);
    if ($calendarId === null) {
      return new JSONResponse(['error' => 'Calendar not found'], Http::STATUS_NOT_FOUND);
    }

    // Clean ICS: remove METHOD line (REQUEST/REPLY/CANCEL are for iMIP, not CalDAV)
    $cleanedIcs = preg_replace('/^METHOD:[^\r\n]*\r?\n/im', '', $icsContent);

    // Extract UID from ICS
    $uid = $this->extractUidFromIcs($cleanedIcs);
    if ($uid === null) {
      // Generate a new UID if not present
      $uid = $this->generateUid();
      // Insert UID into ICS after BEGIN:VEVENT
      $cleanedIcs = preg_replace(
        '/(BEGIN:VEVENT\r?\n)/i',
        '$1UID:' . $uid . "\r\n",
        $cleanedIcs,
        1
      );
    }

    // Check if event already exists (visible, not soft-deleted)
    $existingEvent = $this->getExistingEvent($calendarId, $uid);
    $updated = $existingEvent !== null;

    // Check for orphaned UID (soft-deleted event)
    $orphanedEvent = $this->getOrphanedEvent($calendarId, $uid);
    if ($orphanedEvent !== null) {
      $this->logger->debug('Purging orphaned calendar event', [
        'calendarId' => $calendarId,
        'uid' => $uid,
        'objectId' => $orphanedEvent['id'],
      ]);
      $this->purgeOrphanedEvent((int)$orphanedEvent['id']);
      $updated = true; // Treat as update since we're replacing a deleted event
    }

    // Now add/update the event via CalDAV backend
    try {
      $this->saveEventToCalendar($calendarId, $uid, $cleanedIcs, $existingEvent !== null);
    } catch (\Exception $e) {
      $this->logger->error('Failed to save calendar event', [
        'calendarId' => $calendarId,
        'uid' => $uid,
        'error' => $e->getMessage(),
      ]);
      return new JSONResponse([
        'error' => 'Failed to save event: ' . $e->getMessage(),
      ], Http::STATUS_INTERNAL_SERVER_ERROR);
    }

    return new JSONResponse([
      'success' => true,
      'updated' => $updated,
      'uid' => $uid,
    ]);
  }

  /**
   * Extract calendar name from URI.
   *
   * @param string $uri The calendar URI.
   *
   * @return string|null
   */
  private function extractCalendarName(string $uri): ?string
  {
    // Remove trailing slash
    $uri = rtrim($uri, '/');

    // If it's a full path, extract the last segment
    if (preg_match('#/calendars/[^/]+/([^/]+)$#', $uri, $matches)) {
      return $matches[1];
    }

    // If it's just the calendar name
    if (!str_contains($uri, '/')) {
      return $uri;
    }

    return null;
  }

  /**
   * Get calendar ID by name for a user.
   *
   * @param string $userId The user ID.
   * @param string $calendarName The calendar name.
   *
   * @return int|null
   */
  private function getCalendarIdByName(string $userId, string $calendarName): ?int
  {
    $qb = $this->db->getQueryBuilder();
    $qb->select('id')
      ->from('calendars')
      ->where($qb->expr()->eq('uri', $qb->createNamedParameter($calendarName)))
      ->andWhere($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId)));

    $result = $qb->executeQuery();
    $row = $result->fetch();
    $result->closeCursor();

    return $row !== false ? (int)$row['id'] : null;
  }

  /**
   * Extract UID from ICS content.
   *
   * @param string $ics The ICS content.
   *
   * @return string|null
   */
  private function extractUidFromIcs(string $ics): ?string
  {
    if (preg_match('/^UID:([^\r\n]+)/im', $ics, $matches)) {
      return trim($matches[1]);
    }
    return null;
  }

  /**
   * Generate a new UID.
   *
   * @return string
   */
  private function generateUid(): string
  {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }

  /**
   * Get existing event (not soft-deleted).
   *
   * @param int $calendarId The calendar ID.
   * @param string $uid The event UID.
   *
   * @return array|null
   */
  private function getExistingEvent(int $calendarId, string $uid): ?array
  {
    $qb = $this->db->getQueryBuilder();
    $qb->select('id', 'uri', 'etag')
      ->from('calendarobjects')
      ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId)))
      ->andWhere($qb->expr()->eq('calendartype', $qb->createNamedParameter(0))) // 0 = regular calendar
      ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
      ->andWhere($qb->expr()->isNull('deleted_at'));

    $result = $qb->executeQuery();
    $row = $result->fetch();
    $result->closeCursor();

    return $row !== false ? $row : null;
  }

  /**
   * Get orphaned event (soft-deleted, still has UID constraint).
   *
   * @param int $calendarId The calendar ID.
   * @param string $uid The event UID.
   *
   * @return array|null
   */
  private function getOrphanedEvent(int $calendarId, string $uid): ?array
  {
    $qb = $this->db->getQueryBuilder();
    $qb->select('id', 'uri')
      ->from('calendarobjects')
      ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId)))
      ->andWhere($qb->expr()->eq('calendartype', $qb->createNamedParameter(0)))
      ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
      ->andWhere($qb->expr()->isNotNull('deleted_at'));

    $result = $qb->executeQuery();
    $row = $result->fetch();
    $result->closeCursor();

    return $row !== false ? $row : null;
  }

  /**
   * Purge an orphaned (soft-deleted) event from the database.
   *
   * @param int $objectId The calendar object ID.
   *
   * @return void
   */
  private function purgeOrphanedEvent(int $objectId): void
  {
    // Delete from calendarobjects
    $qb = $this->db->getQueryBuilder();
    $qb->delete('calendarobjects')
      ->where($qb->expr()->eq('id', $qb->createNamedParameter($objectId)));
    $qb->executeStatement();

    // Also delete from calendarobjects_props if exists
    $qb = $this->db->getQueryBuilder();
    $qb->delete('calendarobjects_props')
      ->where($qb->expr()->eq('objectid', $qb->createNamedParameter($objectId)));
    $qb->executeStatement();
  }

  /**
   * Save event to calendar using CalDAV backend.
   *
   * @param int $calendarId The calendar ID.
   * @param string $uid The event UID.
   * @param string $icsContent The ICS content.
   * @param bool $isUpdate Whether this is an update operation.
   *
   * @return void
   */
  private function saveEventToCalendar(int $calendarId, string $uid, string $icsContent, bool $isUpdate): void
  {
    /** @var \OCA\DAV\CalDAV\CalDavBackend $caldavBackend */
    $caldavBackend = \OC::$server->get(\OCA\DAV\CalDAV\CalDavBackend::class);

    $uri = $uid . '.ics';

    if ($isUpdate) {
      // Get current etag for update
      $existingEvent = $this->getExistingEvent($calendarId, $uid);
      if ($existingEvent !== null) {
        $caldavBackend->updateCalendarObject($calendarId, $existingEvent['uri'], $icsContent);
      } else {
        // Shouldn't happen, but create if missing
        $caldavBackend->createCalendarObject($calendarId, $uri, $icsContent);
      }
    } else {
      $caldavBackend->createCalendarObject($calendarId, $uri, $icsContent);
    }
  }
}
