<?php
/**
 *
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
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
/**
 *
 * USAGE
 *   The class provides one public method:
 *
 *   - getCCTMaildir($domain)
 *         Devuelve el correcto maildir según el dominio del usuario.
 *
 */
class CCTMaildir
{
    private static function getCCTMaildirOfDomain($domain) {
        $mailDomains = array(
            'rosario-conicet.gov.ar',
            'ifir-conicet.gov.ar',
            'cifasis-conicet.gov.ar',
            'roscytec.org.ar',
            'iidefar-conicet.gob.ar',
            'ishir-conicet.gov.ar',
            'irice-conicet.gov.ar',
            'inv.rosario-conicet.gov.ar',
            'iech-conicet.gob.ar',
            'bec.rosario-conicet.gov.ar');

        $mailbioqDomains = array(
            'ibr-conicet.gov.ar',
            'cefobi-conicet.gov.ar',
            'iquir-conicet.gov.ar',
            'idicer-conicet.gob.ar',
            'ifise-conicet.gov.ar',
            'iprobyq-conicet.gob.ar',
            'iicar-conicet.gob.ar');

        // Si el dominio del usuario está en servidor mail
        if (in_array($domain, $mailDomains)) {
            return '/roundcube1/';
        }
        // Si el dominio del usuario está en servidor mailbioq
        if (in_array($domain, $mailbioqDomains)) {
            return '/roundcube2/';
        }
        \OCP\Util::writeLog('roundcube', 'CCTMaildir.php: Dominio desconocido: ' . $domain, \OCP\Util::DEBUG);
        return \OC::$server->getConfig()->getAppValue('roundcube', 'maildir', '');
    }

    public static function getCCTMaildir($username = null) {
        $user = $username;
        if ($username === null) {
            $user = \OC::$server->getUserSession()->getUser()->getUID();
        }
        $user_domain = explode('@', $user);
        if (count($user_domain) === 2) {
            $dominio = $user_domain[1];
            return self::getCCTMaildirOfDomain($dominio);
        } else {
            return \OC::$server->getConfig()->getAppValue('roundcube', 'maildir', '');
        }
    }
}
