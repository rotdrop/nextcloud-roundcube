<?php
namespace OCA\RoundCube\Controller;

use OCP\AppFramework\{
	Controller,
	Http\JSONResponse
};
use OCP\IRequest;
use OCA\RoundCube\App;

class PageController extends Controller
{
	public function __construct($AppName, IRequest $request) {
		parent::__construct($AppName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		// workaround to detect OC version
		$ocVersion = implode('.', \OCP\Util::getVersion());
		\OCP\Util::writeLog('roundcube', __METHOD__ . "Running on OwnCloud $ocVersion", \OCP\Util::DEBUG);
		// add new navigation entry
		\OC::$server->getNavigationManager()->setActiveEntry("roundcube_index");

		$ocUser = App::getSessionVariable(App::SESSION_ATTR_RCUSER);
		$ocPass = App::getSessionVariable(App::SESSION_ATTR_RCPASS);
		$tmpl = new \OCP\Template("roundcube", "tpl.mail", "user");
		$tmpl->assign('user', $ocUser);
		$tmpl->assign('pass', $ocPass);
		$tmpl->printpage();
	}

	/**
	 * @NoAdminRequired
	 */
	public function refresh() {
		return \OCA\RoundCube\AuthHelper::refresh();
	}
}
