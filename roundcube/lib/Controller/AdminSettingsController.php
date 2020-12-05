<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @author Claus-Justus Heine
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

use OCP\IRequest;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;
use OCP\IL10N;

use OCA\RoundCube\Settings\Admin;
use OCA\RoundCube\Service\AuthRoundCube as Authenticator;

class AdminSettingsController extends Controller
{
  use \OCA\RoundCube\Traits\LoggerTrait;
  use \OCA\RoundCube\Traits\ResponseTrait;

  private $userId;

  private $config;

  private $urlGenerator;

  public function __construct(
    $appName
    , IRequest $request
    , Authenticator $authenticator
    , IConfig $config
    , IURLGenerator $urlGenerator
    , $UserId
    , ILogger $logger
    , IL10N $l10n
  ) {
    parent::__construct($appName, $request);

    $this->authenticator = $authenticator;

    $this->config = $config;
    $this->urlGenerator = $urlGenerator;

    $this->userId = $UserId;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function set()
  {
    $responseData = [];
    foreach (Admin::SETTINGS as $setting => $default) {
      $this->logInfo("$setting: ".$this->request[$setting]);
      if (!isset($this->request[$setting])) {
        continue;
      }
      $value = trim($this->request[$setting]);
      $this->logInfo("Got setting ".$setting.": ".$value);
      switch ($setting) {
        case 'externalLocation':
          if ($value === '') { // ok, reset
            break;
          }
          if ($value[0] == '/') {
            $value = $this->urlGenerator->getAbsoluteURL($value);
          }
          $urlParts = parse_url($value);
          if (empty($urlParts['scheme']) || !preg_match('/https?/', $urlParts['scheme'])) {
            return self::grumble($this->l->t("Scheme of external URL must be one of `http' or `https', `%s' given.", [$urlParts['scheme']]));
          }
          if (empty($urlParts['host'])) {
            return self::grumble($this->l->t("Host-part of external URL seems to be empty"));
          }
          $this->authenticator->externalURL($value);
          if ($this->authenticator->loginStatus() == Authenticator::STATUS_UNKNOWN) {
            return self::grumble($this->l->t("RoundCube instance does not seem to be reachable at %s", [ $value ]));
          }
          break;
        case 'authenticationRefreshInterval':
          $realValue = filter_var($value, FILTER_VALIDATE_INT, ['min_range' => 0]);
          if ($realValue === false) {
            return self::grumble($this->l->t('Value "%1$s" for set "%2$s" is not in the allowed range.', [$value, $setting]));
          }
          $value = $realValue;
          break;
      }
      $this->config->setAppValue($this->appName, $setting, $value);
      $responseData[$setting] = [
        'value' => $value,
        'message' => $this->l->t('Parameter %s set to "%s"', [ $setting, $value ]),
      ];
    }
    switch (count($responseData)) {
      case 0:
        return new DataResponse([ 'message' => $this->l->t('Unknown Request') ], Http::STATUS_BAD_REQUEST);
      case 1:
        return new DataResponse(array_values($responseData)[0]);
      default:
        $values = [];
        $messages = [];
        foreach ($responseData as $key => $data) {
          $values[$key] = $data['value'];
          $messages[] = $data['message'];
        }
        return new DataResponse([ 'value' => $values, 'message' => implode(', ', $messages).'.']);
    }
  }
}
