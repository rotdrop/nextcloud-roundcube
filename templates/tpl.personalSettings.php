<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Martin Reinhardt and David Jaedke
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @copyright 2012 Martin Reinhardt contact@martinreinhardt-online.de
 * @author Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
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
 *
 */

namespace OCA\RoundCube;

use OCA\RoundCube\Service\Constants;

style($appName, 'settings');
script($appName, 'personal-settings');

?>
<div class="section" id="roundcube">
  <h2 class="app-name"><?php p($l->t('Embedded RoundCube')); ?></h2>
  <form id="<?php echo Constants::APP_PREFIX; ?>settings" action="#" method="post">
    <input type="hidden" name="appname" value="<?php p($appName); ?>"/>
    <input type="hidden" name="submit" value="1"/>

    <!-- @TODO disable depending on admin settings -->
    <div class="rcSetting">
      <input type="text"
             id="emailAddress"
             class="emailAddress"
             name="emailAddress"
	     value="<?php p($emailAddress); ?>"
      />
      <label>
        <?php p($l->t('Email-User for Roundcube')); ?>
      </label>
    </div>
    <div class="rcSetting">
      <!-- @TODO show/hide password -->
      <input type="password"
             id="emailPassword"
             class="emailPassword"
             name="emailPassword"
	     value="<?php p($emailPassword); ?>"
      />
      <label>
        <?php p($l->t('Email-Password for Roundcube')); ?>
      </label>
    </div>

    <input id="rcSettingsSubmit" type="submit" value="<?php p($l->t('Save')); ?>"/>
    <span id="rc_save_status" class="msg status hidden"/><?php p($l->t('Saving...')); ?></span>
    <span id="rc_save_error" class="msg error hidden"/></span>
    <span id="rc_save_success" class="msg success hidden"/></span>
  </form>
</div>
