<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Martin Reinhardt and David Jaedke
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2012 Martin Reinhardt contact@martinreinhardt-online.de
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

namespace OCA\RoundCube;

style($appName, 'admin-settings');
script($appName, 'admin-settings');

?>
<div class="section" id="roundcube">
  <h2 class="app-name"><?php p($l->t('Embedded RoundCube')); ?></h2>
  <form id="<?php p($webPrefix); ?>settings" action="#" method="post">
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
      <?php $title = $l->t('Use the cloud user-id as (user part of) the email address'); ?>
      <input type="radio"
             name="emailAddressChoice"
             class="radio emailAdressChoice"
             id="userIdEmail"
             value="userIdEmail"
	     <?php if ($emailAddressChoice == 'userIdEmail') { echo 'checked="checked"'; } ?>
             title="<?php p($title); ?>"
      />
      <label for="userIdEmail" title="<?php p($title); ?>">
        <?php p($l->t('Cloud login-id')); ?>
      </label>
      <span class="emailDefaultDomain<?php if ($emailAddressChoice != 'userIdEmail') { p(' disabled'); } ?>">
        <span>&nbsp;--&nbsp;</span>
        <span class="typewriter">USER_ID@</span>
        <input type="text"
               id="emailDefaultDomain"
               name="emailDefaultDomain"
               class="emailDefaultDomain"
               placeholder="<?php p($l->t('Email Domain')); ?>"
               value="<?php p($emailDefaultDomain); ?>"
               title="<?php p($l->t('Specify the domain-part for the case that the user-id is not an email-address.')); ?>"
	       <?php if ($emailAddressChoice != 'userIdEmail') { echo 'disabled="disabled"'; } ?>
	       maxlength="128"
        />
      </span>
      <br/>
      <?php $title = $l->t('Use the email-address from the user\'s preferences.'); ?>
      <input type="radio"
             name="emailAddressChoice"
             class="radio emailAdressChoice"
             id="userPreferencesEmail"
             value="userPreferencesEmail"
	     <?php if ($emailAddressChoice == 'userPreferencesEmail') { echo 'checked="checked"'; } ?>
             title="<?php p($title); ?>"
      />
      <label for="userPreferencesEmail" title="<?php p($title); ?>">
        <?php p($l->t('User\'s Preferences')); ?>
      </label>
      <br/>
      <?php $title = $l->t('Let the user specify an arbitrary address.'); ?>
      <input type="radio"
             name="emailAddressChoice"
             class="radio emailAdressChoice"
             id="userChosenEmail"
             value="userChosenEmail"
	     <?php if ($emailAddressChoice  == 'userChosenEmail') { echo 'checked="checked"'; } ?>
             title="<?php p($title); ?>"
      />
      <label for="userChosenEmail" title="<?php p($title); ?>">
        <?php p($l->t('User\'s Choice')); ?>
      </label>
    </div>
    <div class="rcSetting">
      <h3><?php p($l->t('Advanced settings')); ?></h3>
      <input type="checkbox"
             class="checkbox"
             name="forceSSO"
             id="forceSSO"
	     <?php if ($forceSSO) { echo 'checked="checked"'; } ?>
      />
      <label for="forceSSO">
	<?php p($l->t('Force single sign on (disables custom password).')); ?>
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
      <br/>
      <input type="checkbox"
             name="personalEncryption"
             id="personalEncryption"
             class="checkbox"
	     <?php if ($personalEncryption) { echo 'checked="checked"'; } ?>
      />
      <label title="<?php p($l->t('Encrypt per-user data -- in particular their email passwords -- with their personal cloud password. This implies that these settings will be lost when users forget their passwords. If unchecked the email login credentials are still protected by the server secret. The latter implies that an administrator is able to decrypt the login credentials, but the configuration data survives user password-loss.')); ?>"
             for="personalEncryption">
	<?php p($l->t('Per-user encryption of config values.')); ?>
      </label>
    </div>

    <input id="rcSettingsSubmit" type="submit" value="<?php p($l->t('Save')); ?>"/>
    <span id="rc_save_status" class="msg status hidden"/><?php p($l->t('Saving...')); ?></span>
    <span id="rc_save_error" class="msg error hidden"/></span>
    <span id="rc_save_success" class="msg success hidden"/></span>
  </form>
</div>
