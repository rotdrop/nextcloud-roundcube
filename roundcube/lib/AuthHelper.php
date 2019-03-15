<?php
/**
 * ownCloud - roundcube auth helper
 *
 * @author Martin Reinhardt
 * @copyright 2013 Martin Reinhardt contact@martinreinhardt-online.de
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

use OCA\RoundCube\DBUtil;
use OCP\Util;

class AuthHelper
{

    /**
     *
     * @param params array with a mutable array inside, for
     *               storing the variable-value pairs.
     *
     */
    public static function jsLoadHook($params)
    {
        \OCP\App::checkAppEnabled('roundcube');
        $jsAssign = &$params['array'];

        $refresh = \OC::$server->getConfig()->getAppValue('roundcube', 'rcRefreshInterval', 30);
        $jsAssign['Roundcube'] = 'Roundcube || {};' . "\n" . 'Roundcube.refreshInterval = ' . $refresh;
    }

    /**
     * Login into roundcube server
     *
     * @param array $params Keys are: [run,uid,password]
     * @return true if login was successfull otherwise false
     */
    public static function login($params)
    {
        App::setSessionVariable(App::SESSION_ATTR_RCUSER, $params['uid']);
        App::setSessionVariable(App::SESSION_ATTR_RCPASS, $params['password']);
        // return false;
// CCT add
        if ($params['uid'] === 'admin') {
            // admin no hace login/logout
            Util::writeLog('roundcube', __METHOD__ . ": 'admin' no hace login/logout", Util::INFO);
            return true;
        }
// fin CCT add
        $via = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (preg_match('#(/ocs/v1.php|'.
                       '/apps/calendar/caldav.php|'.
                       '/apps/contacts/carddav.php|'.
                       '/remote.php/webdav)/#', $via)) {
            return;
        }
        \OCP\App::checkAppEnabled('roundcube');
        try {
            $username = $params['uid'];
            $password = $params['password'];

            Util::writeLog('roundcube', __METHOD__ . ": Preparing login of roundcube user '$username'", Util::DEBUG);

// CCT edit
            // $maildir = \OC::$server->getConfig()->getAppValue('roundcube', 'maildir', ''); // CCT comentada
            $maildir = CCTMaildir::getCCTMaildir();
// fin CCT edit
            $rc_host = self::getServerHost();
            $rc_port = \OC::$server->getConfig()->getAppValue('roundcube', 'rcPort', '');
            $enable_auto_login = \OC::$server->getConfig()->getAppValue('roundcube', 'autoLogin', false);
            if ($enable_auto_login) {
                Util::writeLog('roundcube', __METHOD__ . ': Starting auto login', Util::DEBUG);
                // SSO attempt
                $mail_username = $username;
                $mail_password = $password;
            } else {
                Util::writeLog('roundcube', __METHOD__ . ': Starting manual login', Util::DEBUG);
                $privKey = App::getPrivateKey($username, $password);
                // Fetch credentials from data-base
                $mail_userdata_entries = App::checkLoginData($username);
                // TODO create dropdown list
                $mail_userdata = $mail_userdata_entries[0];
                $mail_username = App::decryptMyEntry($mail_userdata['mail_user'], $privKey);
                Util::writeLog('roundcube', __METHOD__ . ': Used roundcube user: ' . $mail_username, Util::DEBUG);
                $mail_password = App::decryptMyEntry($mail_userdata['mail_password'], $privKey);
            }
            // save username for displaying in later usage
            App::setSessionVariable(App::SESSION_ATTR_RCUSER, $mail_username);
            // login
            return App::login($rc_host, $rc_port, $maildir, $mail_username, $mail_password);
        } catch (Exception $e) {
            // We got an exception == table not found
            Util::writeLog('roundcube', __METHOD__ . ': Login error.', Util::ERROR);
            return false;
        }
    }

    /**
     * Logout from roundcube server to cleaning up session on OwnCloud logout
     *
     * @return boolean true if logut was successfull, otherwise false
     */
    public static function logout()
    {
// CCT add
        if (\OC::$server->getUserSession()->getUser()->getUID() === 'admin') {
            // admin no hace login/logout
            Util::writeLog('roundcube', __METHOD__ . ": 'admin' no hace login/logout", Util::INFO);
            return true;
        }
// fin CCT add
        $via = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (preg_match('#(/ocs/v1.php|'.
                       '/apps/calendar/caldav.php|'.
                       '/apps/contacts/carddav.php|'.
                   '/remote.php/webdav)/#', $via)) {
            return;
        }
        \OCP\App::checkAppEnabled('roundcube');
        try {
            Util::writeLog('roundcube', __METHOD__ . ': Preparing logout of user from roundcube.', Util::DEBUG);
// CCT edit
            // $maildir = \OC::$server->getConfig()->getAppValue('roundcube', 'maildir', ''); // CCT comentada
            $maildir = CCTMaildir::getCCTMaildir();
// fin CCT edit
            $rc_host = self::getServerHost();
            $rc_port = \OC::$server->getConfig()->getAppValue('roundcube', 'rcPort', '');

            App::logout($rc_host, $rc_port, $maildir, \OC::$server->getUserSession()->getUser()->getUID());
            Util::writeLog('roundcube', __METHOD__ . ': Logout of user ' . \OC::$server->getUserSession()->getUser()->getUID() . ' from roundcube done', Util::INFO);
            return true;
        } catch (Exception $e) {
            // We got an exception == table not found
            Util::writeLog('roundcube', __METHOD__ . ': Logout/Session cleaning causing errors.', Util::DEBUG);
            return false;
        }
    }

    /**
     * Refreshes the roundcube HTTP session
     *
     * @return boolean true if refresh was successfull, otherwise false
     */
    public static function refresh()
    {
        $via = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (preg_match('#(/ocs/v1.php|'.
                       '/apps/calendar/caldav.php|'.
                       '/apps/contacts/carddav.php|'.
                   '/remote.php/webdav)/#', $via)) {
            return;
        }
        try {
            Util::writeLog('roundcube', __METHOD__ . ': Preparing refresh for roundcube', Util::DEBUG);
// CCT edit
            // $maildir = \OC::$server->getConfig()->getAppValue('roundcube', 'maildir', ''); // CCT comentada
            $maildir = CCTMaildir::getCCTMaildir();
// fin CCT edit
            $rc_host = self::getServerHost();
            $rc_port = \OC::$server->getConfig()->getAppValue('roundcube', 'rcPort', '');
            App::refresh($rc_host, $rc_port, $maildir);
            Util::writeLog('roundcube', __METHOD__ . ': Finished refresh for roundcube', Util::DEBUG);
            return true;
        } catch (Exception $e) {
            // We got an exception during login/refresh
            Util::writeLog('roundcube', 'AuthHelper.php: ' . 'Login error during refresh.', Util::DEBUG);
            return false;
        }
    }

    /**
     * listener which gets invoked if password is changed within owncloud
     *
     * @param unknown $params
     *            userdata
     */
    public static function changePasswordListener($params)
    {
        $username = $params['uid'];
        $password = $params['password'];

        // Try to fetch from session
        $oldPrivKey = App::getSessionVariable(App::SESSION_ATTR_RCPRIVKEY);
        // Take the chance to alter the priv/pubkey pair
        App::generateKeyPair($username, $password);
        $privKey = App::getPrivateKey($username, $password);
        $pubKey = App::getPublicKey($username);
        if ($oldPrivKey !== false) {
            // Fetch credentials from data-base
            $mail_userdata_entries = App::checkLoginData($username);
            foreach ($mail_userdata_entries as $mail_userdata) {
                $mail_username = App::decryptMyEntry($mail_userdata['mail_user'], $oldPrivKey);
                $mail_password = App::decryptMyEntry($mail_userdata['mail_password'], $oldPrivKey);
                App::cryptEmailIdentity($username, $mail_username, $mail_password);
                Util::writeLog('roundcube', __METHOD__ . ':' . 'Updated mail password data due to password changed for user ' . $username, Util::DEBUG);
            }
        } else {
            Util::writeLog('roundcube', __METHOD__ . ':' . 'No private key for ' . $username, Util::DEBUG);
        }
    }

    public static function getServerHost()
    {
        $rcHost = \OC::$server->getConfig()->getAppValue('roundcube', 'rcHost', '');
        if ($rcHost === '') {
            $rcHost = \OC::$server->getRequest()->getServerHost();
        }
        Util::writeLog('roundcube', __METHOD__ . ':' . ' rcHost: ' . $rcHost, Util::DEBUG);
        return $rcHost;
    }

    /**
     * Delete user from roundcube table
     * @param $params userdata
     * @return true if delete was successfull otherwise false
     */
    public static function delete($user)
    {
        try {
            // $username = $params['uid'];
            $username = $user->getUID();

            Util::writeLog('roundcube', __METHOD__ . ": Preparing delete of roundcube user '$username'.", Util::DEBUG);
            return DBUtil::delUser($username);
        } catch (Exception $e) {
            // We got an exception == table not found
            Util::writeLog('roundcube', __METHOD__ . ': Missing table or user.', Util::ERROR);
            return false;
        }
    }
}
