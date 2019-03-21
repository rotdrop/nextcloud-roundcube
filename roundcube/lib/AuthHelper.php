<?php
/**
 * ownCloud - RoundCube authentication helper
 *
 * @author Martin Reinhardt
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
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

use OCA\RoundCube\BackLogin;
use OCA\RoundCube\Crypto;
use OCP\Util;

class AuthHelper
{
    const COOKIE_RC_TOKEN     = "oc-rc-token";
    const COOKIE_RC_STRING    = "oc-rc-string";
    const COOKIE_RC_SESSID    = "roundcube_sessid";
    const COOKIE_RC_SESSAUTH  = "roundcube_sessauth";
    const SESSION_RC_PRIVKEY  = 'oc-rc-privateKey';
    const SESSION_RC_ADDRESS  = 'oc-rc-internal-address';

    /**
     * Save Login data for later login into roundcube server
     *
     * @param array $params Keys are: [run,uid,password]
     * @return true if login was successfull otherwise false
     */
    public static function postLogin($params) {
        \OCP\App::checkAppEnabled('roundcube');
        if ($params['uid'] === 'admin') {
            // admin no hace login/logout
            Util::writeLog('roundcube', __METHOD__ . ": 'admin' no hace login/logout", Util::INFO);
            return false;
        }
        $via = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (preg_match(
                '#(/ocs/v1.php|'.
                  '/apps/calendar/caldav.php|'.
                  '/apps/contacts/carddav.php|'.
                  '/remote.php/webdav)/#', $via)
        ) {
            return false;
        }
        Util::writeLog('roundcube', __METHOD__ . ": Preparing login of roundcube user '{$params['uid']}'", Util::DEBUG);
        $passphrase = Crypto::generateToken();
        $pair = Crypto::generateKeyPair($passphrase);
        $plainText = $params['password'];
        $b64crypted = Crypto::publicEncrypt($plainText, $pair['publicKey']);
        \OC::$server->getSession()->set(self::SESSION_RC_PRIVKEY, $pair['privateKey']);
        setcookie(self::COOKIE_RC_TOKEN, $passphrase, 0, "", "", true, true);
        setcookie(self::COOKIE_RC_STRING, $b64crypted, 0, "", "", true, true);
        return true;
    }

    /**
     * Logs in to RC webmail.
     * @return bool True on login, false otherwise.
     */
    public static function login() {
        $passphrase = \OC::$server->getRequest()->getCookie(self::COOKIE_RC_TOKEN);
        $b64crypted = \OC::$server->getRequest()->getCookie(self::COOKIE_RC_STRING);
        $encPrivKey = \OC::$server->getSession()->get(self::SESSION_RC_PRIVKEY);
        $password = Crypto::privateDecrypt($b64crypted, $encPrivKey, $passphrase);
        $username = \OC::$server->getUserSession()->getUser()->getUID();
        $backLogin = new BackLogin($username, $password);
        return $backLogin->login();
    }

    /**
     * Logout from RoundCube server by cleaning up session on OwnCloud logout
     * @return boolean True on success, false otherwise.
     */
    public static function logout() {
        \OCP\App::checkAppEnabled('roundcube');
        $user = \OC::$server->getUserSession()->getUser()->getUID();
        if ($user === 'admin') {
            // admin no hace login/logout
            Util::writeLog('roundcube', __METHOD__ . ": 'admin' no hace login/logout", Util::INFO);
            return true;
        }
        \OC::$server->getSession()->remove(self::SESSION_RC_PRIVKEY);
        // Expires cookies.
        setcookie(self::COOKIE_RC_TOKEN,    "-del-", 1, "", "", true, true);
        setcookie(self::COOKIE_RC_STRING,   "-del-", 1, "", "", true, true);
        setcookie(self::COOKIE_RC_SESSID,   "-del-", 1, "/", "", true, true);
        setcookie(self::COOKIE_RC_SESSAUTH, "-del-", 1, "/", "", true, true);
        Util::writeLog('roundcube', __METHOD__ . ": Logout of user '$user' from RoundCube done.", Util::INFO);
        return true;
    }

    /**
     * Listener which gets invoked if password is changed within ownCloud.
     * @param array $params ['uid', 'password']
     */
    public static function changePasswordListener($params) {
        if (isset($params['uid']) && isset($params['password'])) {
            self::login();
        }
    }
}
