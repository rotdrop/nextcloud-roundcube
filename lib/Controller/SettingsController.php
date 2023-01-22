<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *"
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\RoundCube\Controller;

use InvalidArgumentException;

use Psr\Log\LoggerInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;

use OCA\RoundCube\Service\Config;
use OCA\RoundCube\Constants;

/**
 * Settings-controller for both, personal and admin, settings.
 */
class SettingsController extends Controller
{
  use \OCA\RotDrop\Toolkit\Traits\UtilTrait;
  use \OCA\RotDrop\Toolkit\Traits\ResponseTrait;
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  private const ADMIN_SETTING = 'Admin';

  public const EXTERNAL_LOCATION = 'externalLocation';
  public const EMAIL_DEFAULT_DOMAIN = 'emailDefaultDomain';
  public const EMAIL_ADDRESS_CHOICE = 'emailAddressChoice';
  public const EMAIL_ADDRESS_CHOICE_USER_ID = 'userIdEmail';
  public const EMAIL_ADDRESS_CHOICE_USER_PREFERENCES = 'userPreferencesEmail';
  public const EMAIL_ADDRESS_CHOICE_USER_CHOSEN = 'userChosenEmail';
  public const EMAIL_ADDRESS_CHOICE_DEFAULT = self::EMAIL_ADDRESS_CHOICE_USER_CHOSEN;
  public const EMAIL_ADDRESS_CHOICES = [
    self::EMAIL_ADDRESS_CHOICE_USER_ID,
    self::EMAIL_ADDRESS_CHOICE_USER_PREFERENCES,
    self::EMAIL_ADDRESS_CHOICE_USER_CHOSEN,
  ];
  public const FORCE_SSO = 'forceSSO';
  public const SHOW_TOP_LINE = 'showTopLine';
  public const ENABLE_SSL_VERIFY = 'enableSSLVerify';
  public const PERSONAL_ENCRYPTION = 'personalEncryption';

  /**
   * @var array<string, array>
   *
   * Admin settings with r/w flag and default value (booleans)
   */
  const ADMIN_SETTINGS = [
    self::EXTERNAL_LOCATION => [ 'rw' => true, 'default' => null, ],
    self::EMAIL_DEFAULT_DOMAIN => [ 'rw' => true, 'default' => null, ],
    self::EMAIL_ADDRESS_CHOICE => [ 'rw' => true , 'default' => self::EMAIL_ADDRESS_CHOICE_DEFAULT, ],
    self::FORCE_SSO => [ 'rw' => true, 'default' => false, ],
    self::SHOW_TOP_LINE => [ 'rw' => true, 'default' => false, ],
    self::ENABLE_SSL_VERIFY => [ 'rw' => true, 'default' => false, ],
    self::PERSONAL_ENCRYPTION => [ 'rw' => true, 'default' => false, ],
  ];

  public const EMAIL_ADDRESS = 'emailAddress';
  public const EMAIL_PASSWORD = 'emailPassword';

  /**
   * @var array<string, array>
   *
   * Personal settings with r/w flag and default value (booleans)
   */
  const PERSONAL_SETTINGS = [
    self::EMAIL_ADDRESS => [ 'rw' => true, 'default' => null, ],
    self::EMAIL_PASSWORD => [ 'rw' => true, 'default' => null, ],
    self::EMAIL_ADDRESS_CHOICE . self::ADMIN_SETTING => [ 'rw' => false, 'default' => self::EMAIL_ADDRESS_CHOICE_DEFAULT, ],
    self::EMAIL_DEFAULT_DOMAIN . self::ADMIN_SETTING => [ 'rw' => false, 'default' => false, ],
    self::FORCE_SSO . self::ADMIN_SETTING => [ 'rw' => false, 'default' => false, ],
  ];

  /** @var IConfig */
  private $coudConfig;

  /** @var Config */
  private $config;

  /** @var string */
  private $userId;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    $userId,
    LoggerInterface $logger,
    IL10N $l10n,
    IConfig $cloudConfig,
    Config $config,
  ) {
    parent::__construct($appName, $request);
    $this->logger = $logger;
    $this->l = $l10n;
    $this->cloudConfig = $cloudConfig;
    $this->config = $config;
    $this->userId = $userId;
  }
  // phpcs:enable

  /**
   * @param string $setting
   *
   * @param mixed $value
   *
   * @param bool $force
   *
   * @return DataResponse
   *
   * @AuthorizedAdminSetting(settings=OCA\FilesArchive\Settings\Admin)
   * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
   */
  public function setAdmin(string $setting, mixed $value, bool $force = false):DataResponse
  {
    if (!isset(self::ADMIN_SETTINGS[$setting])) {
      return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $setting));
    }
    if (!(self::ADMIN_SETTINGS[$setting]['rw'] ?? false)) {
      return self::grumble($this->l->t('The admin setting "%1$s" is read-only', $setting));
    }
    $oldValue = $this->config->getAppValue(
      $setting,
      self::ADMIN_SETTINGS[$setting]['default'] ?? null,
    );
    $humanValue = null;
    switch ($setting) {
      case self::EXTERNAL_LOCATION:
        if ($value === '') { // ok, reset
          $newValue = null;
          break;
        }
        if ($value[0] == '/') {
          $value = $this->urlGenerator->getAbsoluteURL($value);
        }
        $urlParts = parse_url($value);
        if (empty($urlParts['scheme']) || !preg_match('/https?/', $urlParts['scheme'])) {
          if (empty($urlParts['scheme'])) {
            return self::grumble($this->l->t(
              'Scheme of external URL must be one of "http" or "https", but nothing was specified.'));
          } else {
            return self::grumble($this->l->t(
              'Scheme of external URL must be one of "http" or "https", "%s" given.', [
                $urlParts['scheme'],
              ]));
          }
        }
        if (empty($urlParts['host'])) {
          return self::grumble($this->l->t("Host-part of external URL seems to be empty"));
        }
        $newValue = $value;
        // $this->authenticator->externalURL($value);
        // if (false && $this->authenticator->loginStatus() == Authenticator::STATUS_UNKNOWN) {
        //   return self::grumble($this->l->t("RoundCube instance does not seem to be reachable at %s", [ $value ]));
        // }
        break;
      case self::EMAIL_DEFAULT_DOMAIN:
      case self::EMAIL_ADDRESS_CHOICE:
        $newValue = $value;
        break;
      case self::FORCE_SSO:
      case self::SHOW_TOP_LINE:
      case self::ENABLE_SSL_VERIFY:
      case self::PERSONAL_ENCRYPTION:
        $newValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
        if ($newValue === null) {
          return self::grumble($this->l->t(
            'Value "%1$s" for setting "%2$s" is not convertible to boolean.', [
              $value, $setting,
            ]));
        }
        if ($newValue === (self::ADMIN_SETTINGS[$setting]['default'] ?? false)) {
          $newValue = null;
        } else {
          if ($newValue === true) {
            $humanValue = $this->l->t('true');
          } elseif ($newValue === false) {
            $humanValue = $this->l->t('false');
          }
          $newValue = (int)$newValue;
        }
        break;
      default:
        return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $setting));
    }

    if ($newValue === null) {
      $this->cloudConfig->deleteAppValue($this->appName, $setting);
      $newValue = self::ADMIN_SETTINGS[$setting]['default'] ?? null;
    } else {
      $this->config->setAppValue($setting, $newValue);
    }

    if ($humanValue === null) {
      $humanValue = $newValue;
    }

    return new DataResponse([
      'newValue' => $newValue,
      'oldValue' => $oldValue,
      'humanValue' => $humanValue,
    ]);
  }

  /**
   * @param string $setting
   *
   * @return DataResponse
   *
   * @AuthorizedAdminSetting(settings=OCA\FilesArchive\Settings\Admin)
   */
  public function getAdmin(?string $setting = null):DataResponse
  {
    if ($setting === null) {
      $allSettings = self::ADMIN_SETTINGS;
    } else {
      if (!isset(self::ADMIN_SETTINGS[$setting])) {
        return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $setting));
      }
      $allSettings = [ $setting => self::ADMIN_SETTINGS[$setting] ];
    }
    $results = [];
    foreach (array_keys($allSettings) as $oneSetting) {
      $value = $this->cloudConfig->getAppValue(
        $this->appName,
        $oneSetting,
        self::ADMIN_SETTINGS[$oneSetting]['default'] ?? null);
      $humanValue = $value;
      switch ($oneSetting) {
        case self::EXTERNAL_LOCATION:
        case self::EMAIL_DEFAULT_DOMAIN:
        case self::EMAIL_ADDRESS_CHOICE:
          break;
        case self::FORCE_SSO:
        case self::SHOW_TOP_LINE:
        case self::ENABLE_SSL_VERIFY:
        case self::PERSONAL_ENCRYPTION:
          if ($humanValue !== null) {
            $humanValue = $humanValue ? $this->l->t('true') : $this->l->t('false');
          }
          $value = !!$value;
          break;
        default:
          return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $oneSetting));
      }
      $results[$oneSetting] = $value;
      $results['human' . ucfirst($oneSetting)] = $humanValue;
    }

    if ($setting === null) {
      return new DataResponse($results);
    } else {
      return new DataResponse([
        'value' => $results[$setting],
        'humanValue' => $results['human' . ucfirst($setting)],
      ]);
    }
  }

  /**
   * Set a personal setting value.
   *
   * @param string $setting
   *
   * @param mixed $value
   *
   * @return Response
   *
   * @NoAdminRequired
   */
  public function setPersonal(string $setting, mixed $value):Response
  {
    if (!isset(self::PERSONAL_SETTINGS[$setting])) {
      return self::grumble($this->l->t('Unknown personal setting: "%1$s"', $setting));
    }
    if (!(self::PERSONAL_SETTINGS[$setting]['rw'] ?? false)) {
      return self::grumble($this->l->t('The personal setting "%1$s" is read-only', $setting));
    }
    $oldValue = $this->config->getPersonalValue(
      $setting,
      self::PERSONAL_SETTINGS[$setting]['default'] ?? null,
    );
    switch ($setting) {
      case self::EMAIL_ADDRESS:
      case self::EMAIL_PASSWORD:
        $newValue = $value;
        if (empty($newValue)) {
          $newValue = null;
        }
        break;
      default:
        return self::grumble($this->l->t('Unknown personal setting: "%s".', [ $setting ]));
    }

    if ($newValue === null) {
      $this->cloudConfig->deleteUserValue($this->userId, $this->appName, $setting);
      $newValue = self::PERSONAL_SETTINGS[$setting]['default'] ?? null;
    } else {
      $this->config->setPersonalValue($setting, $newValue);
    }

    switch ($setting) {
      default:
        $humanValue = $newValue;
        break;
    }

    return new DataResponse([
      'newValue' => $newValue,
      'oldValue' => $oldValue,
      'humanValue' => $humanValue,
    ]);
  }

  /**
   * Get one or all personal settings.
   *
   * @param null|string $setting If null get all settings, otherwise just the
   * requested one.
   *
   * @return Response
   *
   * @NoAdminRequired
   */
  public function getPersonal(?string $setting = null):Response
  {
    if ($setting === null) {
      $allSettings = self::PERSONAL_SETTINGS;
    } else {
      if (!isset(self::PERSONAL_SETTINGS[$setting])) {
        return self::grumble($this->l->t('Unknown personal setting: "%1$s"', $setting));
      }
      $allSettings = [ $setting => self::PERSONAL_SETTINGS[$setting] ];
    }
    $results = [];
    foreach (array_keys($allSettings) as $oneSetting) {
      if (str_ends_with($oneSetting, self::ADMIN_SETTING)) {
        $adminKey = substr($oneSetting, 0, -strlen(self::ADMIN_SETTING));
        $value = $this->config->getAppValue(
          $adminKey,
          self::ADMIN_SETTINGS[$adminKey]['default'] ?? null,
        );
      } else {
        $value = $this->config->getPersonalValue(
          $oneSetting,
          self::PERSONAL_SETTINGS[$oneSetting]['default'] ?? null,
        );
      }
      $humanValue = $value;
      switch ($oneSetting) {
        case self::EMAIL_ADDRESS:
        case self::EMAIL_PASSWORD:
        case self::EMAIL_ADDRESS_CHOICE . self::ADMIN_SETTING:
        case self::EMAIL_DEFAULT_DOMAIN . self::ADMIN_SETTING:
          break;
        case self::FORCE_SSO . self::ADMIN_SETTING:
          $value = (bool)(int)$value;
          $humanValue = $value === true ? $this->l->t('true') : $this->l->t('false');
          break;
        default:
          return self::grumble($this->l->t('Unknown personal setting: "%1$s"', $oneSetting));
      }
      $results[$oneSetting] = $value;
      $results['human' . ucfirst($oneSetting)] = $humanValue;
    }

    if ($setting === null) {
      return new DataResponse($results);
    } else {
      return new DataResponse([
        'value' => $results[$setting],
        'humanValue' => $results['human' . ucfirst($setting)],
      ]);
    }
  }

  /**
   * @param string $stringValue
   *
   * @return null|string
   *
   * @throws InvalidArgumentException
   */
  private function parseMemorySize(string $stringValue):?string
  {
    if ($stringValue === '') {
      $stringValue = null;
    }
    if ($stringValue === null) {
      return $stringValue;
    }
    $newValue = $this->storageValue($stringValue);
    if (!is_int($newValue) && !is_float($newValue)) {
      throw new InvalidArgumentException($this->l->t('Unable to parse memory size limit "%s"', $stringValue));
    }
    if (empty($newValue)) {
      $newValue = null;
    }
    return $newValue;
  }
}
