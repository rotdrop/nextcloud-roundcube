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
use OCA\RoundCube\CCTMaildir;
use OCA\RoundCube\MailObject;

$html_output = "";
$ocUser     = $_['user'];
$appName    = $_['appName'];
$url        = $_['url'] ."?_task=mail";
$rmCtrlNav  = $_['rmCtrlNav'];
$rmHdrNav   = $_['rmHdrNav'];
$imgLoading = $_['loading'];

style($appName, 'base');
script($appName, 'app');
?>
<input type="hidden" id="disable_control_nav" value="<?php p($rmCtrlNav); ?>"/>
<input type="hidden" id="disable_header_nav" value="<?php p($rmHdrNav); ?>"/>
<div id="roundcubeLoaderContainer" style="display:none"><img src="<?php p($imgLoading); ?>" id="roundcubeLoader"></div>
<iframe src="<?php p($url); ?>" id="roundcubeFrame" name="roundcube" width="100%" height="100%" style="display:block"></iframe>

<?php
/*if (false) {
    $mailAppReturn = App::showMailFrame($url);
    if ($mailAppReturn->isErrorOccurred()) {
        OCP\Util::writeLog($appName, 'Not rendering roundcube iframe view due to errors', OCP\Util::ERROR);
        OCP\Util::writeLog($appName, 'Got the following error code: ' . $mailAppReturn->getErrorCode(), OCP\Util::ERROR);
        switch ($mailAppReturn->getErrorCode()) {
            case MailObject::ERROR_CODE_NETWORK:
                $html_output = $this->inc("part.error.error-settings");
                $html_output = $html_output . $mailAppReturn->getErrorDetails();
                break;
            case MailObject::ERROR_CODE_LOGIN:
                $html_output = $this->inc("part.error.wrong-auth");
                $html_output = $html_output . $mailAppReturn->getErrorDetails();
                break;
            case MailObject::ERROR_CODE_RC_NOT_FOUND:
                $html_output = $this->inc("part.error.error-settings");
                $html_output = $html_output . $mailAppReturn->getErrorDetails();
                break;
            default:
                $html_output = $this->inc("part.error.error-settings");
                $html_output = $html_output . $mailAppReturn->getErrorDetails();
                break;
        }
    }
}
if (false) {
    OCP\Util::writeLog($appName, 'Rendering roundcube iframe view', OCP\Util::INFO);
    if (!$rmCtrlNav) {
        $html_output = $html_output . "<div class=\"mail-controls\" id=\"mail-control-bar\"><div style=\"position: absolute;right: 13.5em;top: 0em;margin-top: 0.3em;\">" . $l->t("Logged in as ") . "&nbsp;" . $mailAppReturn->getDisplayName() . "</div></div>";
    }
    $html_output = $html_output . "<div id=\"notification\"></div>";
    if (!$rmCtrlNav) {
        $html_output = $html_output . "<div id=\"roundcube_container\" style=\"top: 5.5em;\">";
    } else {
        $html_output = $html_output . "<div id=\"roundcube_container\" >";
    }
    $html_output = $html_output . $mailAppReturn->getHtmlOutput();
    $html_output = $html_output . "</div>";
}
p($html_output);
*/
