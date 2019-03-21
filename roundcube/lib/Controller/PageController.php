<?php
/**
 * ownCloud - RoundCube PageController
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

use OCA\RoundCube\AuthHelper;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Util;

class PageController extends \OCP\AppFramework\Controller
{
	public function __construct($AppName, \OCP\IRequest $request) {
		parent::__construct($AppName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		// workaround to detect OC version
		// $ocVersion = implode('.', \OCP\Util::getVersion());
		// \OCP\Util::writeLog('roundcube', __METHOD__ . "Running on OwnCloud $ocVersion", \OCP\Util::DEBUG);
		$l = \OC::$server->getL10N($this->appName);
		\OC::$server->getNavigationManager()->setActiveEntry($this->appName);
		$user = \OC::$server->getUserSession()->getUser()->getUID();

		if (!\OCA\RoundCube\DBUtil::tableExists()) {
			Util::writeLog($this->appName, __METHOD__ . ": DB table entries not created.", Util::WARN);
			return new TemplateResponse($this->appName, "part.error.db", array());
		}
		if ($user === 'admin') {
			Util::writeLog($this->appName, __METHOD__ . ": 'admin' no hace login/logout.", Util::INFO);
			return new TemplateResponse($this->appName, "part.error.admin", array());
		}
		if (!AuthHelper::login()) {
			return new TemplateResponse($this->appName, "part.error.login", array());
		}
		$config = \OC::$server->getConfig();
		$url = \OC::$server->getSession()->get(AuthHelper::SESSION_RC_ADDRESS);
		$tplParams = array(
			'user'      => $user,
			'appName'   => $this->appName,
			'url'       => $url,
			'loading'   => \OC::$server->getURLGenerator()->imagePath($this->appName, 'loader.gif'),
			'rmCtrlNav' => $config->getAppValue($this->appName, 'removeControlNav', false),
			'rmHdrNav'  => $config->getAppValue($this->appName, 'removeHeaderNav', false)
		);
		$tpl = new TemplateResponse($this->appName, "tpl.mail", $tplParams);
		// This is mandatory to embed a different subdomain in an iframe.
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('*.rosario-conicet.gov.ar');
		$tpl->setContentSecurityPolicy($csp);

		return $tpl;
	}
}
