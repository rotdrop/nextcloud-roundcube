<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @copyright 2020, 2021, 2023 Claus-Justus Heine
 * @license   AGPL-3.0-or-later
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
use OCP\AppFramework\Services\IInitialState;
use OCP\Util;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;

use OCA\RoundCube\Constants;
use OCA\RoundCube\Controller\SettingsController;
use OCA\RoundCube\Service\AssetService;
use OCA\RoundCube\Service\Config;
use OCA\RoundCube\Service\AuthRoundCube as Authenticator;

/** Main page entry point. */
class PageController extends Controller
{
  use \OCA\RoundCube\Toolkit\Traits\LoggerTrait;

  const MAIN_TEMPLATE = 'app';
  const MAIN_ASSET = self::MAIN_TEMPLATE;
  const SUCCESS_STATE = 'success';
  const ERROR_STATE = 'error';
  const ERROR_NOEMAIL_REASON = 'noemail';
  const ERROR_LOGIN_REASON = 'login';

  /** @var string */
  private $userId;

  /** @var \OCA\RoundCube\Service\AuthRoundCube */
  private $authenticator;

  /** @var Config */
  private $config;

  /** @var AssetService */
  private $assetService;

  /** @var IInitialState */
  private $initialState;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  /**
   * Explicitly declare the properties.
   */
  private $l; // Assuming $l is for localization (IL10N)


  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    ?string $userId,
    Authenticator $authenticator,
    Config $config,
    AssetService $assetService,
    IInitialState $initialState,
    IURLGenerator $urlGenerator,
    ILogger $logger,
    IL10N $l10n,
  ) {
    parent::__construct($appName, $request);
    $this->userId = $userId;
    $this->authenticator = $authenticator;
    $this->config = $config;
    $this->assetService = $assetService;
    $this->initialState = $initialState;
    $this->urlGenerator = $urlGenerator;
    $this->logger = $logger;
    $this->l = $l10n;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * @return TemplateResponse
   *
   * @NoAdminRequired
   * @NoCSRFRequired
   */
  public function index()
  {
    $state = self::SUCCESS_STATE;
    $reason = null;

    $credentials = $this->config->emailCredentials();
    if (empty($credentials)) {
      $state = self::ERROR_STATE;
      $reason = self::ERROR_NOEMAIL_REASON;
    } elseif (!$this->authenticator->login($credentials['userId'], $credentials['password'])) {
      $state = self::ERROR_STATE;
      $reason = self::ERROR_LOGIN_REASON;
    }

    $this->initialState->provideInitialState('config', [
      'state' => $state,
      'reason' => $reason,
      'emailUserId' => $credentials['userId'] ?? null,
      Config::EXTERNAL_LOCATION => $this->authenticator->externalURL(),
      Config::SHOW_TOP_LINE => $this->config->getAppValue(Config::SHOW_TOP_LINE),
    ]);

    $url = $this->authenticator->externalURL();
    $this->logInfo($url);
    $tplParams = [
      'appName' => $this->appName,
      'assets' => [
        Constants::JS => $this->assetService->getJSAsset(self::MAIN_ASSET),
        Constants::CSS => $this->assetService->getCSSAsset(self::MAIN_ASSET),
      ],
    ];
    $tpl = new TemplateResponse($this->appName, self::MAIN_TEMPLATE, $tplParams);

    // This is mandatory to embed a different server in an iframe.
    $urlParts = parse_url($url);
    $rcServer = $urlParts['host'];

    if ($rcServer !== '') {
      $csp = new ContentSecurityPolicy();
      $csp->addAllowedFrameDomain($rcServer);
      // $csp->addAllowedScriptDomain($rcServer);
      $csp->allowInlineScript(true)->allowEvalScript(true);
      // $this->logDebug('Added CSP frame: ' . $rcServer);
      $tpl->setContentSecurityPolicy($csp);
    }
    return $tpl;
  }
}
