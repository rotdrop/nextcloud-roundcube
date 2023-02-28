<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
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

use OCP\IRequest;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IUserSession;
use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;

use OCA\RoundCube\Settings\Personal;
use OCA\RoundCube\Service\Config;
use OCA\RoundCube\Service\AuthRoundCube as Authenticator;

/** AJAX endpoints for personal settings. */
class PersonalSettingsController extends Controller
{
  use \OCA\RoundCube\Toolkit\Traits\LoggerTrait;
  use \OCA\RoundCube\Toolkit\Traits\ResponseTrait;

  /** @var \OCP\IUser */
  private $user;

  private $config;

  private $urlGenerator;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    IUserSession $userSession,
    Authenticator $authenticator,
    Config $config,
    IURLGenerator $urlGenerator,
    ILogger $logger,
    IL10N $l10n,
  ) {
    parent::__construct($appName, $request);

    $this->user = $userSession->getUser();

    $this->authenticator = $authenticator;

    $this->config = $config;
    $this->urlGenerator = $urlGenerator;

    $this->logger = $logger;
    $this->l = $l10n;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * @return DataResponse
   *
   * @NoAdminRequired
   */
  public function set()
  {
    $responseData = [];
    foreach (Personal::SETTINGS as $setting) {
      if (!isset($this->request[$setting])) {
        continue;
      }
      $value = trim($this->request[$setting]);
      switch ($setting) {
        case 'emailAddress':
          $message = $this->l->t('Parameter "%s" set to "%s"', [ $setting, $value ]);
          break;
        case 'emailPassword':
          $message = $this->l->t('Parameter "%s" set to given value', [ $setting ]);
          break;
      }
      $this->config->setPersonalValue($setting, $value);
      $responseData[$setting] = [
        'value' => $value,
        'message' => $message,
      ];
    }
    switch (count($responseData)) {
      case 0:
        return self::grumble($this->l->t('Unknown Request'));
      case 1:
        return self::dataResponse(array_values($responseData)[0]);
      default:
        $values = [];
        $messages = [];
        foreach ($responseData as $key => $data) {
          $values[$key] = $data['value'];
          $messages[] = $data['message'];
        }
        return self::dataResponse([ 'value' => $values, 'message' => implode(', ', $messages).'.']);
    }
  }
}
