<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author    Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2026 Claus-Justus Heine
 * @license   AGPL-3.0-or-later
 *
 * Nextcloud RoundCube App is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Nextcloud RoundCube App is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Nextcloud RoundCube App.  If not, see <http://www.gnu.org/licenses/>.
 */

use Isolated\Symfony\Component\Finder\Finder;

$infoXml = file_get_contents(__DIR__ . '/appinfo/info.xml');
$matches = null;
preg_match('|<namespace>([^<]+)</namespace>|', $infoXml, $matches);
$nameSpace = $matches[1];
preg_match('|<scopednamespace>([^<]+)</scopednamespace>|', $infoXml, $matches);
$scopedNameSpace = $matches[1];

$prefix = 'OCA\\' . $nameSpace . '\\' . $scopedNameSpace;

return [
  'prefix' => $prefix,
  'exclude-classes' => [
    'OC',
  ],
  'expose-global-classes' => false,
  'exclude-namespaces' => [
    '/^(OC|OCP|NCU)(\\\\|$)/',
    '/^OCA\\\\(GroupFolders|RotDrop\\\\DevScripts)(\\\\|$)/',
    '/^PHPUnit/',
    '/^Psr(\\\\|$)/',
    '/^Spatie\\\\TypeScriptTransformer(\\\\|$)/',
  ],
  'finders' => [
    Finder::create()
      ->files()
      ->ignoreVCS(true)
      ->followLinks(true)
      ->in('vendor-scoped'),
  ],
  'patchers' => [
    function(string $filePath, string $prefix, string $content): string {
      $content = str_replace("trigger_deprecation('", '\\' . $prefix . "\\trigger_deprecation('", $content);
      return $content;
    },
  ],
];
