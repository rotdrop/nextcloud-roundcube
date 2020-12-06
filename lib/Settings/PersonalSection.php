<?php
/*
 * nextCloud - RoundCube mail plugin
 *
 * @author Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\RoundCube\Settings;

use OCP\Settings\IIconSection;
use OCP\IURLGenerator;
use OCP\IL10N;

class PersonalSection implements IIconSection
{
  /** @var string */
  private $appName;

  /** @var \OCP\IURLGenerator */
  private $ulrGenerator;

  /** @var \OCP\IL10N */
  private $l;

  public function __construct(
    string $appName
    , IURLGenerator $urlGenerator
    , IL10N $l10n
  ) {
    $this->appName = $appName;
    $this->urlGenerator = $urlGenerator;
    $this->l = $l10n;
  }

  /**
   * returns the ID of the section. It is supposed to be a lower case string
   *
   * @returns string
   */
  public function getID() {
    return $this->appName;
  }

  /**
   * returns the translated name as it should be displayed, e.g. 'LDAP / AD
   * integration'. Use the L10N service to translate it.
   *
   * @return string
   */
  public function getName() {
    // @@TODO make this configurable
    return $this->l->t("RoundCube Integration");
  }

  /**
   * @return int whether the form should be rather on the top or bottom of
   * the settings navigation. The sections are arranged in ascending order of
   * the priority values. It is required to return a value between 0 and 99.
   */
  public function getPriority() {
    return 50;
  }

  public function getIcon() {
    // @@TODO make it configurable
    return $this->urlGenerator->imagePath($this->appName, 'app.svg');
  }

}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
