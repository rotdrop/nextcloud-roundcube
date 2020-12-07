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

namespace OCA\RoundCube\Listener;

use OCP\User\Events\PasswordUpdatedEvent as HandledEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\IL10N;

use OCA\RoundCube\Service\Constants;
use OCA\RoundCube\Service\Config;
use OCA\RoundCube\Service\AuthRoundCube as Authenticator;

class PasswordUpdatedEventListener implements IEventListener
{
  use \OCA\RoundCube\Traits\LoggerTrait;

  const EVENT = HandledEvent::class;

  /** @var string */
  private $appName;

  /** @var \OCA\RoundCube\Service\AuthRoundCube */
  private $authenticator;

  /** @var \OCA\RoundCube\Service\Config */
  private $config;

  public function __construct(
    Authenticator $authenticator
    , Config $config
    , ILogger $logger
    , IL10N $l10n
  ) {
    $this->appName = Constants::APP_NAME;
    $this->authenticator = $authenticator;
    $this->config = $config;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function handle(Event $event): void {
    if (!($event instanceOf HandledEvent)) {
      return;
    }

    $this->config->recryptPersonalValues($event->getPassword());
  }
}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
