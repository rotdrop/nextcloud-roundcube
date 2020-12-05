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

style($appName, 'admin-settings');
script($appName, 'admin-settings');

?>
<div class="section" id="roundcube">
  <h2 class="app-name"><?php p($l->t('Embedded RoundCube')); ?></h2>
  <form id="<?php echo Constants::APP_PREFIX; ?>settings" action="#" method="post">
    <input type="hidden" name="appname" value="<?php p($appName); ?>"/>
    <input type="hidden" name="submit" value="1"/>

    <div class="rcSetting">
      <h3><?php p($l->t('Default RC installation path')); ?></h3>
      <label>
        <input type="text"
               id="externalLocation"
               class="externalLocation"
               name="externalLocation"
	       value="<?php p($externalLocation); ?>"
	       maxlength="128"
        />
      </label>
      <p><?php p($l->t('Default path relative to ownCloud server (%s).', $ocServer)); ?></p>
    </div>
    <div class="rcSetting">
      <h3><?php p($l->t('Email Address Selection')); ?></h3>
      <input type="radio"
             name="emailAddressChoice"
             class="radio emailAdressChoice"
             id="userIdEmail"
             value="userIdEmail"
	     <?php if ($userIdEmail) { echo 'checked="checked"'; } ?>
             title="<?php p($l->t('Use the cloud user-id as (user part of) the email address')); ?>"
      />
      <label for="userIdEmail">
        <?php p($l->t('Cloud login-id')); ?>
      </label>
      <span class="emailDefaultDomain<?php if (empty($userIdEmail)) { p(' disabled'); } ?>">
        <span>&nbsp;--&nbsp;</span>
        <span class="typewriter">USER_ID@</span>
        <input type="text"
               name="emailDefaultDomain"
               class="emailDefaultDomain"
               placeholder="<?php p($l->t('Email Domain')); ?>"
               value="<?php p($emailDefaultDomain); ?>"
               title="<?php p($l->t('Specify the domain-part for the case that the user-id is not an email-address.')); ?>"
	       <?php if (empty($userIdEmail)) { echo 'disabled="disabled"'; } ?>
	       maxlength="128"
        />
      </span>
      <br/>
      <input type="radio"
             name="emailAddressChoice"
             class="radio emailAdressChoice"
             id="userPreferencesEmail"
             value="userPreferencesEmail"
	     <?php if ($userPreferencesEmail) { echo 'checked="checked"'; } ?>
             title="<?php p($l->t('Use the email-address from the user\'s preferences.')); ?>"
      />
      <label for="userPreferencesEmail">
        <?php p($l->t('User\'s Preferences')); ?>
      </label>
      <br/>
      <input type="radio"
             name="emailAddressChoice"
             class="radio emailAdressChoice"
             id="userChosenEmail"
             value="userChosenEmail"
	     <?php if ($userChosenEmail) { echo 'checked="checked"'; } ?>
             title="<?php p($l->t('Let the user specify an arbitrary address.')); ?>"
      />
      <label for="userChosenEmail">
        <?php p($l->t('User\'s Choice')); ?>
      </label>
    </div>
    <div class="rcSetting">
      <h3><?php p($l->t('Advanced settings')); ?></h3>
      <label>
	<input type="number"
               min="0"
               name="authenticationRefreshInterval"
               id="authenticationRefreshInterval"
               class="authenticationRefreshInterval"
               value="<?php echo $authenticationRefreshInterval; ?>"
               placeholder="<?php echo $l->t('Refresh Time [s]'); ?>"
               title="<?php echo $l->t('Please enter the desired session-refresh interval here. The interval is measured in seconds and should be somewhat smaller than the configured session life-time for the roundcube instance in use.'); ?>"
        />
	<?php p($l->t('Session refresh rate [s].')); ?>
      </label>
      <br/>
      <input type="checkbox"
             class="checkbox"
             name="showTopLine"
             id="showTopLine"
	     <?php if ($showTopLine) { echo 'checked="checked"'; } ?>
      />
      <label for="showTopLine">
	<?php p($l->t('Show RoundCube top information bar (shows logout button).')); ?>
      </label>
      <br/>
      <input type="checkbox"
             name="enableSSLVerify"
             id="enableSSLVerify"
             class="checkbox"
	     <?php if ($enableSSLVerify) { echo 'checked="checked"'; } ?>
      />
      <label title="<?php p($l->t('Disable when debugging with self-signed certificates.')); ?>"
             for="enableSSLVerify">
	<?php p($l->t('Enable SSL verification.')); ?>
      </label>
    </div>

    <input id="rcAdminSubmit" type="submit" value="<?php p($l->t('Save')); ?>"/>
    <span id="rc_save_status" class="msg status hidden"/><?php p($l->t('Saving...')); ?></span>
    <span id="rc_save_error" class="msg error hidden"/></span>
    <span id="rc_save_success" class="msg success hidden"/></span>
  </form>
</div>
