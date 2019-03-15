<?php
/**
 * ownCloud - roundcube db helper methods
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

class DBUtil
{
    /**
     * Check if roundcube table exists in the current database.
     *
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    public static function tableExists() {
        \OCP\Util::writeLog('roundcube', __METHOD__ . ": Checking if roundcube table exists.", \OCP\Util::DEBUG);
        // Try a select statement against the table
        // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
        try {
            $sql = 'SELECT * FROM `*PREFIX*roundcube` LIMIT 1';
            \OCP\Util::writeLog('roundcube', __METHOD__ . ": Used SQL: '$sql'.", \OCP\Util::DEBUG);
            $query = \OC::$server->getDatabaseConnection()->prepare($sql);
            $result = $query->execute();
        } catch (Exception $e) {
            // We got an exception == table not found
            \OCP\Util::writeLog('roundcube', __METHOD__ . ": Table roundcube does not exist. $e", \OCP\Util::DEBUG);
            return false;
        }
        \OCP\Util::writeLog('roundcube', __METHOD__ . ": Table roundcube exists.", \OCP\Util::DEBUG);
        return true;
    }

    public static function getUser($ocUser) {
        $stmt = \OC::$server->getDatabaseConnection()->prepare(
            "SELECT * FROM `*PREFIX*roundcube` WHERE `oc_user`=?;"
        );
        $result = $stmt->execute(array(
            $ocUser
        ));
        if ($result === true) {
            return $stmt->fetchAll();
        }
        return array();
    }

    public static function addUser($params) {
        \OCP\Util::writeLog('roundcube', __METHOD__ . ": Writing basic data for {$params['uid']}", \OCP\Util::DEBUG);
        $stmt = \OC::$server->getDatabaseConnection()->prepare(
            "INSERT INTO `*PREFIX*roundcube` (`oc_user`) VALUES (?);"
        );
        $result = $stmt->execute(array(
            $params['uid']
        ));
        return $result;
    }

    public static function delUser($ocUser) {
        \OCP\Util::writeLog('roundcube', __METHOD__ . ": Deleting user: $ocUser", \OCP\Util::DEBUG);
        $stmt = \OC::$server->getDatabaseConnection()->prepare(
            "DELETE FROM `*PREFIX*roundcube` WHERE `oc_user`=?;"
        );
        $result = $stmt->execute(array(
            $ocUser
        ));
        return $result;
    }
}
