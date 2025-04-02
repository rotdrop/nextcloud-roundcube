<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\AppFramework\IAppContainer;

use OCA\RotDrop\Toolkit\Listener\BeforeMessageLoggedEventListener;

/**
 * Utitily trait to simplifiy logging somewhat.
 */
trait LoggerTrait
{
  /** @var LoggerInterface */
  protected LoggerInterface $logger;

  /** @var IAppContainer */
  protected IAppContainer $appContainer;

  /**
   * Return the stored logger class
   *
   * @return LoggerInterface
   */
  public function logger():LoggerInterface
  {
    return $this->logger;
  }

  /**
   * Return the stored appConmtainer instance.
   *
   * @return IAppContainer
   */
  protected function appContainer():IAppContainer
  {
    return $this->appContainer;
  }

  /**
   * Map PSR log-levels to ILogger log-levels as the PsrLoggerAdapter only
   * understands those.
   *
   * @param mixed $level
   *
   * @return mixed
   */
  protected function mapLogLevels(mixed $level)
  {
    if (is_int($level) || is_numeric($level)) {
      return $level;
    }
    switch ($level) {
      case LogLevel::EMERGENCY:
        return ILogger::FATAL;
      case LogLevel::ALERT:
        return ILogger::ERROR;
      case LogLevel::CRITICAL:
        return ILogger::ERROR;
      case LogLevel::ERROR:
        return ILogger::ERROR;
      case LogLevel::WARNING:
        return ILogger::WARN;
      case LogLevel::NOTICE:
        return ILogger::INFO;
      case LogLevel::INFO:
        return ILogger::INFO;
      case LogLevel::DEBUG:
        return ILogger::DEBUG;
      default:
        return ILogger::ERROR;
    }
  }

  /**
   * Log the given message at the specified level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   * @param int $shift
   * @param bool $showTrace
   * @param bool $returnLogEntry
   *
   * @return null|array
   */
  public function log(
    mixed $level,
    string $message,
    array $context = [],
    int $shift = 0,
    bool $showTrace = false,
    bool $returnLogEntry = false,
  ):?array {
    $level = $this->mapLogLevels($level);

    if ($shift < 0) {
      $prefix = '';
    } else {
      $trace = debug_backtrace();
      $prefix = '';
      $shift = min($shift, count($trace));

      do {
        $caller = $trace[$shift];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 'unknown';
        $caller = $trace[$shift + 1] ?? 'unknown';
        $class = $caller['class'] ?? 'unknown';
        $method = $caller['function'];

        $prefix .= $file . ':' . $line . ': ' . $class . '::' . $method . '(): ';
      } while ($showTrace && --$shift > 0);
    }

    $logEntry = null;
    if ($returnLogEntry) {
      /** @var IEventDispatcher $eventDispatcher */
      $eventDispatcher = $this->appContainer()->get(IEventDispatcher::class);
      /** @var BeforeMessageLoggedEventListener $listener */
      $listener = $this->appContainer()->get(BeforeMessageLoggedEventListener::class);
      $appName = $this->appContainer()->get('AppName');
      $eventDispatcher->addListener(
        BeforeMessageLoggedEventListener::EVENT,
        [$listener, 'handle'],
      );
      $context = [
        ...$context,
        $appName => [
          'callback' => function(array $logData) use (&$logEntry) {
            $logEntry = $logData;
          },
        ],
      ];
    }
    $this->logger()->log($level, $prefix . $message, $context);

    return $logEntry;
  }

  /**
   * @param Throwable $exception
   * @param string $message
   * @param int $shift
   * @param mixed $level
   * @param array $context
   * @param bool $returnLogEntry
   *
   * @return null|array
   */
  public function logException(
    Throwable $exception,
    string $message = null,
    int $shift = 0,
    mixed $level = LogLevel::ERROR,
    array $context = [],
    bool $returnLogEntry = false,
  ):?array {
    return $this->log(
      $level,
      $message ?? 'Caught an Exception',
      context: [ 'exception' => $exception, ...$context ],
      shift: $shift + 1,
      showTrace: false, // does not make sense
      returnLogEntry: $returnLogEntry,
    );
  }

  /**
   * Log an error.
   *
   * @param string $message
   * @param array $context
   * @param int $shift
   * @param bool $showTrace
   * @param bool $returnLogEntry
   *
   * @return null|array
   */
  public function logError(
    string $message,
    array $context = [],
    int $shift = 0,
    bool $showTrace = false,
    bool $returnLogEntry = false,
  ):?array {
    return $this->log(LogLevel::ERROR, $message, $context, $shift + 1, $showTrace, $returnLogEntry);
  }

  /**
   * Log a debug message.
   *
   * @param string $message
   * @param array $context
   * @param int $shift
   * @param bool $showTrace
   * @param bool $returnLogEntry
   *
   * @return null|array
   */
  public function logDebug(
    string $message,
    array $context = [],
    int $shift = 0,
    bool $showTrace = false,
    bool $returnLogEntry = false,
  ):?array
  {
    return $this->log(LogLevel::DEBUG, $message, $context, $shift + 1, $showTrace, $returnLogEntry);
  }

  /**
   * Log an informational message.
   *
   * @param string $message
   * @param array $context
   * @param int $shift
   * @param bool $showTrace
   * @param bool $returnLogEntry
   *
   * @return null|array
   */
  public function logInfo(
    string $message,
    array $context = [],
    int $shift = 0,
    bool $showTrace = false,
    bool $returnLogEntry = false,
  ):?array {
    return $this->log(LogLevel::INFO, $message, $context, $shift + 1, $showTrace, $returnLogEntry);
  }

  /**
   * Log a warning message.
   *
   * @param string $message
   * @param array $context
   * @param int $shift
   * @param bool $showTrace
   * @param bool $returnLogEntry
   *
   * @return null|array
   */
  public function logWarn(
    string $message,
    array $context = [],
    int $shift = 0,
    bool $showTrace = false,
    bool $returnLogEntry = false,
  ):?array {
    return $this->log(LogLevel::WARNING, $message, $context, $shift + 1, $showTrace, $returnLogEntry);
  }

  /**
   * Log a fatal error message.
   *
   * @param string $message
   * @param array $context
   * @param int $shift
   * @param bool $showTrace
   * @param bool $returnLogEntry
   *
   * @return null|array
   */
  public function logFatal(
    string $message,
    array $context = [],
    int $shift = 0,
    bool $showTrace = false,
    bool $returnLogEntry = false,
  ):?array {
    return $this->log(LogLevel::EMERGENCY, $message, $context, $shift + 1, $showTrace, $returnLogEntry);
  }
}
