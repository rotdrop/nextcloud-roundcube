<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\RotDrop\Toolkit\Listener;

use Throwable;
use Psr\Log\LoggerInterface;

use OC\Log\LogDetails;
use OC\SystemConfig;

use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Log\BeforeMessageLoggedEvent as HandledEvent;
use OCP\Server;

/**
 * Listener foŕ log-entries in order to provide the formatted log-entry to
 * controllers.
 */
class BeforeMessageLoggedEventListener implements IEventListener
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;
  const EVENT = HandledEvent::class;

  /**
   * @param IAppContainer $appContainer The only argument in order to have a
   * small CTOR footprint.
   */
  public function __construct(
    protected IAppContainer $appContainer,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.Superglobals)
   */
  public function handle(Event $event): void
  {
    /** @var HandledEvent $event */
    if (!$event instanceof HandledEvent) {
      return;
    }

    /** @var IEventDispatcher $eventDispatcher */
    $eventDispatcher = Server::get(IEventDispatcher::class);
    // This must be a one-shot listener, even more as NC now uses lazy ghosts.
    $eventDispatcher->removeListener(self::EVENT, [$this, 'handle']);

    // The following line triggers the initialization of this lazy ghost.
    $this->logger = $this->appContainer->get(LoggerInterface::class);

    $appName = $this->appContainer->get('appName');

    if ($event->getApp() != $appName) {
      return;
    }
    $data = $event->getMessage();
    $callback = $data[$appName]['callback'] ?? null;
    if (!is_callable($callback)) {
      return;
    }
    unset($data[$appName]['callback']);
    try {
      $systemConfig = $this->appContainer->get(SystemConfig::class);
      $logDetails = new class($systemConfig) extends LogDetails {
        /** {@inheritdoc} */
        public function __construct(SystemConfig $systemConfig)
        {
          parent::__construct($systemConfig);
        }
      };
      $logEntry = $logDetails->logDetails($appName, $data, $event->getLevel());
      $callback($logEntry);
    } catch (Throwable $t) {
      $this->logException($t);
    }
  }
}
