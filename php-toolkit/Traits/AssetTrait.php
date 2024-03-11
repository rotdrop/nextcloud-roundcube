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

namespace OCA\RotDrop\Toolkit\Traits;

use OCP\IL10N;

use OCA\RotDrop\Toolkit\Exceptions;

/**
 * Return JavaScript- and CSS-assets names dealing with attached content
 * hashes. This needs some setup on the webpack build-system:
 * ```
 * const HtmlWebpackPlugin = require('html-webpack-plugin');
 * ...
 * webpackConfig.plugins = webpackConfig.plugins.concat([
 *   ...
 *   new HtmlWebpackPlugin({
 *     inject: false,
 *     filename: 'js/asset-meta.json',
 *     minify: false,
 *     templateContent(arg) {
 *       return JSON.stringify(arg.htmlWebpackPlugin.files, null, 2);
 *     },
 *   }),
 *   ...
 * ]);
 * ```.
 */
trait AssetTrait
{
  use LoggerTrait;

  /** @var IL10N */
  protected IL10N $l;

  /** @var array */
  protected $assets = [];

  /**
   * Read and parse the asset-meta information file. Its location is determined from $classDir.
   *
   * @param string $classDir The value of __DIR__ of the using class.
   *
   * @return void
   */
  protected function initializeAssets(string $classDir):void
  {
    $nestingLevel = count(explode('\\', __CLASS__)) - 2;
    $pathPrefix = str_repeat(Constants::PATH_SEPARATOR . '..', $nestingLevel);
    $assetMetaFile = $classDir . $pathPrefix . Constants::PATH_SEPARATOR .Constants::WEB_ASSET_META;

    $metaJson = file_get_contents($assetMetaFile);
    $assetMeta = json_decode($metaJson, true);
    foreach ([Constants::JS, Constants::CSS] as $type) {
      $this->assets[$type] = [];
      foreach (($assetMeta[$type] ?? []) as $assetFileName) {
        $assetFileName = basename($assetFileName, '.' . $type);
        if (preg_match('/^(.*)-([a-f0-9]+)$/', $assetFileName, $matches)) {
          ${Constants::ASSET} = $matches[0];
          $base = $matches[1];
          ${Constants::HASH} = $matches[2];
        } else {
          ${Constants::ASSET} = $assetFileName;
          $base = $assetFileName;
          ${Constants::HASH} = '';
        }
        $this->assets[$type][$base] = compact(Constants::ASSET, Constants::HASH);
      }
    }
  }

  /**
   * @param string $type js or css.
   *
   * @param string $baseName
   *
   * @return array
   */
  protected function getAsset(string $type, string $baseName):array
  {
    if (empty($this->assets[$type][$baseName])) {
      throw new Exceptions\EnduserNotificationException($this->l->t(
        'Installation problem; the required resource "%1$s" of type "%2$s" is not installed on the server, please contact the system administrator!', [
          $baseName,
          $type,
        ]));
    }
    return $this->assets[$type][$baseName];
  }

  /**
   * @param string $baseName
   *
   * @return array
   */
  protected function getJSAsset(string $baseName):array
  {
    return $this->getAsset(Constants::JS, $baseName);
  }

  /**
   * @param string $baseName
   *
   * @return array
   */
  protected function getCSSAsset(string $baseName):array
  {
    return $this->getAsset(Constants::CSS, $baseName);
  }
}
