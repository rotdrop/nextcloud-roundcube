<?php
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

		$validation = array();
		if (!is_string($defaultRCPath) || $defaultRCPath === '') {
			$validation[] = $l->t("Default RC installation path can't be an empty string.");
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
			if (preg_match('/^https?:\/\//', $path) === 0 && $path !== '') {
				$path = ltrim($path, " /");
			}
		}
		if (count($rcDomains) !== count($rcPaths)) {
			$validation[] = $l->t("Unpaired domains and paths.");
		}
		if (!empty($validation)) {
			return new JSONResponse(array(
				'status'  => 'error',
				'message' => $l->t("Some inputs are not valid."),
				'invalid' => $validation
			));
		}

		// Passed validation.
		$defaultRCPath = trim($defaultRCPath);
		if (preg_match('/^https?:\/\//', $defaultRCPath) === 0) {
			$defaultRCPath = ltrim($defaultRCPath, " /");
		}
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
