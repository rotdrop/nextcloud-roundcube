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
use OCA\RoundCube\App;

style('roundcube', 'userSettings');
script('roundcube', 'userSettings');

if (!OCA\RoundCube\DBUtil::tableExists()) {
	OCP\Util::writeLog('roundcube', 'DB table entries do not exist...', OCP\Util::ERROR);
	print_unescaped($this->inc("part.error.db"));
}

$mail_userdata_entries = App::checkLoginData(\OC::$server->getUserSession()->getUser()->getUID());
?>
<div class="section" id="roundcube">
	<form id="roundcube" action="#"	method="post">
		<!-- Prevent CSRF attacks-->
		<input type="hidden" name="requesttoken" value="" id="requesttoken">
		<input type="hidden" name="appname" value="roundcube">

		<fieldset>
			<h2>RoundCube</h2>
			<em><?php p($l->t('RoundCube Mailaccount')); ?></em>
			<br>
<?php
$enable_auto_login = \OC::$server->getConfig()->getAppValue('roundcube', 'autoLogin', false);
if (!$enable_auto_login) {
	foreach ($mail_userdata_entries as $mail_userdata) {
		$mail_username = isset($_SESSION[App::SESSION_RC_USER]) ? $_SESSION[App::SESSION_RC_USER] : '';
		$mail_password = '';
		// TODO use template and add button for adding entries
?>
			<input type="text" id="rc_mail_username" name="rc_mail_username"
				value="<?php p($mail_username); ?>"
				placeholder="<?php p($l->t('Email Login Name')); ?>">
			<input type="password" id="rc_mail_password" name="rc_mail_password"
				placeholder="<?php p($l->t('Email Password')); ?>"
				data-typetoggle="rc_mail_password_show" />
			<div id="rc_usermail_update_message" class="statusmessage">
				<?php p($l->t('Saving...')); ?>
			</div>
			<div id="rc_usermail_success_message" class="successmessage"></div>
			<div id="rc_usermail_error_message" class="errormessage">
				<?php p($l->t('General saving error occurred.')); ?>
			</div>
			<div id="rc_usermail_error_empty_message" class="errormessage">
				<?php p($l->t('Please fill username and password fields')); ?>
			</div>
			<input type="button" id="rc_usermail_update" name="rc_usermail_update"
				value="<?php p($l->t('Update Email Identity')); ?>"/>
<?php
	}
} else {
	p($l->t('Autologin for users activated. OwnCloud user data will be used for login in roundcube'));
}
?>
		</fieldset>
	</form>
</div>
