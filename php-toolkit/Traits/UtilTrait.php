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

use NumberFormatter;
use DateTimeInterface;

use OCP\IL10N;

/**
 * Trait for some general nice-to-have functions.
 */
trait UtilTrait
{
  use BracedPlaceholderTrait;

  /** @var IL10N */
  protected IL10N $l;

  /**
   * Take any dashed or "underscored" lower-case string and convert to
   * camel-case.
   *
   * @param string $string the string to convert.
   *
   * @param bool $capitalizeFirstCharacter self explaining.
   *
   * @param string $dashes Characters to replace.
   *
   * @return string
   */
  protected static function dashesToCamelCase(string $string, bool $capitalizeFirstCharacter = false, string $dashes = '_-'):string
  {
    $str = str_replace(str_split($dashes), '', ucwords($string, $dashes));

    if (!$capitalizeFirstCharacter) {
      $str[0] = strtolower($str[0]);
    }

    return $str;
  }

  /**
   * Take an camel-case string and convert to lower-case with dashes
   * or underscores between the words. First letter may or may not
   * be upper case.
   *
   * @param string $string String to work on.
   *
   * @param string $separator Separator to use, defaults to '-'.
   *
   * @return string
   */
  protected static function camelCaseToDashes(string $string, string $separator = '-'):string
  {
    return strtolower(preg_replace('/([A-Z])/', $separator.'$1', lcfirst($string)));
  }

  /**
   * Return the locale as string, e.g. de_DE.UTF-8.
   *
   * @return string
   */
  protected function getLocale():string
  {
    $locale = $this->l->getLocaleCode();
    $primary = locale_get_primary_language($locale);
    if ($primary == $locale) {
      $locale = $locale.'_'.strtoupper($locale);
    }
    if (strpos($locale, '.') === false) {
      $locale .= '.UTF-8';
    }
    return $locale;
  }

  /**
   * Transliterate the given string to the given or default locale.
   *
   * @param string $string
   *
   * @param string $locale
   *
   * @return string
   */
  protected function transliterate(string $string, ?string $locale = null):string
  {
    $oldlocale = setlocale(LC_CTYPE, '0');
    empty($locale) && $locale = $this->getLocale();
    setlocale(LC_CTYPE, $locale);
    $result = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    setlocale(LC_CTYPE, $oldlocale);
    return $result;
  }

  /**
   * Try to parse a floating point value.
   *
   * @param string $value Input value. Maybe a percentage.
   *
   * @param null|string $locale
   *
   * @return bool|float
   */
  public function floatValue(string $value, ?string $locale = null)
  {
    $amount = preg_replace('/\s+/u', '', $value);
    empty($locale) && $locale = $this->getLocale();
    $locales = [ $locale, 'en_US_POSIX' ];
    $parsed = false;
    foreach ($locales as $locale) {
      $fmt = new NumberFormatter($locale, \NumberFormatter::DECIMAL);

      $decimalSeparator = $fmt->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
      $groupingSeparator = $fmt->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

      $decPos = strpos($amount, $decimalSeparator);
      $grpPos = strpos($amount, $groupingSeparator);

      if ($grpPos !== false && $decPos === false) {
        // unlikely: 1,000, we assume 1,000.00 would be used
        continue;
      } elseif ($decPos < $grpPos) {
        // unlikely: 1.000,00 in en_US
        continue;
      }

      $parsed = $fmt->parse($amount);
      if ($parsed !== false) {
        $percent = $fmt->getSymbol(\NumberFormatter::PERCENT_SYMBOL);
        if (preg_match('/'.$percent.'/u', $amount)) {
            $parsed /= 100.0;
        }
        break;
      }
    }
    return $parsed !== false ? (float)$parsed : $parsed;
  }

  /**
   * Format a float value for display in the given or the default locale.
   *
   * @param mixed $value Value to format.
   *
   * @param int $decimals Number of decimal places.
   *
   * @param null|string $locale Locale to use, use user-locale if null.
   *
   * @return string
   */
  public function formatFloatValue(mixed $value, int $decimals = 4, ?string $locale = null):string
  {
    empty($locale) && $locale = $this->getLocale();
    $fmt = new NumberFormatter($locale, NumberFormatter::DECIMAL);
    $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
    $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
    $result = $fmt->format((float)$value);
    return $result;
  }

  /**
   * Parse a storage user input value and return its value in bytes.
   *
   * @param null|string $value Input value.
   *
   * @param null|string $locale Locale or null for the user's default locale.
   *
   * @return null|int|float
   *
   * - if the passed value is null or the empty string then the function
   * returns null.
   * - if otherwise an error occurs during parsing, null is returned
   * - otherwise the storage value in bytes is returned
   */
  public function storageValue(?string $value, ?string $locale = null)
  {
    if ($value === null || $value === '') {
      return null;
    }
    if ($value === '0') {
      return (int)0;
    }
    $factor = [
      'b' => 1,
      'kb' => 1000,
      'kib' => (1 << 10),
      'mb' => (1000 * 1000),
      'mib' => (1 << 20),
      'gb' => (1000 * 1000 * 1000),
      'gib' => (1 << 30),
      'tb' => (1000 * 1000 * 1000 * 1000),
      'tib' => (1 << 40),
      'pb' => (1000 * 1000 * 1000 * 1000 * 1000),
      'pib' => (1 << 50),
      'eb' => (1000 * 1000 * 1000 * 1000 * 1000 * 1000),
      'eib' => (1 << 60),
      'zb' => (1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000),
      'zib' => (1 << 70),
      'yb' => (1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000),
      'yib' => (1 << 80),
    ];
    $value = preg_replace('/\s+/u', '', $value);
    $value = strtolower(
      str_ireplace(
        [ 'bytes', 'kilo', 'kibi', 'mega', 'mebi', 'giga', 'gibi', 'tera', 'tibi', 'peta', 'pebi', 'exa', 'exbi', 'zetta', 'zebi', 'yotta', 'yobi', ],
        [ 'b',     'k',    'ki',   'm',    'mi',   'g',    'gi',   't',    'ti',   'p',    'pi',   'e',   'ei',   'z',     'zi',   'y',     'yi',   ],
        $value));

    if (preg_match('/([-0-9,.]+)([kmgtpezy]?i?b?)?$/', $value, $matches)) {
      $this->logInfo('MATCHES ' . print_r($matches, true));
      $value = $this->floatValue($matches[1], $locale);
      if (empty($value)) {
        return null;
      }
      if (!empty($matches[2])) {
        if (empty($factor[$matches[2]])) {
          return null;
        }
        $value *= $factor[$matches[2]];
        if ($value <= PHP_INT_MAX) {
          $value = (int)$value;
        }
      }
      return $value;
    } else {
      return null;
    }
  }

  /**
   * @param int $bytes The size in bytes to format.
   *
   * @param string $format If \true then binary prefixes are used (kib, mib, etc.).
   *
   * @param int $digits Number of fractinal digits.
   *
   * @param null|string $locale Locale to use to format fractional numbers.
   *
   * @return null|string The human readable storage size.
   *
   * @see storageValue()
   */
  protected function formatStorageValue(int $bytes, string $format = 'binary', int $digits = 2, ?string $locale = null):?string
  {
    $units = [
      'decimal' => [
        '', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y',
      ],
      'longDecimal' => [
        'bytes', 'kilo', 'mega', 'giga', 'tera', 'peta', 'exa', 'zetta', 'yotta',
      ],
      'binary' => [
        '', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi',
      ],
      'longBinary' => [
        '', 'kibi', 'mebi', 'gibi', 'tebi', 'pebi', 'exbi', 'zebi', 'yobi'
      ],
    ];
    $bytesUnit = [
      'decimal' => 'B',
      'longDecimal' => ' ' . $this->l->t('bytes'),
      'binary' => 'B',
      'longBinary' => ' ' . $this->l->t('bytes'),
    ];
    $radix = [
      'decimal' => 1000.0,
      'longDecimal' => 1000.0,
      'binary' => 1024.0,
      'longBinary' => 1024.0,
    ];

    $units = $units[$format] ?? null;
    $bytesUnit = $bytesUnit[$format] ?? null;
    $radix = $radix[$format] ?? null;

    if ($units === null || $bytesUnit === null || $radix === null) {
      // maybe throw InvalidArgumentException
      return null;
    }

    empty($locale) && $locale = $this->getLocale();

    $floatVal = $bytes;
    $exponent = 0;
    $exponentLimit = count($units) - 1;
    while ($floatVal > $radix && $exponent < $exponentLimit) {
      $floatVal /= $radix;
      ++$exponent;
    }

    $fmt = new NumberFormatter($locale, \NumberFormatter::DECIMAL);
    $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
    $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $digits);
    $stringVal = $fmt->format($floatVal);

    return $stringVal . ' ' . $units[$exponent] . $bytesUnit;
  }

  /**
   * Unset all array elements with value $value.
   *
   * @param array $hayStack The array to modify.
   *
   * @param mixed $value The value to remove.
   *
   * @return int The number of array slots that have been unset. As
   * the values need not be unique this can be any non-negative
   * integer.
   */
  protected static function unsetValue(array &$hayStack, mixed $value):int
  {
    $numUnset = 0;
    while (($key = array_search($value, $hayStack)) !== false) {
      unset($hayStack[$key]);
      ++$numUnset;
    }
    return $numUnset;
  }

  /**
   * @param array $paths Array of path names.
   *
   * @param bool $leadingSlash If \false the $paths array elements do not
   * start with a slash.
   *
   * @return string The common directory prefix. An empty string if the
   * path-names do not share any common prefix.
   */
  protected function getCommonPath(array $paths, bool $leadingSlash = true):string
  {
    // $this->logInfo('PATHS ' . print_r($paths, true));
    $lastOffset = (int)$leadingSlash;
    $common = $leadingSlash ? '/' : '';
    while (($index = strpos($paths[0], '/', $lastOffset)) !== false) {
      $dirLen = $index - $lastOffset + 1; // include /
      $dir = substr($paths[0], $lastOffset, $dirLen);
      foreach ($paths as $path) {
        if (substr($path, $lastOffset, $dirLen) != $dir) {
          return $leadingSlash ? substr($common, 0, -1) : $common;
        }
      }
      $common .= $dir;
      $lastOffset = $index + 1;
    }
    return $leadingSlash ? substr($common, 0, -1) : $common;
  }

  /**
   * @param string|mixed $classOrClassName
   *
   * @return string The "basename" of the given class or class-name.
   */
  protected function getClassBaseName($classOrClassName):string
  {
    $className = is_string($classOrClassName) ? $classOrClassName : get_class($classOrClassName);
    return substr(strrchr($className, '\\'), 1);
  }

  /**
   * @param string $rgbaString RGBA color value in the format "#RRGGBBAA". The
   * "A" (opacity) value is optional.
   *
   * @return array Decoded RGBA color array.
   */
  protected function rgbaStringToArray(string $rgbaString):array
  {
    $inputLength = strlen($rgbaString);
    if ($inputLength != 7 && $inputLength != 9 || $rgbaString[0] !== '#') {
      throw new InvalidArgumentException(
        $this->l->t('The supplied color-string "%s" seems to be invalid.', $rgbaString));
    }
    $rgbaArray = [
      hexdec(substr($rgbaString, 1, 2)),
      hexdec(substr($rgbaString, 3, 2)),
      hexdec(substr($rgbaString, 5, 2)),
    ];
    if (strlen($rgbaString) === 9) {
      $rgbaArray[3] = hexdec(substr($rgbaString, 7, 2));
    }
    foreach ($rgbaArray as $colorValue) {
      if ($colorValue === false) {
        throw new InvalidArgumentException(
          $this->l->t('The supplied color-string "%s" seems to be invalid.', $rgbaString));
      }
    }

    return $rgbaArray;
  }

  /**
   * @param array $rgbaArray RGB(A) data with values between 0 and 255 (8-bit
   * color data). The "A" (opacity) value is optional. The input array is a
   * flat number array with 3 or 4 components.
   *
   * @return string Encoded RGB(A) color string in the format "#RRGGBBAA". The
   * "AA" is only there if the input array has 4 components.
   */
  protected function rgbaArrayToString(array $rgbaArray):string
  {
    $rgbaString = '#';
    foreach ($rgbaArray as $colorValue) {
      if (!is_numeric($colorValue) || (int)$colorValue != $colorValue || $colorValue < 0 || $colorValue > 255) {
        throw new InvalidArgumentException(
          $this->l->t('The input color values are invalid.'));
      }
      $rgbaString .= sprintf('%02x', $colorValue);
    }
    return strtolower($rgbaString);
  }
}
