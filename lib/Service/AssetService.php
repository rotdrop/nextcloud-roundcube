<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2021, 2022, 2023 Claus-Justus Heine
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

namespace OCA\RoundCube\Service;

use OCP\IL10N;
use Psr\Log\LoggerInterface;

use OCA\CAFEVDB\Exceptions;
use OCA\CAFEVDB\Constants;

/**
 * Return JavaScript- and CSS-assets names dealing with the attached content
 * hashes
 */
class AssetService
{
  use \OCA\RotDrop\Toolkit\Traits\AssetTrait {
    getAsset as public;
    getJSAsset as public;
    getCSSAsset as public;
  }

  const JS = Constants::JS;
  const CSS = Constants::CSS;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(IL10N $l10n, LoggerInterface $logger)
  {
    $this->logger = $logger;
    $this->l = $l10n;
    $this->initializeAssets(__DIR__);
  }
  // phpcs:enable
}
