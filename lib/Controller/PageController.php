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
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const MAIN_TEMPLATE = 'app';
  const MAIN_ASSET = self::MAIN_TEMPLATE;
  const ERROR_NOEMAIL_TEMPLATE = 'error/noemail';
  const ERROR_LOGIN_TEMPLATE = 'error/login';
  const ERROR_ASSET = 'error';

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
    $credentials = $this->config->emailCredentials();
    if (empty($credentials)) {
      return new TemplateResponse(
        $this->appName,
        self::ERROR_NOEMAIL_TEMPLATE, [
          'appName' => $this->appName,
          'user' => $this->userId,
          'assets' => [
            Constants::JS => $this->assetService->getJSAsset(self::ERROR_ASSET),
            Constants::CSS => $this->assetService->getCSSAsset(self::ERROR_ASSET),
          ],
        ],
      );
    }

    if (!$this->authenticator->login($credentials['userId'], $credentials['password'])) {
      return new TemplateResponse(
        $this->appName,
        self::ERROR_LOGIN_TEMPLATE, [
          'appName' => $this->appName,
          'user' => $this->userId,
          'assets' => [
            Constants::JS => $this->assetService->getJSAsset(self::ERROR_ASSET),
            Constants::CSS => $this->assetService->getCSSAsset(self::ERROR_ASSET),
          ],
        ],
      );
    }

    $this->initialState->provideInitialState('config', [
      SettingsController::EXTERNAL_LOCATION => $this->authenticator->externalURL(),
      SettingsController::SHOW_TOP_LINE => $this->config->getAppValue(SettingsController::SHOW_TOP_LINE, flase),
    ]);

    $url = $this->authenticator->externalURL();
    $this->logInfo($url);
    $tplParams = [
      'appName'      => $this->appName,
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
