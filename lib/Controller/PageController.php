<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @copyright 2020-2025 Claus-Justus Heine
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
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;
use Psr\Log\LoggerInterface as ILogger;

use OCA\RoundCube\Constants;
use OCA\RoundCube\Controller\SettingsController;
use OCA\RoundCube\Service\AssetService;
use OCA\RoundCube\Service\AuthRoundCube as Authenticator;
use OCA\RoundCube\Service\Config;

/** Main page entry point. */
class PageController extends Controller
{
  use \OCA\RoundCube\Toolkit\Traits\LoggerTrait;

  const MAIN_TEMPLATE = 'app';
  const MAIN_ASSET = self::MAIN_TEMPLATE;
  const SUCCESS_STATE = 'success';
  const ERROR_STATE = 'error';
  const ERROR_NORCURL_REASON = 'norcurl';
  const ERROR_NOEMAIL_REASON = 'noemail';
  const ERROR_LOGIN_REASON = 'login';
  const ERROR_CARDDAV_REASON = 'carddav';

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    private ?string $userId,
    private Authenticator $authenticator,
    private Config $config,
    private AssetService $assetService,
    private IInitialState $initialState,
    private IURLGenerator $urlGenerator,
    protected ILogger $logger,
  ) {
    parent::__construct($appName, $request);
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

    $roundCubeUrl = $this->authenticator->externalURL();
    if (empty($roundCubeUrl)) {
      $state = self::ERROR_STATE;
      $reason = self::ERROR_NORCURL_REASON;
    } else {
      $credentials = $this->config->emailCredentials();
      if (empty($credentials)) {
        $state = self::ERROR_STATE;
        $reason = self::ERROR_NOEMAIL_REASON;
      } elseif (!$this->authenticator->login($credentials['userId'], $credentials['password'])) {
        $state = self::ERROR_STATE;
        $reason = self::ERROR_LOGIN_REASON;
      } elseif ($this->authenticator->cardDavConfig() === false) {
        $state = self::ERROR_STATE;
        $reason = self::ERROR_CARDDAV_REASON;
      }
    }

    $this->initialState->provideInitialState('config', [
      'state' => $state,
      'reason' => $reason,
      'emailUserId' => $credentials['userId'] ?? null,
      Config::EXTERNAL_LOCATION => $roundCubeUrl,
      Config::SHOW_TOP_LINE => $this->config->getAppValue(Config::SHOW_TOP_LINE),
      Config::ENABLE_BRIDGE => $this->config->getAppValue(Config::ENABLE_BRIDGE),
    ]);

    Util::addScript($this->appName, $this->assetService->getJSAsset(self::MAIN_ASSET)['asset']);
    Util::addStyle($this->appName, $this->assetService->getCSSAsset(self::MAIN_ASSET)['asset']);

    $tpl = new TemplateResponse($this->appName, self::MAIN_TEMPLATE, []);

    // This is mandatory to embed a different server in an iframe.
    $urlParts = parse_url($roundCubeUrl);
    $rcServer = $urlParts['host'];

    if ($rcServer !== '') {
      $csp = new ContentSecurityPolicy();
      $csp->addAllowedFrameDomain($rcServer);
      // $csp->addAllowedScriptDomain($rcServer);
      // $csp->allowInlineScript(true)->allowEvalScript(true);
      // $this->logDebug('Added CSP frame: ' . $rcServer);
      $tpl->setContentSecurityPolicy($csp);
    }
    return $tpl;
  }
}
