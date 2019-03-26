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
			'maildir'           => $config->getAppValue($this->appName, 'maildir', ''),
			'showTopLine'       => $config->getAppValue($this->appName, 'showTopLine', false),
			'enableSSLVerify'   => $config->getAppValue($this->appName, 'enableSSLVerify', true),
			'enableDebug'       => $config->getAppValue($this->appName, 'enableDebug', false),
			'rcHost'            => $config->getAppValue($this->appName, 'rcHost', ''),
			'rcPort'            => $config->getAppValue($this->appName, 'rcPort', ''),
			'rcInternalAddress' => $config->getAppValue($this->appName, 'rcInternalAddress', '')
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
		$maildir           = $req->getParam('maildir', '');
		$showTopLine       = $req->getParam('showTopLine', null);
		$enableSSLVerify   = $req->getParam('enableSSLVerify', null);
		$enableDebug       = $req->getParam('enableDebug', null);
		$rcHost            = $req->getParam('rcHost', '');
		$rcPort            = $req->getParam('rcPort', '');
		$rcInternalAddress = $req->getParam('rcInternalAddress', '');

		$validation = array();
		if (!is_string($maildir) || $maildir === '') {
			$validation[] = $l->t("Maildir can't be an empty string.");
		}
		if (!is_string($rcHost) || ($rcHost !== '' && strlen($rcHost) < 4)) {
			$validation[] = $l->t("Host is not valid.");
		}
		if (! ((is_numeric($rcPort) && $rcPort > 0 && $rcPort < 65536) || $rcPort === '')) {
			$validation[] = $l->t("Port must be a valid port number or left empty.");
		}
		if (! ((is_string($rcInternalAddress) && strpos($rcInternalAddress, '://') > -1)
			|| $rcInternalAddress === '')) {
			$validation[] = $l->t("Internal address '%s' is not an URL",
				array($rcInternalAddress));
		}
		if (!empty($validation)) {
			return new JSONResponse(array(
				'status'  => 'error',
				'message' => $l->t("Some inputs are not valid."),
				'invalid' => $validation
			));
		}

		// Passed validation.
		$maildirFixed = "/" . trim($maildir, " /") . "/";
		$config->setAppValue($appName, 'maildir', $maildirFixed);
		$checkBoxes = array('showTopLine', 'enableSSLVerify', 'enableDebug');
		foreach ($checkBoxes as $c) {
			$config->setAppValue($appName, $c, $$c !== null);
		}
		$config->setAppValue($appName, 'rcHost', $rcHost);
		$config->setAppValue($appName, 'rcPort', $rcPort);
		$config->setAppValue($appName, 'rcInternalAddress', $rcInternalAddress);

		return new JSONResponse(array(
			'status'  => 'success',
			'message' => $l->t('Application settings successfully stored.'),
			'config'  => array('maildir' => $maildirFixed)
		));
	}
}
