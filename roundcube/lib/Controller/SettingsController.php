<?php
namespace OCA\RoundCube\Controller;

use OCP\AppFramework\{
	Controller,
	Http\JSONResponse
};
use OCP\IRequest;
use OCP\Template;

class SettingsController extends Controller
{
	public function __construct($AppName, IRequest $request) {
		parent::__construct($AppName, $request);
	}

	public function adminSettings() {
		// fill template
		$params = array('maildir', 'removeHeaderNav', 'removeControlNav', 'autoLogin', 'noSSLverify','enableDebug', 'rcHost', 'rcPort', 'rcInternalAddress', 'rcRefreshInterval');

		$tmpl = new \OCP\Template('roundcube', 'tpl.adminSettings');
		foreach ($params as $param) {
			$value = \OC::$server->getConfig()->getAppValue('roundcube', $param, '');
			$tmpl->assign($param, $value);
		}
		// workaround to detect OC version
		$ocVersion = \OCP\Util::getVersion();
		$tmpl->assign('ocVersion', $ocVersion[0]);
		//$tmpl->assign('user', 'admin'); // CCT add
		return $tmpl->fetchPage();
	}

	/**
	 * @NoAdminRequired
	 */
	public function userSettings() {
		// fill template
		$params = array();
		$tmpl = new \OCP\Template('roundcube', 'tpl.userSettings');
		foreach ($params as $param) {
			$value = \OC::$server->getConfig()->getAppValue('roundcube', $param, '');
			$tmpl->assign($param, $value);
		}
		// workaround to detect OC version
		$ocVersion = \OCP\Util::getVersion();
		$tmpl->assign('ocVersion', $ocVersion[0]);
		return $tmpl -> fetchPage();
	}

	/**
	 * @NoAdminRequired
	 */
	public function setUserSettings() {
		return \OCA\RoundCube\App::saveUserSettings(
			$_POST['appname'],
			\OC::$server->getUserSession()->getUser()->getUID(),
			$_POST['rc_mail_username'],
			$_POST['rc_mail_password']
		);
	}

	public function setAdminSettings() {
		$l = \OC::$server->getL10N('roundcube');

		$chkboxs = array('removeHeaderNav', 'removeControlNav', 'autoLogin',
			'noSSLverify', 'enableDebug');

		if (isset($_POST['appname']) && $_POST['appname'] === "roundcube") {
			foreach ($chkboxs as $c) {
				\OC::$server->getConfig()->setAppValue('roundcube', $c, isset($_POST[$c]));
			}
			if (isset($_POST['rcHost'])) {
				if ($_POST['rcHost'] === '' || strlen($_POST['rcHost']) > 3) {
					\OC::$server->getConfig()->setAppValue('roundcube', 'rcHost', $_POST['rcHost']);
				}
			}
			\OC::$server->getConfig()->setAppValue('roundcube', 'rcPort', $_POST['rcPort']);
			if (isset($_POST['maildir'])) {
				$maildir =  $_POST['maildir'];
				if (substr($maildir, -1) !== '/') {
					$maildir .= '/';
				}
				\OC::$server->getConfig()->setAppValue('roundcube', 'maildir', $maildir);
			}
			if (isset($_POST['rcInternalAddress'])) {
				if ($_POST['rcInternalAddress'] == '' || strpos($_POST['rcInternalAddress'], '://') > -1) {
					\OC::$server->getConfig()->setAppValue('roundcube', 'rcInternalAddress', $_POST['rcInternalAddress']);
				} else {
					return new JSONResponse(array(
						"status" => 'error',
						"data" => array(
							"message" => $l->t("Internal address '%s' is not an URL",
								array($_POST['rcInternalAddress	']))
						)
					));
				}
			}
			if (isset($_POST['rcRefreshInterval'])) {
				$refresh = trim($_POST['rcRefreshInterval']);
				if ($refresh === '') {
					\OC::$server->getConfig()->deleteAppValue('roundcube', 'rcRefreshInterval');
				} elseif (!is_numeric($refresh)) {
					return new JSONResponse(array(
						"status" => 'error',
						"data" => array(
							"message" => $l->t("Refresh interval '%s' is not a number.",
								array($refresh))
						)
					));
				} else {
					\OC::$server->getConfig()->setAppValue('roundcube', 'rcRefreshInterval', $refresh);
				}
			}
			// update login status
			// CCT: comentado porque admin no hace login
			// $username = \OC::$server->getUserSession()->getUser()->getUID();
			// $params = array("uid" => $username);
			// $loginHelper = new \OCA\RoundCube\AuthHelper();
			// $loginHelper->login($params);
		} else {
			return new JSONResponse(array(
				"status" => 'error',
				"data" => array("message" => $l->t("Not submitted for us."))
			));
		}

		return new JSONResponse(array(
			"status" => 'success',
			'data' => array('message' => $l->t('Application settings successfully stored.'))
		));
	}
}
