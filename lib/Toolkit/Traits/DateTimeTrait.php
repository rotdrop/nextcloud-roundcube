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

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/** Support traits for date-time stuff */
trait DateTimeTrait
{
  /**
   * Ensure a valid date.
   *
   * @param null|DateTimeInterface $dateTime
   *
   * @return DateTimeInterface
   */
  public static function ensureDate(?DateTimeInterface $dateTime):DateTimeInterface
  {
    return $dateTime ?? new DateTimeImmutable('@1');
  }

  /**
   * Set
   *
   * @param string|int|\DateTimeInterface $dateTime
   *
   * @return null|\DateTimeImmutable
   */
  public static function convertToDateTime($dateTime):?DateTimeImmutable
  {
    if ($dateTime === null || $dateTime === '') {
      return null;
    } elseif (!($dateTime instanceof DateTimeInterface)) {
      $timeStamp = filter_var($dateTime, FILTER_VALIDATE_INT, [ 'min_range' => 0 ]);
      if ($timeStamp === false) {
        $timeStamp = filter_var($dateTime, FILTER_VALIDATE_FLOAT, [ 'min_range' => 0 ]);
      }
      if ($timeStamp !== false) {
        return (new DateTimeImmutable())->setTimestamp($timeStamp);
      } elseif (is_string($dateTime)) {
        return new DateTimeImmutable($dateTime);
      } else {
        throw new InvalidArgumentException('Cannot convert input to DateTime.');
      }
    } elseif ($dateTime instanceof DateTime) {
      return DateTimeImmutable::createFromMutable($dateTime);
    } elseif ($dateTime instanceof DateTimeImmutable) {
      return $dateTime;
    } else {
      throw new InvalidArgumentException('Unsupported date-time class: '.get_class($dateTime));
    }
    return null; // not reached
  }

  /**
   * Reinterprete the date portion of a \DateTimeInterface object at time 00:00:00 in another time-zone.
   *
   * @param DateTimeInterface $date
   *
   * @param DateTimeZone $timeZone
   *
   * @return DateTimeImmutable
   *
   * @todo Rework time-zone stuff.
   */
  public static function convertToTimezoneDate(DateTimeInterface $date, DateTimeZone $timeZone):DateTimeImmutable
  {
    return DateTimeImmutable::createFromFormat('Y-m-d|', $date->format('Y-m-d'), $timeZone);
  }
}
