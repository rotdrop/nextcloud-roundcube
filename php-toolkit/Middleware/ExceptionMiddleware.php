<?php
/**
 * Archive Manager for Nextcloud
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023, 2024, 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Middleware;

use Exception;
use ReflectionMethod;
use Throwable;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use OCA\RotDrop\Toolkit\Exceptions\EnduserNotificationException;
use OCA\RotDrop\Toolkit\Attributes;
use OCA\RotDrop\Toolkit\Response\PreRenderedTemplateResponse;
use OCA\RotDrop\Toolkit\AppInfo\AbstractApplication as App;

/**
 * Turn an exception into a data response which can be parsed by the
 * frontend. Can be disabled by the DoNotCatchExceptions attribute.
 */
class ExceptionMiddleware extends Middleware
{
  use \OCA\RotDrop\Toolkit\Traits\HasAnnotationOrAttributeTrait;
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    protected ContainerInterface $appContainer,
    protected IControllerMethodReflector $reflector,
    protected IL10N $l,
    protected IRequest $request,
    protected LoggerInterface $logger,
  ) {
  }
  // phpcs:enable

  /**
   * {@inheritdoc}
   *
   * This is called just before the NC core would call Response::render()
   * anyway. The goal is to catch exception during rendering of
   * TemplateReponse instances. Normally an exception thrown during render
   * ends up in the top-level exception handler which then renders the core
   * exception template, which may be undesirable in certain contexts.
   */
  public function afterController($controller, $methodName, Response $response)
  {
    $reflectionMethod = new ReflectionMethod($controller, $methodName);
    if ($this->hasAnnotationOrAttribute($reflectionMethod, Attributes\DoNotCatchExceptions::class)
        || !($response instanceof PreRenderedTemplateResponse)) {
      return $response;
    }
    try {
      $response->preRender();
    } catch (Throwable $t) {
      return $this->afterThrowable($controller, $methodName, $t, wrap: true);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * Convert a EnduserNotificationException into an error response.
   */
  public function afterException($controller, $methodName, Exception $exception)
  {
    return $this->afterThrowable($controller, $methodName, $exception, wrap: false);
  }

  /**
   * @param Controller $controller
   *
   * @param string $methodName
   *
   * @param Throwable $exception
   *
   * @param bool $wrap Whether to wrap any exception into an EnduserNotificationException.
   *
   * @return null|JSONResponse
   */
  protected function afterThrowable(Controller $controller, string $methodName, Throwable $exception, bool $wrap)
  {
    $reflectionMethod = new ReflectionMethod($controller, $methodName);
    if ($this->hasAnnotationOrAttribute($reflectionMethod, Attributes\DoNotCatchExceptions::class)) {
      throw $exception;
    }
    if (!($exception instanceof EnduserNotificationException)) {
      if (!$wrap) {
        throw $exception;
      }
      try {
        $folderPrefix = $this->appContainer->get(App::APP_ROOT_FOLDER);
      } catch (Throwable) {
        // ignore
        $folderPrefix = \OC::$SERVERROOT;
      }

      $originalException = $exception;
      $exceptionMessage = $this->l->t(
        'Unable to serve request to "%1$s": %2$s',
        [ $this->request->getPathInfo(), $originalException->getMessage() ],
      );
      $exceptionMessage = str_replace($folderPrefix, '...', $exceptionMessage);

      $context = [];
      switch (get_class($originalException)) {
        case InvalidArgumentException::class:
          $httpStatusCode = Http::STATUS_BAD_REQUEST;
          break;
        default:
          $httpStatusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
          break;
      }

      $exception = new EnduserNotificationException(
        $exceptionMessage, 0, $originalException,
        httpStatusCode: $httpStatusCode,
        context: $context,
      );
    }
    $httpStatusCode = $exception->getHttpStatusCode();
    $context = array_merge_recursive(
      [ 'httpStatusCode' => $httpStatusCode ],
      $exception->getContext() ?? [],
    );
    $logEntry = $this->logException(
      $exception,
      message: $exception->getMessage(),
      context: $context,
      returnLogEntry: true,
      shift: PHP_INT_MIN, // do not decorate with prefix
    );
    if (is_array($logEntry)) {
      array_walk_recursive($logEntry, fn(&$value) => $value = str_replace(\OC::$SERVERROOT, '', iconv('UTF-8', 'UTF-8//IGNORE', $value)));
    } else {
      $this->logError('Log entry is null');
    }
    $this->logDebug('LOG_ENTRY ' . print_r($logEntry, true));
    // the frontend shows the "messages" array to the user
    return new JSONResponse(
      array_merge($logEntry ?? [], [ 'messages' => [ $exception->getMessage() ] ]),
      $httpStatusCode,
    );
  }
}
