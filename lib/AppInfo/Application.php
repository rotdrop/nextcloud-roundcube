<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
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

class Application extends App implements IBootstrap
{
  /** @var string */
  protected $appName;

  public function __construct (array $urlParams = []) {
    $infoXml = new \SimpleXMLElement(file_get_contents(__DIR__ . '/../../appinfo/info.xml'));
    $this->appName = (string)$infoXml->id;
    parent::__construct($this->appName, $urlParams);
  }

  public function getAppName()
  {
    return $this->appName;
  }

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
      function() use ($initialState, $refreshInterval) {

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

  // Called earlier than boot, so anything initialized in the
  // "boot()" method must not be used here.
  public function register(IRegistrationContext $context): void
  {
    ListenerRegistration::register($context);
  }

}
