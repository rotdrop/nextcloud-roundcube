<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2022, 2023 Claus-Justus Heine
 * @license AGPL-3.0-or-later
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

use Throwable;

use OCP\User\Events\BeforeUserLoggedOutEvent as HandledEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\AppFramework\IAppContainer;
use OCP\ILogger;

use OCA\RoundCube\Service\AuthRoundCube;

/** Log the current user out of Roundcube when it logs out of Nextcloud. */
class BeforeUserLoggedOutEventListener implements IEventListener
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const EVENT = HandledEvent::class;

  /** @var IAppContainer */
  private $appContainer;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(IAppContainer $appContainer)
  {
    $this->appContainer = $appContainer;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** {@inheritdoc} */
  public function handle(Event $event): void
  {
    if (!($event instanceof HandledEvent)) {
      return;
    }

    $this->logger = $this->appContainer->get(ILogger::class);

    try {
      /** @var AuthRoundCube $authenticator */
      $authenticator = $this->appContainer->get(AuthRoundCube::class);
      $authenticator->logout();
    } catch (Throwable $t) {
      $this->logException($t, 'Unable to log out of roundcube app.');
    }
  }
}
