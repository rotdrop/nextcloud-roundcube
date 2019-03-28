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
$imgDel = \OC::$server->getURLGenerator()->imagePath('core', 'actions/delete.svg');
?>
<div class="section" id="roundcube">
	<h2 class="app-name">RoundCube</h2>
	<form id="rcAdminSettings" action="#" method="post">
		<!-- Prevent CSRF attacks-->
		<input type="hidden" name="requesttoken" id="requesttoken">
		<input type="hidden" name="appname" value="roundcube">

		<div class="rcSetting">
			<h3><?php p($l->t('Default RC installation path')); ?></h3>
			<label><input type="text" id="defaultRCPath" name="defaultRCPath"
				value="<?php p($_['defaultRCPath']); ?>"
				maxlength="128" style="width:400px">
			</label>
			<p><?php p($l->t('Default path relative to ownCloud server (%s), or full URL if it\'s on a different domain.', $_['ocServer'])); ?></p>
		</div>
		<div class="rcSetting">
			<h3><?php p($l->t('Per email domain RC installations')); ?></h3>
			<p><?php p($l->t("Enter your users' email domains and their corresponding paths if you have different RoundCube installations. For example: 'domain1.com':'roundcube1', 'domain2.com':'https://mail.domain2.com/'. Path relative to ownCloud server or full URL.")); ?></p>
			<table id="rcTableDomainPath">
				<tbody>
					<template id="rcDomainPath">
						<tr>
							<td><input type="text" name="rcDomain[]" maxlength="64" style="width:200px"></td>
							<td><input type="text" name="rcPath[]" maxlength="128" style="width:350px"></td>
							<td class="remove"><a class="action delete" href="#" title="<?php p($l->t('Remove')); ?>"><img class="action" src="<?php p($imgDel); ?>"></a></td>
						</tr>
					</template>
<?php
foreach ($_['domainPath'] as $domain => $path) : ?>
					<tr>
						<td>
							<input type="text" name="rcDomain[]" maxlength="64"
							style="width:200px" value="<?php p($domain); ?>">
						</td>
						<td>
							<input type="text" name="rcPath[]" maxlength="128"
							style="width:350px" value="<?php p($path); ?>">
						</td>
						<td class="remove">
							<a class="action delete" href="#"
								title="<?php p($l->t('Remove')); ?>">
								<img class="action" src="<?php p($imgDel); ?>">
							</a>
						</td>
					</tr>
<?php
endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<td>
							<a id="rcAddDomainPath" class="button" href="#"><?php p($l->t('Add')); ?></a>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<div class="rcSetting">
			<h3><?php p($l->t('Advanced settings')); ?></h3>
			<label>
				<input type="checkbox" name="showTopLine" id="showTopLine"
					<?php if ($_['showTopLine']) { p(' checked="checked"'); } ?>>
				<?php p($l->t('Show RoundCube top information bar (shows logout button).')); ?>
			</label>
			<br>
			<label
				title="<?php p($l->t('Disable when debugging with self-signed certificates.')); ?>">
				<input type="checkbox" name="enableSSLVerify" id="enableSSLVerify"
					<?php if ($_['enableSSLVerify']) { p(' checked="checked"'); } ?>>
				<?php p($l->t('Enable SSL verification.')); ?>
			</label>
		</div>

		<input id="rcAdminSubmit" type="submit" value="<?php p($l->t('Save')); ?>">
		<span id="rc_save_status" class="msg hidden"><?php p($l->t('Saving...')); ?></span>
		<span id="rc_save_error" class="msg error hidden"></span>
		<span id="rc_save_success" class="msg success hidden"></span>
	</form>
</div>
