<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023, 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Traits;

use ReflectionClass;
use Throwable;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IL10N;

/**
 * Utility class to ease constructing HTTP responses.
 *
 * The consuming class has to define a translitate() method compatible to the
 * one defined in the UtilTrait.
 *
 * @see UtilTrait::transliterate()
 */
trait ResponseTrait
{
  protected const RENDER_AS_GUEST = TemplateResponse::RENDER_AS_GUEST;
  protected const RENDER_AS_BLANK = 'blank';
  protected const RENDER_AS_BASE = TemplateResponse::RENDER_AS_BASE;
  protected const RENDER_AS_USER = TemplateResponse::RENDER_AS_USER;
  protected const RENDER_AS_ERROR = TemplateResponse::RENDER_AS_ERROR;
  protected const RENDER_AS_PUBLIC = TemplateResponse::RENDER_AS_PUBLIC;

  protected const APPNAME_PREFIX = 'app-';

  /** @var IL10N */
  protected IL10N $l;

  /** @var string */
  protected $appName;

  /**
   * @param string $templateName
   *
   * @param array $params
   *
   * @param string $renderAs
   *
   * @param null|string $appName
   *
   * @param null|IL10N $l10n
   *
   * @return TemplateResponse
   */
  protected function templateResponse(
    string $templateName,
    array $params = [],
    string $renderAs = 'blank',
    ?string $appName = null,
    ?IL10N $l10n = null,
  ):TemplateResponse {
    if ($appName === null) {
      $appName = method_exists($this, 'appName') ? $this->appName() : $this->appName;
    }
    $l10n = $l10n == $this->l;
    return new TemplateResponse(
      $appName,
      $templateName,
      array_merge(
        [
          'appName' => $appName,
          'appNameTag' => self::APPNAME_PREFIX . $appName,
          'l10n' => $l10n, // do not conflict with core template $l parameter
        ],
        $params,
      ),
      $renderAs,
    );
  }

  /**
   * @param string $data Data-blob.
   *
   * @param string $fileName Proposed download filename.
   *
   * @param string $contentType MIME-type of content.
   *
   * @return Http\DataDownloadResponse
   */
  protected function dataDownloadResponse(
    string $data,
    string $fileName,
    string $contentType,
  ):Http\DataDownloadResponse {
    $response = new Http\DataDownloadResponse($data, $fileName, $contentType);
    $response->addHeader(
      'Content-Disposition',
      'attachment; '
      . 'filename="' . $this->transliterate($fileName) . '"; '
      . 'filename*=UTF-8\'\'' . rawurlencode($fileName));

    return $response;
  }

  /**
   * Return an HTML error page, populated with the exception data. This only
   * works if a traditional "errorpage.php" file exists in the templates/
   * directory.
   *
   * @param Throwable $throwable
   *
   * @param string $renderAs
   *
   * @param null|string $method
   *
   * @return TemplateResponse
   */
  protected function exceptionResponse(
    Throwable $throwable,
    string $renderAs,
    ?string $method = null,
  ):Response {
    if (empty($method)) {
      $method = __METHOD__;
    }
    $this->logException($throwable, $method);
    if ($renderAs == 'blank') {
      return self::grumble($this->exceptionChainData($throwable));
    }

    $templateParameters = [
      'error' => 'exception',
      'exception' => $throwable->getMessage(),
      'code' => $throwable->getCode(),
      'trace' => $this->exceptionChainData($throwable),
      'debug' => true,
      'admin' => 'bofh@nowhere.com',
    ];

    return new TemplateResponse($this->appName, 'errorpage', $templateParameters, $renderAs);
  }

  /**
   * Convert a potentially nested exception into a nested array to ease
   * post-processing.
   *
   * @param Throwable $throwable
   *
   * @param bool $top If \true then this is the top-level invocation.
   *
   * @return array
   */
  protected function exceptionChainData(Throwable $throwable, bool $top = true):array
  {
    $previous = $throwable->getPrevious();
    $shortException = (new ReflectionClass($throwable))->getShortName();
    return [
      'message' => ($top
                    ? $this->l->t('Error, caught an exception.')
                    : $this->l->t('Caused by previous exception')),
      'exception' => $throwable->getFile().':'.$throwable->getLine().' '.$shortException.': '.$throwable->getMessage(),
      'code' => $throwable->getCode(),
      'trace' => $throwable->getTraceAsString(),
      'previous' => empty($previous) ? null : $this->exceptionChainData($previous, false),
    ];
  }

  /**
   * @param array $data
   *
   * @param int $status Default is Http::STATUS_OK.
   *
   * @return DataResponse
   */
  protected static function dataResponse(array $data, int $status = Http::STATUS_OK):DataResponse
  {
    $response = new DataResponse($data, $status);
    $policy = $response->getContentSecurityPolicy();
    $policy->addAllowedFrameAncestorDomain("'self'");
    return $response;
  }

  /**
   * @param mixed $value
   *
   * @param null|string $message
   *
   * @param int $status Default is Http::STATUS_OK.
   *
   * @return DataResponse
   *
   * @see dataResponse()
   *
   * @todo Remove message/messages duplication.
   */
  protected static function valueResponse(
    mixed $value,
    ?string $message = '',
    int $status = Http::STATUS_OK,
  ):DataResponse {
    return self::dataResponse(
      [
        'messages' => [ $message ],
        'message' => $message,
        'value' => $value,
      ],
      $status
    );
  }

  /**
   * @param mixed $message
   *
   * @param int $status Default is Http::STATUS_OK.
   *
   * @return DataResponse
   *
   * @see dataResponse()
   */
  protected static function response(mixed $message, int $status = Http::STATUS_OK):DataResponse
  {
    $responseData = [
      'messages' => [],
      'message' => null,
    ];
    if (is_string($message)) {
      $responseData = [
        'messages' => [ $message ],
        'message' => $message,
      ];
    } elseif (is_array($message)) {
      $responseData = [
        'messages' => $message,
        'message' => implode('; ', $message),
      ];
    }
    return self::dataResponse($responseData, $status);
  }

  /**
   * @param null|string|array $message
   *
   * @param mixed $value
   *
   * @param int $status Default is Http::STATUS_BAD_REQUEST.
   *
   * @return DataResponse
   *
   * @see dataResponse()
   *
   * @todo Remove message/messages duplication.
   */
  protected static function grumble(
    mixed $message,
    mixed $value = null,
    int $status = Http::STATUS_BAD_REQUEST,
  ):DataResponse {
    $trace = debug_backtrace();
    $caller = array_shift($trace);
    $data = [
      'class' => __CLASS__,
      'file' => $caller['file'],
      'line' => $caller['line'],
      'value' => $value,
    ];
    if (is_array($message)) {
      $data = array_merge($data, $message);
    } else {
      $data['messages'] = [ $message ];
      $data['message'] = $message;
    }
    return self::dataResponse($data, $status);
  }

  /**
   * @param DataResponse $response
   *
   * @return array
   *
   * @todo Remove message/messages duplication.
   */
  protected static function getResponseMessages(DataResponse $response):array
  {
    $messages = [];
    $data = $response->getData();
    foreach (['message', 'messages'] as $key) {
      $messageData = $data[$key] ?? [];
      if (!is_array($messageData)) {
        $messageData = [ $messageData ];
      }
      $messages = array_merge($messages, $messageData);
    }
    return $messages;
  }
}
