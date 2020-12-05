<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 * it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\RoundCube\AppInfo;

/**********************************************************
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
use OCA\RoundCube\Service\Constants;

class Application extends App implements IBootstrap
{

  public function __construct (array $urlParams = []) {
    parent::__construct(Constants::APP_NAME, $urlParams);
  }

  public function boot(IBootContext $context): void
  {
    $container = $context->getAppContainer();

    /* @var OCP\IConfig */
    $config = $container->query(IConfig::class);
    $refreshInterval = $config->getAppValue(Constants::APP_NAME, 'authenticationRefreshInterval', 600);

    /* @var OCP\IInitialStateService */
    $initialState = $container->query(IInitialStateService::class);

    /* @var IEventDispatcher $eventDispatcher */
    $dispatcher = $container->query(IEventDispatcher::class);
    $dispatcher->addListener(
      \OCP\AppFramework\Http\TemplateResponse::EVENT_LOAD_ADDITIONAL_SCRIPTS_LOGGEDIN,
      function() use ($initialState, $refreshInterval) {

        $initialState->provideInitialState(
          Constants::APP_NAME,
          'initial',
          [
            'appName' => Constants::APP_NAME,
            'webPrefix' => Constants::APP_PREFIX,
            'refreshInterval' => $refreshInterval,
          ]
        );

        \OCP\Util::addScript(Constants::APP_NAME, 'refresh');
      }
    );
  }

  // Called earlier than boot, so anything initialized in the
  // "boot()" method must not be used here.
  public function register(IRegistrationContext $context): void
  {
    ListenerRegistration::register($context);
  }

}
