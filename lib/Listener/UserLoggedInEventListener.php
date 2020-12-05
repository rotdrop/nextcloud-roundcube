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

use OCP\User\Events\UserLoggedInEvent as Event1;
use OCP\User\Events\UserLoggedInWithCookieEvent as Event2;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IL10N;

use OCA\RoundCube\Service\Constants;

class UserLoggedInEventListener implements IEventListener
{
  use \OCA\RoundCube\Traits\LoggerTrait;

  const EVENT = [ Event1::class, Event2::class ];

  /** @var string */
  private $appName;

  /** @var OCP\IRequest */
  private $request;

  /** @var OCA\RoundCube\Service\AuthDokuWiki */
  private $authenticator;

  public function __construct(
    IRequest $request
    , ILogger $logger
    , IL10N $l10n
  ) {
    $this->appName = Constants::APP_NAME;
    $this->request = $request;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function handle(Event $event): void {
    if (!($event instanceOf Event1 && !($event instanceOf Event2))) {
      return;
    }

    if ($this->ignoreRequest($this->request)) {
      return;
    }

    $userName = $event->getUser()->getUID();
    $password = $event->getPassword();
    // do something with it
  }

  /**
   * In order to avoid request ping-pong the auto-login should only be
   * attempted for UI logins.
   */
  private function ignoreRequest(IRequest $request):bool
  {
    if ($request->getHeader('OCS-APIREQUEST') === 'true') {
      $this->logInfo('Ignoring API login');
      return true;
    }
    if (strpos($this->request->getHeader('Authorization'), 'Bearer ') === 0) {
      $this->logInfo('Ignoring API "bearer" auth');
      return true;
    }
    return false;
  }

}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
