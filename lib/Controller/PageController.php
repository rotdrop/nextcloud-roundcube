<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @author 2020 Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use OCP\AppFramework\Controller;
use OCP\IRequest;

use OCA\RoundCube\AuthHelper;
use OCA\RoundCube\InternalAddress;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Util;
use OCP\ISession;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IL10N;

class PageController extends Controller
{
  use \OCA\RoundCube\Traits\LoggerTrait;

  /** @var string */
  private $userId;

  /** @var \OCP\IConfig */
  private $config;

  /** @var \OCP\ISession */
  private $session;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  public function __construct(
    $appName
    , $userId
    , IRequest $request
    , IConfig $config
    , ISession $session
    , IURLGenerator $urlGenerator
    , ILogger $logger
    , IL10N $l10n
  ) {
    parent::__construct($appName, $request);
    $this->userId = $userId;
    $this->config = $config;
    $this->session = $session;
    $this->urlGenerator = $urlGenerator;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  /**
   * @NoAdminRequired
   * @NoCSRFRequired
   */
  public function index() {

    if (strpos($this->userId, '@') === false) {
      $this->logWarn("username ($this->userId) is not an email address.");
      return new TemplateResponse($this->appName, "part.error.noemail", array('user' => $this->userId));
    }
    if (!AuthHelper::login()) {
      return new TemplateResponse($this->appName, "part.error.login", array());
    }
    $url = $session->get(AuthHelper::SESSION_RC_ADDRESS);
    $tplParams = array(
      'appName'     => $this->appName,
      'url'         => $url,
      'loading'     => $this->urlGenerator->imagePath($this->appName, 'loader.gif'),
      'showTopLine' => $this->config->getAppValue($this->appName, 'showTopLine', false)
    );
    $tpl = new TemplateResponse($this->appName, "tpl.mail", $tplParams);
    // This is mandatory to embed a different server in an iframe.
    $rcServer = $this->session->get(AuthHelper::SESSION_RC_SERVER, '');
    if ($rcServer !== '') {
      $csp = new ContentSecurityPolicy();
      $csp->addAllowedFrameDomain($rcServer);
      // $csp->addAllowedScriptDomain($rcServer);
      $csp->allowInlineScript(true)->allowEvalScript(true);
      // Util::writeLog($this->appName, __METHOD__ . ": Added CSP frame: $rcServer", Util::DEBUG);
      $tpl->setContentSecurityPolicy($csp);
    }
    return $tpl;
  }
}
