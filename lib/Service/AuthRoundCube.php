<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
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
namespace OCA\RoundCube\Service;

use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IL10N;

use OCA\RoundCube\AppInfo\Application;

/**
 * This class provides the login to RC server using curl.
 */
class AuthRoundCube
{
  use \OCA\RoundCube\Traits\LoggerTrait;

  const COOKIE_RC_SESSID    = 'roundcube_sessid';
  const COOKIE_RC_SESSAUTH  = 'roundcube_sessauth';

  const STATUS_UNKNOWN = 0;
  const STATUS_LOGGED_OUT = -1;
  const STATUS_LOGGED_IN = 1;

  const FORM_URLENCODED = 'application/x-www-form-urlencoded';

  /** @var string */
  private $appName;

  /** @var \OCP\IConfig */
  private $config;

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  /** @var bool */
  private $enableSSLVerify;

  private $proto;
  private $host;
  private $port;
  private $path;

  private $loginStatus;  // 0 unknown, -1 logged off, 1 logged on

  private $rcRequestToken;
  private $rcSessionId;
  private $rcSessionAuth;

  public function __construct(
    Application $app
    , Config $config
    , IURLGenerator $urlGenerator
    , $userId
    , ILogger $logger
    , IL10N $l10n
  ) {
    $this->appName = $app->getAppName();
    $this->userId = $userId;
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
    $this->logger = $logger;
    $this->l = $l10n;

    $this->enableSSLVerify = $this->config->getAppValue('enableSSLVerify', true);

    $location = $this->config->getAppValue('externalLocation');
    if ($location[0] == '/') {
      $url = $this->urlGenerator->getAbsoluteURL($location);
    } else {
      $url = $location;
    }

    $urlParts = parse_url($url);

    $this->proto = $urlParts['scheme'];
    $this->host  = $urlParts['host'];
    $this->port  = isset($urlParts['port']) ? ':'.$urlParts['port'] : '';
    $this->path  = $urlParts['path'];

    $this->loginStatus = self::STATUS_UNKNOWN;

    $this->rcRequestToken = null;
    $this->rcSessionId = null;
    $this->rcSessionAuth = null;
  }

  /**
   * Return the name of the app.
   */
  public function getAppName(): string
  {
    return $this->appName;
  }

  /**
   * Return the URL for use with an iframe or object tag
   */
  public function externalURL($url = null)
  {
    if (!empty($url)) {
      if ($url[0] == '/') {
        $url = $this->urlGenerator->getAbsoluteURL($url);
      }

      $urlParts = parse_url($url);

      $this->proto = $urlParts['scheme'];
      $this->host  = $urlParts['host'];
      $this->port  = isset($urlParts['port']) ? ':'.$urlParts['port'] : '';
      $this->path  = $urlParts['path'];
    }

    if (empty($this->proto) || empty($this->host)) {
      return null;
    }
    return $this->proto.'://'.$this->host.$this->port.$this->path;
  }

  /**
   * Log  into the external application.
   *
   * @param $username Login name
   *
   * @param $password credentials
   *
   * @return true if successful, false otherwise.
   */
  public function login(string $username, string $password)
  {
    $this->logInfo('user: '.$username.' password: '.$password[0]);
    // End previous session:
    // Delete cookies sessauth & sessid by expiring them.
    setcookie(self::COOKIE_RC_SESSID, "-del-", 1, "/", "", true, true);
    setcookie(self::COOKIE_RC_SESSAUTH, "-del-", 1, "/", "", true, true);
    // Get login page, extracts sessionID and token.
    $loginPageObj = $this->sendRequest("?_task=login", "GET");
    if ($loginPageObj === false) {
      $this->logError("Could not get login page.");
      return false;
    }
    $cookies = self::parseCookies($loginPageObj['headers']['set-cookie']);
    if (isset($cookies[self::COOKIE_RC_SESSID])) {
      $this->rcSessionId = $cookies[self::COOKIE_RC_SESSID];
    }
    // Get input values from login form and prepare data to send.
    $inputs = self::parseInputs($loginPageObj['html']);
    $this->rcRequestToken = $inputs['_token']['value'];
    $data = [
      "_token"    => $inputs["_token"]["value"],
      "_task"     => "login",
      "_action"   => "login",
      "_timezone" => $inputs["_timezone"]["value"],
      "_url"      => $inputs["_url"]["value"],
      "_user"     => $username,
      "_pass"     => $password
    ];
    // Post login form.
    $loginAnswerObj = $this->sendRequest("?_task=login&_action=login", "POST", $data);
    if ($loginAnswerObj === false) {
      $this->logError("Could not get login response.");
      return false;
    }
    // Set cookies sessauth and sessid.
    $cookiesLogin = self::parseCookies($loginAnswerObj['headers']['set-cookie']);
    $inputsLogin = self::parseInputs($loginAnswerObj['html']);
    $this->rcRequestToken = $inputs['_token']['value'];
    if (isset($cookiesLogin[self::COOKIE_RC_SESSID]) &&
        $cookiesLogin[self::COOKIE_RC_SESSID] !== "-del-") {
      $this->rcSessionId = $cookiesLogin[self::COOKIE_RC_SESSID];
      setcookie(self::COOKIE_RC_SESSID, $this->rcSessionId,
                0, "/", "", true, true);
    }
    if (isset($cookiesLogin[self::COOKIE_RC_SESSAUTH]) &&
        $cookiesLogin[self::COOKIE_RC_SESSAUTH] !== "-del-") {
      // We received a sessauth => logged in!
      $this->rcSessionAuth = $cookiesLogin[self::COOKIE_RC_SESSAUTH];
      setcookie(self::COOKIE_RC_SESSAUTH, $this->rcSessionAuth,
                0, "/", "", true, true);
      return true;
    }
    // Check again whether input fields of login form exist.
    $inputsLogin = self::parseInputs($loginAnswerObj['html']);
    if (empty($inputsLogin) || !isset($inputsLogin["_user"]) || !isset($inputsLogin["_pass"])) {
      return true; // It shouldn't get here ever.
    } else {
      $this->logError("Could not login.");
      return false;
    }
  }

  /**
   * Logoff from the external application.
   *
   * @return true if logout succeeded
   */
  public function logout()
  {
    $data = [
      '_action' => 'logout',
      '_task' => 'logout',
    ];
    if ($this->rqRequestToken) {
      $data['_token'] = $this->rqRequestToken;
    }
    $logoutPageObj = $this->sendRequest('', 'POST', $data);
    if ($logoutPageObj === false) {
      $this->logError("Could not trigger logout.");
      return false;
    }

    setcookie(self::COOKIE_RC_SESSID, "-del-", 1, "/", "", true, true);
    setcookie(self::COOKIE_RC_SESSAUTH, "-del-", 1, "/", "", true, true);

    return true;
  }

  /**
   * @param string $text The text where to look for input fields.
   * @return array [name => [key, value]] Input fields indexed by name.
   */
  private static function parseInputs($text) {
    $inputs = array();
    if (preg_match_all('/<input ([^>]*)>/i', $text, $inputMatches)) {
      foreach ($inputMatches[1] as $input) {
        if (preg_match_all('/(\w+)="([^"]*)"/i', $input, $keyvalMatches)) {
          $tmp = array();
          $name = "";
          foreach ($keyvalMatches[1] as $index => $key) {
            if ($key === "name") {
              $name = $keyvalMatches[2][$index];
            } else {
              $tmp[$key] = $keyvalMatches[2][$index];
            }
          }
          if ($name !== "") {
            $inputs[$name] = $tmp;
          }
        }
      }
    }
    return $inputs;
  }

  /**
   * Send request using cURL.
   *
   * @param string $rcQuery  Query string to append to rcInternalAddress
   *                       (Example: "?_task=login").
   * @param string $method POST or GET request.
   * @param string $data   Data to send.
   * @return array ['headers' => [headers], 'html' => html]
   */
  private function sendRequest($rcQuery = '', $method = 'POST', $data = null)
  {
    $response = false;

    if (!empty($rcQuery) && $rcQuery[0] != '/') {
      $rcQuery = '/'.$rcQuery;
    }
    $rcQuery = $this->externalURL().$rcQuery;
    $this->logDebug("URL: '$rcQuery'.");
    try {
      $curl = curl_init();
      // general settings
      $curlOpts = array(
        CURLOPT_URL            => $rcQuery,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FRESH_CONNECT  => true
      );
      if ($method === 'POST') {
        $curlOpts[CURLOPT_POST] = true;
        if ($data) {
          $postData = http_build_query($data);
          $curlOpts[CURLOPT_POSTFIELDS] = $postData;
          $curlOpts[CURLOPT_TIMEOUT] = 60;
          $curlOpts[CURLOPT_HTTPHEADER] = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding: identity',
            'Content-Type: ' . self::FORM_URLENCODED,
            'Content-Length: ' . strlen($postData),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
          );
        }
      } else {
        $curlOpts[CURLOPT_HTTPGET] = true;
      }
      $cookies = "";
      if ($this->rcSessionId !== "") {
        $cookies .= self::COOKIE_RC_SESSID . "={$this->rcSessionId}; ";
      }
      if ($this->rcSessionAuth !== "") {
        $cookies .= self::COOKIE_RC_SESSAUTH . "={$this->rcSessionAuth}; ";
      }
      $curlOpts[CURLOPT_COOKIE] = rtrim($cookies, "; ");
      if (!$this->enableSSLVerify) {
        $this->logWarn("Disabling SSL verification.");
        $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
        $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
      }
      curl_setopt_array($curl, $curlOpts);

      $rawResponse = curl_exec($curl);

      // Error handling
      $curlErrorNum   = curl_errno($curl);
      $curlError      = curl_error($curl);
      $headerSize     = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      $respHttpCode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      $this->logDebug("Got the following HTTP Status Code: ($respHttpCode) $curlErrorNum: $curlError");
      if ($curlErrorNum === CURLE_OK && $respHttpCode < 400) {
        $response = self::splitResponse($rawResponse, $headerSize);
      } else {
        $this->logWarn("Opening url '$rcQuery' failed with '$curlError'");
      }
      curl_close($curl);
    } catch (Exception $e) {
      $this->logWarn("URL '$rcQuery' open failed.");
    }
    return $response;
  }

  /**
   * Splits a curl response into headers and html.
   * @param string $response
   * @param int    $headerSize
   * @return array ['headers' => [headers], 'html' => html]
   */
  private static function splitResponse($response, $headerSize) {
    $headers = $html = "";
    if ($headerSize) {
      $headers = substr($response, 0, $headerSize);
      $html    = substr($response, $headerSize);
    } else {
      $hh = explode("\r\n\r\n", $response, 2);
      $headers = $hh[0];
      $html    = $hh[1];
    }
    $headersArray = self::parseResponseHeaders($headers);
    return [
      'headers' => $headersArray,
      'html'    => $html
    ];
  }

  /**
   * @param string $rawHeaders String of headers from a curl response.
   * Example:
   * HTTP/1.1 200 OK
   * Date: Tue, 19 Mar 2019 15:19:28 GMT
   * Server: Apache/2.2.22 (Debian)
   * X-Powered-By: PHP/5.4.45-0+deb7u7
   * Expires: Tue, 19 Mar 2019 15:19:28 GMT
   * Cache-Control: private, no-cache, no-store, must-revalidate, post-check=0, pre-check=0
   * Pragma: no-cache
   * Last-Modified: Tue, 19 Mar 2019 15:19:28 GMT
   * X-DNS-Prefetch-Control: off
   * Content-Language: es
   * Vary: Accept-Encoding
   * Content-Type: text/html; charset=UTF-8
   * Set-Cookie: roundcube_sessid=h5s3o6qasjhbd6bq4gfrl5amh2; path=/; secure; HttpOnly
   * Transfer-Encoding: chunked
   *
   * @return array ['name0' => [header0:0, header0:1], 'name1' => [header1:0], ...]
   */
  private static function parseResponseHeaders($rawHeaders) {
    $responseHeaders = array();
    $headerLines = explode("\r\n", trim($rawHeaders));
    foreach ($headerLines as $header) {
      if ($header && is_string($header) && strpos($header, ':') !== false) {
        list($name, $value) = explode(': ', $header, 2);
        $name = strtolower($name);
        if (!isset($responseHeaders[$name])) {
          $responseHeaders[$name] = [ $value ];
        } else {
          $responseHeaders[$name][] = $value;
        }
      }
    }
    return $responseHeaders;
  }

  /**
   * @param array $cookieHeaders ['name=value; text', ...]
   * @return array ['name' => 'value', ...]
   */
  private static function parseCookies($cookieHeaders) {
    $cookies = array();
    if (is_array($cookieHeaders)) {
      foreach ($cookieHeaders as $ch) {
        if (preg_match('/^([^=]+)=([^;]+);/i', $ch, $match)) {
          if ($match[1] !== "" && $match[2] !== "" && strlen($match[2]) > 5) {
            $cookies[$match[1]] = $match[2];
          }
        }
      }
    }
    return $cookies;
  }
}
