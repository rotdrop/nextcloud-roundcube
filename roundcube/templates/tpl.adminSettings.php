<?php
/**
 * ownCloud - roundcube mail plugin
 *
 * @author Martin Reinhardt and David Jaedke
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @copyright 2012 Martin Reinhardt contact@martinreinhardt-online.de
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
style('roundcube', 'adminSettings');
script('roundcube', 'adminSettings');
?>
<div class="section" id="roundcube">
	<form id="rcMailAdminPrefs" action="#" method="post">
		<!-- Prevent CSRF attacks-->
		<input type="hidden" name="requesttoken" value="" id="requesttoken">
		<input type="hidden" name="appname" value="roundcube">
		<h2>RoundCube</h2>
		<h3><?php p($l->t('Basic settings')); ?></h3>
		<label for="maildir" title="<?php p($l->t('If you have "http://example.com/roundcube" enter "/roundcube/" here. Note that subdomains or URLs do not work, just absolute paths to the same domain ownCloud is running.')); ?>"><?php p($l->t('Absolute path to RC installation')); ?>
		</label>
		<input type="text" id="maildir" name="maildir"
			value="<?php p($_['maildir']); ?>">
		<br>
		<h3><?php p($l->t('Advanced settings')); ?></h3>
		<label
			title="<?php p($l->t('Show RoundCube control navigation menu items with currently logged in user information')); ?>">
			<input type="checkbox" name="showTopLine" id="showTopLine"
				<?php if ($_['showTopLine']) { p(' checked="checked"'); } ?>>
			<?php p($l->t('Show information bar on top of page')); ?>
		</label>
		<br>
		<label
			title="<?php p($l->t('Enable SSL verification, e.g. disable for self-signed certificates')); ?>">
			<input type="checkbox" name="enableSSLVerify" id="enableSSLVerify"
				<?php if ($_['enableSSLVerify']) { p(' checked="checked"'); } ?>>
			<?php p($l->t('Enable SSL verification, e.g. disable for self-signed certificates')); ?>
		</label>
		<br>
		<label
			title="<?php p($l->t('Enable debug messages. RC tends to bloat the log-files.')); ?>">
			<input type="checkbox" name="enableDebug" id="enableDebug"
				<?php if ($_['enableDebug']) { p(' checked="checked"'); } ?>>
			<?php p($l->t('Enable debug logging')); ?>
		</label>
		<br>
		<br>
		<label for="rcHost" title="<?php p($l->t('Overwrite roundcube server hostname if not the same as owncloud, e.g. for (sub)domains which resides on the same server, e.g rc.domain.tld. But keep in mind that due to iFrame security constraints it will be only working on the same server, see HTML/JS same-origin policies.')); ?>">
			<?php p($l->t('Overwrite RC server hostname')); ?>
		</label>
		<input type="text" id="rcHost" name="rcHost" value="<?php p($_['rcHost']); ?>">
		<br>
		<label for="rcPort" title="<?php p($l->t('Overwrite roundcube server port (If not specified, ports 80/443 are used for HTTP/S).')); ?>">
			<?php p($l->t('Overwrite RC server port')); ?>
		</label>
		<input type="text" id="rcPort" name="rcPort" value="<?php p($_['rcPort']); ?>">
		<br>
		<br>
		<label for="rcInternalAddress" title="<?php p($l->t('Internal RoundCube address (as seen by the OwnCloud server). Use this if the internal address, to which OwnCloud should connect when talking to RoundCube, does not match the host and port set above.')); ?>">
			<?php p($l->t('Internal RC address')); ?>
		</label>
		<br>
		<input type="url" id="rcInternalAddress" name="rcInternalAddress" style="width: 400px;"
			value="<?php p($_['rcInternalAddress']); ?>">
		<br>
		<br>
		<input id="rcAdminSubmit" type="submit" value="<?php p($l->t('Save')); ?>">
		<span id="rc_save_status" class="msg hidden"><?php p($l->t('Saving...')); ?></span>
		<span id="rc_save_error" class="msg error hidden"></span>
		<span id="rc_save_success" class="msg success hidden"></span>
	</form>
</div>
