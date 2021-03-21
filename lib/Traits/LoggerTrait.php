<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RoundCube\Traits;

use OCP\ILogger;
use OCP\IL10N;

trait LoggerTrait
{
  /** @var ILogger */
  protected $logger;

  /** @var IL10N */
  protected $l;

  public function log(int $level, string $message, array $context = [], $shift = 0) {
    $trace = debug_backtrace();
    $caller = $trace[$shift];
    $file = $caller['file'];
    $line = $caller['line'];
    $caller = $trace[$shift+1];
    $class = $caller['class'];
    $method = $caller['function'];

    $prefix = $file.':'.$line.': '.$class.'::'.$method.': ';
    return $this->logger->log($level, $prefix.$message, $context);
  }

  public function logException($exception, $message = null, $shift = 0) {
    $trace = debug_backtrace();
    $caller = $trace[$shift];
    $file = $caller['file'];
    $line = $caller['line'];
    $caller = $trace[$shift+1];
    $class = $caller['class'];
    $method = $caller['function'];

    $prefix = $file.':'.$line.': '.$class.'::'.$method.': ';

    empty($message) && ($message = "Caught an Exception");
    $this->logger->logException($exception, [ 'message' => $prefix.$message ]);
  }

  public function logError(string $message, array $context = [], $shift = 1) {
    return $this->log(ILogger::ERROR, $message, $context, $shift);
  }

  public function logDebug(string $message, array $context = [], $shift = 1) {
    return $this->log(ILogger::DEBUG, $message, $context, $shift);
  }

  public function logInfo(string $message, array $context = [], $shift = 1) {
    return $this->log(ILogger::INFO, $message, $context, $shift);
  }

  public function logWarn(string $message, array $context = [], $shift = 1) {
    return $this->log(ILogger::WARN, $message, $context, $shift);
  }

  public function logFatal(string $message, array $context = [], $shift = 1) {
    return $this->log(ILogger::FATAL, $message, $context, $shift);
  }

}
