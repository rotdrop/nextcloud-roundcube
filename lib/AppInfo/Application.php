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

namespace OCA\RoundCube\AppInfo;

/*-********************************************************
 *
 * Bootstrap
 *
 */

use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\App;
use OCP\IConfig;
use OCP\IInitialStateService;

/*
 *
 **********************************************************
 *
 * Events and listeners
 *
 */

use OCP\EventDispatcher\IEventDispatcher;
use OCA\RoundCube\Listener\Registration as ListenerRegistration;

/*
 *
 **********************************************************
 *
 */

include_once __DIR__ . '/../../vendor/autoload.php';

/**
 * App entry point.
 */
class Application extends App implements IBootstrap
{
  use \OCA\RotDrop\Toolkit\Traits\AppNameTrait;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(array $urlParams = [])
  {
    $this->appName = $this->getAppInfoAppName(__DIR__);
    parent::__construct($this->appName, $urlParams);
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** @return true */
  public function getAppName():string
  {
    return $this->appName;
  }

  /** {@inheritdoc} */
  public function boot(IBootContext $context): void
  {
    $container = $context->getAppContainer();

    /* @var OCP\IConfig */
    $config = $container->query(IConfig::class);
    $refreshInterval = $config->getAppValue($this->appName, 'authenticationRefreshInterval', 600);
    $showTopLine = $config->getAppValue($this->appName, 'showTopLine', false);

    /* @var OCP\IInitialStateService */
    $initialState = $container->query(IInitialStateService::class);

    /* @var IEventDispatcher $eventDispatcher */
    $dispatcher = $container->query(IEventDispatcher::class);
    $dispatcher->addListener(
      \OCP\AppFramework\Http\TemplateResponse::EVENT_LOAD_ADDITIONAL_SCRIPTS_LOGGEDIN,
      function() use ($initialState, $refreshInterval, $showTopLine) {

        $initialState->provideInitialState(
          $this->appName,
          'initial',
          [
            'appName' => $this->appName,
            'webPrefix' => $this->appName,
            'refreshInterval' => $refreshInterval,
            'showTopLine' => $showTopLine,
          ]
        );

        //\OCP\Util::addScript($this->appName, 'refresh');
      }
    );
  }

  /** {@inheritdoc} */
  public function register(IRegistrationContext $context): void
  {
    ListenerRegistration::register($context);
  }
}
