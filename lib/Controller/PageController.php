<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * Nextcloud RoundCube App is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * Nextcloud RoundCube App is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with Nextcloud RoundCube App. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\RoundCube\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Util;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IL10N;

use OCA\RoundCube\Service\Config;
use OCA\RoundCube\Service\AuthRoundCube as Authenticator;

class PageController extends Controller
{
  use \OCA\RoundCube\Traits\LoggerTrait;

  /** @var string */
  private $userId;

  /** @var \OCA\RoundCube\Service\AuthRoundCube */
  private $authenticator;

  /** @var \OCP\IConfig */
  private $config;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  public function __construct(
    $appName
    , IRequest $request
    , $userId
    , Authenticator $authenticator
    , Config $config
    , IURLGenerator $urlGenerator
    , ILogger $logger
    , IL10N $l10n
  ) {
    parent::__construct($appName, $request);
    $this->userId = $userId;
    $this->authenticator = $authenticator;
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  /**
   * @NoAdminRequired
   * @NoCSRFRequired
   */
  public function index()
  {
    $credentials = $this->config->emailCredentials();
    if (empty($credentials)) {
      return new TemplateResponse($this->appName, "part.error.noemail", [ 'user' => $this->userId ]);
    }

    if (!$this->authenticator->login($credentials['userId'], $credentials['password'])) {
      return new TemplateResponse($this->appName, "part.error.login", array());
    }
    $url = $this->authenticator->externalURL();
    $this->logInfo($url);
    $tplParams = [
      'appName'      => $this->appName,
      'webPrefix'    => $this->appName,
      'url'          => $url,
      'loadingImage' => $this->urlGenerator->imagePath($this->appName, 'loader.gif'),
      'showTopLine'  => $this->config->getAppValue($this->appName, 'showTopLine', false)
    ];
    $tpl = new TemplateResponse($this->appName, "tpl.mail", $tplParams);

    // This is mandatory to embed a different server in an iframe.
    $urlParts = parse_url($url);
    $rcServer = $urlParts['host'];

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
