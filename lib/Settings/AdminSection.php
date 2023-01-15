<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2023 Claus-Justus Heine
 * @license AGPL-3.0-or-later
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

namespace OCA\RoundCube\Settings;

use OCP\Settings\IIconSection;
use OCP\IURLGenerator;
use OCP\IL10N;

/**
 * Admin settings section in left side-bar.
 */
class AdminSection implements IIconSection
{
  /** @var string */
  private $appName;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  /** @var \OCP\IL10N */
  private $l;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IURLGenerator $urlGenerator,
    IL10N $l10n,
  ) {
    $this->appName = $appName;
    $this->urlGenerator = $urlGenerator;
    $this->l = $l10n;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** {@inheritdoc} */
  public function getID()
  {
    return $this->appName;
  }

  /** {@inheritdoc} */
  public function getName()
  {
    // @@TODO make this configurable
    return $this->l->t("RoundCube Integration");
  }

  /** {@inheritdoc} */
  public function getPriority()
  {
    return 50;
  }

  /** {@inheritdoc} */
  public function getIcon()
  {
    // @@TODO make it configurable
    return $this->urlGenerator->imagePath($this->appName, 'app.svg');
  }
}
