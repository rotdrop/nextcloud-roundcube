<?php
/**
 * ownCloud - RoundCube mail plugin
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
namespace OCA\RoundCube\Controller;

use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;

class SettingsController extends \OCP\AppFramework\Controller
{
	public function __construct($AppName, \OCP\IRequest $request) {
		parent::__construct($AppName, $request);
	}

	public function adminSettings() {
		$config = \OC::$server->getConfig();
		$tplParams = array(
			'ocServer'          => \OC::$server->getURLGenerator()->getAbsoluteURL("/"),
			'defaultRCPath'     => $config->getAppValue($this->appName, 'defaultRCPath', ''),
			'domainPath'        => json_decode($config->getAppValue($this->appName, 'domainPath', ''), true),
			'showTopLine'       => $config->getAppValue($this->appName, 'showTopLine', false),
			'enableSSLVerify'   => $config->getAppValue($this->appName, 'enableSSLVerify', true)
		);
		return new TemplateResponse($this->appName, 'tpl.adminSettings', $tplParams, 'blank');
	}

	/**
	 * Validates and stores RC admin settings.
	 * @return JSONResponse array(
	 *                        "status"   => ...,
	 *                        "message"  => ...,
	 *                        ["invalid" => array($msg1, $msg2, ...),]
	 *                        ["config" => array("key" => "value", ...)]
	 *                      )
	 */
	public function setAdminSettings() {
		$l = \OC::$server->getL10N('roundcube');
		$req = \OC::$server->getRequest();
		$appName = $req->getParam('appname', null);
		if ($appName !== $this->appName) {
			return new JSONResponse(array(
				"status"  => 'error',
				"message" => $l->t("Not submitted for us.")
			));
		}

		$config = \OC::$server->getConfig();
		$defaultRCPath   = $req->getParam('defaultRCPath', '');
		$rcDomains       = $req->getParam('rcDomain', '');
		$rcPaths         = $req->getParam('rcPath', '');
		$showTopLine     = $req->getParam('showTopLine', null);
		$enableSSLVerify = $req->getParam('enableSSLVerify', null);

		// Validate and do a first fix of some values.
		$validation = array();
		if (!is_string($defaultRCPath) || $defaultRCPath === '') {
			$validation[] = $l->t("Default RC installation path can't be an empty string.");
		} elseif (preg_match('/^([a-zA-Z]+:)?\/\//', $path) === 1) {
			$validation[] = $l->t("Default path must be a url relative to this server.");
		} else {
			$defaultRCPath = trim($defaultRCPath);
		}
		foreach ($rcDomains as &$dom) {
			if (!is_string($dom) || preg_match('/(@|\/)/', $dom) === 1) {
				$validation[] = $l->t("A domain is not valid.");
				break;
			} else {
				$dom = trim($dom);
			}
		}
		foreach ($rcPaths as &$path) {
			if (!is_string($path)) {
				$validation[] = $l->t("A path is not valid.");
				break;
			}
			$path = trim($path);
			if (preg_match('/^([a-zA-Z]+:)?\/\//', $path) === 1 || $path === '') {
				$validation[] = $l->t("Paths must be urls relative to this server.");
				break;
			} else {
				$path = ltrim($path, " /");
			}
		}
		if (count($rcDomains) !== count($rcPaths)) {
			$validation[] = $l->t("Unpaired domains and paths.");
		}
		// Won't change anything if validation fails.
		if (!empty($validation)) {
			return new JSONResponse(array(
				'status'  => 'error',
				'message' => $l->t("Some inputs are not valid."),
				'invalid' => $validation
			));
		}

		// Passed validation.
		$defaultRCPath = ltrim($defaultRCPath, " /");
		$config->setAppValue($appName, 'defaultRCPath', $defaultRCPath);
		$domainPath = json_encode(array_filter(
			array_combine($rcDomains, $rcPaths),
			function($v, $k) {
				return $k !== '' && $v !== '';
			},
			ARRAY_FILTER_USE_BOTH
		));
		$config->setAppValue($appName, 'domainPath', $domainPath);
		$checkBoxes = array('showTopLine', 'enableSSLVerify');
		foreach ($checkBoxes as $c) {
			$config->setAppValue($appName, $c, $$c !== null);
		}

		return new JSONResponse(array(
			'status'  => 'success',
			'message' => $l->t('Application settings successfully stored.'),
			'config'  => array('defaultRCPath' => $defaultRCPath)
		));
	}
}
