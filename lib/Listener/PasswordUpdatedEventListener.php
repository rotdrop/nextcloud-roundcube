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

namespace OCA\RoundCube\Listener;

use OCP\User\Events\PasswordUpdatedEvent as HandledEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\AppFramework\IAppContainer;
use OCP\ILogger;
use OCP\IL10N;

use OCA\RoundCube\Service\Config;
use OCA\RoundCube\Service\AuthRoundCube as Authenticator;

class PasswordUpdatedEventListener implements IEventListener
{
  use \OCA\RoundCube\Traits\LoggerTrait;

  const EVENT = HandledEvent::class;

  /** @var IAppContainer */
  private $appContainer;

  public function __construct(IAppContainer $appContainer)
  {
    $this->appContainer = $appContainer;
  }

  public function handle(Event $event): void {
    if (!($event instanceOf HandledEvent)) {
      return;
    }

    $this->logger = $this->appContainer->get(ILogger::class);
    try {
      /** @var Config */
      $config = $this->appContainer->get(Config::class);
      $config->recryptPersonalValues($event->getPassword());
    } catch (\Throwable $t) {
      $this->logException($t, 'Unable to recrypt personal values');
    }
  }
}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
