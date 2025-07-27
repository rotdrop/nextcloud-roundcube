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
 * Trait implementing some braced placeholder substitutions.
 */
trait BracedPlaceholderTrait
{
  /**
   * Replace braced placeholders in a template string.
   *
   * The general syntax of a replacement is {[C[N]|]KEY[|M[D]][@FILTER]}
   * where anything in square brackets is optional.
   *
   * - 'C' is any character used for optional padding to the left.
   * - 'N' is the padding length. If ommitted, the value of 1 is assumed.
   * - 'KEY' is the replacement key
   * - 'FILTER' can be either
   *   - a single character which is used to replace occurences of '/' in the
   *     replacement for KEY
   *   - A=[B] in which case occurences of A are replaced by B. If B is omitted
   *     occurences of A are replaced by the empty string.
   *   - the hash-algo passed to the PHP hash($algo, $data) in which case the replacement value
   *     is the hash w.r.t. FILTER of the replacement data
   *
   * - 'M' is a number of "path" components to include from the right from the
   *   expansion of KEY with path-delimiter 'D' (default: "/"). "{KEY|2}" for
   *   the value "foo/bar/foobar" would result in "bar/foobar".
   *
   * @param string $template
   *
   * @param array $templateValues An array of replacement values:
   * ```
   * [ KEY1 => VALUE1, KEY2 => [ 'value' => VALUE2, 'padding' => NUMBER|OTHER_KEY ], ... ]
   * ```

   * where the second varian specifies a default padding either as number or
   * implicitly as reference to another key in which case the default padding
   * is the strlen() of the replacement value of the other key. If any value
   * is a \DateTimeInterface then it will be formatted by interpreting any
   * FILTER as format string with default 'c'.
   *
   * @param null|array $l10nTemplateKeys Optional translated keys as
   * ```
   * [
   *    TRANSLATED_KEY => ORIGINAL_KEY
   * ]
   * ```
   * The template may contain translated keys, but the $templateValues
   * replacement array must not contain translated keys.
   *
   * @return string
   *
   * @see \DateTimeInterface::format()
   */
  protected function replaceBracedPlaceholders(
    string $template,
    array $templateValues,
    ?array $l10nTemplateKeys = null,
  ):string {

    $keys = array_keys($templateValues);
    $keys = array_combine($keys, $keys);
    $l10nKeys = array_merge($keys, array_flip($l10nTemplateKeys) ?? $keys);

    return preg_replace_callback(
      '/{((.)([0-9]*)\|)?([^{}@|]+)(\|([0-9]+)([^{}])?)?(\@([^{}]+))?}/',
      function(array $matches) use ($keys, $l10nKeys, $templateValues) {
        // $this->logInfo('MATCHES ' . print_r($matches, true));
        $match = $matches[0];
        $padChar = $matches[2];
        $padding = $matches[3] ?: 0;
        $keyMatch = strtoupper($matches[4]);

        $tailCount = $matches[6] ?? null;
        $tailDelimiter = $matches[7] ?? Constants::PATH_SEPARATOR;

        $filter = $matches[9] ?? '';
        $key = $l10nKeys[$keyMatch] ?? ($keys[$keyMatch] ?? null);
        $value = !empty($key) ? $templateValues[$key] : $match;
        if (is_array($value)) {
          $padding = $padding ?: $value['padding'];
          if (!is_numeric($padding)) {
            $padding = $l10nKeys[$padding] ?? ($keys[$padding] ?? null);
            $padding = strlen($templateValues[$padding] ?? '.');
          }
          $value = $value['value'];
        }
        if (strlen($padChar) == 1) {
          $value = str_pad($value, $padding ?: 1, $padChar, STR_PAD_LEFT);
        }
        if ($value instanceof DateTimeInterface) {
          // interprete the filter as format for DateTimeInterface::format()
          $value = $value->format(empty($filter) ? 'c' : $filter);
        } else {
          if (!empty($tailCount) && $tailCount !== 0) {
            $components = explode($tailDelimiter, $value);
            array_splice($components, 0, -$tailCount);
            $value = implode($tailDelimiter, $components);
          }
          if (!empty($filter)) {
            if (strlen($filter) == 1) {
              $filter = Constants::PATH_SEPARATOR . '=' . $filter;
            }
            if (strlen($filter) >= 2 && $filter[1] == '=') {
              $search = $filter[0];
              $replace = $filter[2] ?? '';
              $value = str_replace($search, $replace, $value);
            } else {
              $value = strtoupper(hash(strtolower($filter), $value)); // result in a hex string
            }
          }
        }
        return $value;
      },
      $template,
    );
  }

  /**
   * Translate the braced placeholders contained in the given $template to the
   * array values supplied by the $l10nTemplateKeys, that is, the keys of
   * $l10nTemplateKeys matching the braced placeholders contained in $template
   * will be replaced by their respective array value.
   *
   * @param string $template
   *
   * @param null|array $l10nTemplateKeys If null, no replacement is performed
   * and the $template argument is return unchanged.
   *
   * @return string
   */
  protected function translateBracedTemplate(string $template, ?array $l10nTemplateKeys):string
  {
    $patterns = array_map(fn($key) => '/{(.*)' . $key . '(.*)}/', array_keys($l10nTemplateKeys));
    $replacements = array_map(fn($value) => '{${1}' . $value . '${2}}', array_values($l10nTemplateKeys));
    return preg_replace($patterns, $replacements, $template);
  }

  /**
   * Translate the braced placeholders contained in the given $template to the
   * array keys supplied by the $l10nTemplateKeys, that is, the array values
   * of $l10nTemplateKeys matching the braced placeholders contained in
   * $template will be replaced by their respective array key.
   *
   * @param string $template
   *
   * @param null|array $l10nTemplateKeys If null, no replacement is performed
   * and the $template argument is return unchanged.
   *
   * @return string
   */
  protected function untranslateBracedTemplate(string $template, ?array $l10nTemplateKeys):string
  {
    return $this->translateBracedTemplate($template, array_flip($l10nTemplateKeys));
  }
}
