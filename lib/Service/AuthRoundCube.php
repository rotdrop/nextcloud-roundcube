<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020 - 2025 Claus-Justus Heine
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

namespace OCA\RoundCube\Service;

use DOMAttr;
use DOMDocument;
use DOMXPath;

use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface as ILogger;

use OCA\RoundCube\AppInfo\Application;
use OCA\RoundCube\Service\Config;
use OCA\RoundCube\Toolkit\Service\AppPasswordService;

/**
 * This class provides the login to RC server using curl.
 */
class AuthRoundCube
{
  use \OCA\RoundCube\Toolkit\Traits\LoggerTrait;

  const COOKIE_RC_SESSID    = 'roundcube_sessid';
  const COOKIE_RC_SESSAUTH  = 'roundcube_sessauth';

  const STATUS_UNKNOWN = 0;
  const STATUS_LOGGED_OUT = -1;
  const STATUS_LOGGED_IN = 1;

  const FORM_URLENCODED = 'application/x-www-form-urlencoded';

  /** @var string */
  private $appName;

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

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private ?string $userId,
    private Config $config,
    private IRequest $request,
    private IURLGenerator $urlGenerator,
    protected AppPasswordService $appPasswordService,
    protected ILogger $logger,
  ) {
    $this->enableSSLVerify = $this->config->getAppValue(Config::ENABLE_SSL_VERIFY);

    $location = $this->config->getAppValue(Config::EXTERNAL_LOCATION);
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

    // If the user's web-browser already provides the necessary cookies, then
    // use them.
    $this->rcSessionAuth = $this->request->cookies[self::COOKIE_RC_SESSAUTH] ?? null;
    $this->rcSessionId = $this->request->cookies[self::COOKIE_RC_SESSID] ?? null;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * @return string Return the name of the app.
   */
  public function getAppName():string
  {
    return $this->appName;
  }

  /**
   * Return the URL for use with an iframe or object tag
   *
   * @param null|string $url
   *
   * @return null|string
   */
  public function externalURL(?string $url = null):?string
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
   * Check whether we are already logged in by the cookies sent to us by the
   * user's web browser.
   *
   * @return bool
   */
  public function checkLoggedIn():bool
  {
    $mailPageObj = $this->sendRequest("?_task=mail", "GET");
    $inputs = self::parseInputs($mailPageObj['html']);
    if (is_array($inputs['_task'] ?? null) && $inputs['_task']['value'] == 'login'
        && is_array($inputs['_action']) && $inputs['_action']['value'] == 'login') {
      return false;
    }
    if (is_array($inputs['_token']) && (is_array($inputs['_q']) && ($inputs['_q']['id'] ?? null) == 'mailsearchform')) {
      $this->rcRequestToken = $inputs['_token']['value'];
      setcookie(
        self::COOKIE_RC_SESSID, $this->rcSessionId,
        0, "/", "", true, true);
      setcookie(
        self::COOKIE_RC_SESSAUTH, $this->rcSessionAuth,
        0, "/", "", true, true);
      return true;
    }
    return false;
  }

  /**
   * Log  into the external application.
   *
   * @param string $username Login name.
   *
   * @param string $password credentials.
   *
   * @return bool true if successful, false otherwise.
   */
  public function login(string $username, string $password):bool
  {
    if ($this->checkLoggedIn()) {
      return true;
    }
    // $this->logInfo('user: ' . $username . ' password: ' . $password);
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
    $cookies = self::parseCookies($loginPageObj['headers']['set-cookie'] ?? null);
    if (isset($cookies[self::COOKIE_RC_SESSID])) {
      $this->rcSessionId = $cookies[self::COOKIE_RC_SESSID];
    }
    // Get input values from login form and prepare data to send.
    $inputs = self::parseInputs($loginPageObj['html']);
    $this->rcRequestToken = $inputs['_token']['value'];
    // $this->logInfo('REQUEST TOKEN UPDATE ' . $this->rcRequestToken);
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
    $cookiesLogin = self::parseCookies($loginAnswerObj['headers']['set-cookie'] ?? null);

    // there should be a location header with updated request token ...
    $location = $loginAnswerObj['headers']['location'] ?? null;
    if ($location === null || !is_array($location)) {
      $this->logError("Could not get login redirect header.");
      return false;
    }
    $location = reset($location);
    $locationParams = [];
    parse_str(parse_url($location, PHP_URL_QUERY), $locationParams);
    /// $this->logInfo('REDIRECT HEADER ' . print_r($locationParams, true));
    if (empty($locationParams['_token'])) {
      $this->logError('Could not update request token after login.');
      return false;
    }
    $this->rcRequestToken = $locationParams['_token'];

    if (isset($cookiesLogin[self::COOKIE_RC_SESSID]) &&
        $cookiesLogin[self::COOKIE_RC_SESSID] !== "-del-") {
      $this->rcSessionId = $cookiesLogin[self::COOKIE_RC_SESSID];
      setcookie(
        self::COOKIE_RC_SESSID, $this->rcSessionId,
        0, "/", "", true, true);
    }
    if (isset($cookiesLogin[self::COOKIE_RC_SESSAUTH]) &&
        $cookiesLogin[self::COOKIE_RC_SESSAUTH] !== "-del-") {
      // We received a sessauth => logged in!
      $this->rcSessionAuth = $cookiesLogin[self::COOKIE_RC_SESSAUTH];
      setcookie(
        self::COOKIE_RC_SESSAUTH, $this->rcSessionAuth,
        0, "/", "", true, true);
      return true;
    }
    // Check again whether input fields of login form exist.
    $inputsLogin = self::parseInputs($loginAnswerObj['html'] ?? null);
    if (empty($inputsLogin) || !isset($inputsLogin["_user"]) || !isset($inputsLogin["_pass"])) {
      return true; // It shouldn't get here ever.
    } else {
      $this->logError("Could not login.");
      return false;
    }
  }

  /**
   * For the case were the email account differs from the cloud account we try
   * to configure the RCM CardDAV plugin with an automatically generated app
   * password.
   *
   * @return null|bool \false on error, \null if unconfigured, \true on
   * success.
   */
  public function cardDavConfig():?bool
  {
    $configTag = $this->config->getAppValue(Config::CARDDAV_PROVISIONG_TAG, null);
    if (empty($configTag)) {
      return null; // unconfigured
    }

    $result = $this->sendRequest('?_task=settings&_action=plugin.carddav', 'GET');
    if ($result === null) {
      $this->logError('Unable load RoundCube CardDAV.');
      return false;
    }
    $domDoc = new DOMDocument('1.0', 'UTF-8');
    $domDoc->encoding = 'UTF-8';
    $domDoc->loadHTML($result['html']);
    $xpath = new DOMXPath($domDoc);
    /** @var DOMAttr $item */
    foreach ($xpath->query('//li[contains(@id, "rcmli_acc") and contains(@class, "account") and contains(@class, "preset")]/@id') as $item) {
      $accountId = (int)substr($item->value, strlen('rcmli_acc'));
      // if we allow to change the display name, then the only chance is to
      // recurse into all preconfigured accounts and see if we hit the
      // preconfigured cloud CardDAV account.
      // https://dev3.home.claus-justus-heine.de/roundcube/?_task=settings&_framed=1&_action=plugin.carddav.AccDetails&accountid=1
      $accountConfig = $this->sendRequest('?_task=settings&_action=plugin.carddav.AccDetails&accountid=' . $accountId, 'GET');
      $accountDoc = new DOMDocument('1.0', 'UTF-8');
      $accountDoc->encoding = 'UTF-8';
      $accountDoc->loadHTML($accountConfig['html']);
      $accountXPath = new DOMXPath($accountDoc);
      // search for id="rcmcrd_plain_presetname"
      $template = $accountXPath->query('//span[@id="rcmcrd_plain_presetname"]/text()');
      if (count($template) == 0) {
        continue;
      }
      $template = $template->item(0)->data;
      if ($template != $configTag) {
        continue;
      }
      // Ok, found it. Fetch all values into an associative array
      $formInputs = $accountXPath->query('//input');
      $formData = [];
      /** @var DOMElement $input */
      foreach ($formInputs as $input) {
        $name = $input->getAttribute('name');
        $value = $input->getAttribute('value');
        $formData[$name] = $value;
      }
      // c-pnok, tweak loginname and replace the password by an app-password -- on
      // every login. Anyhow, loading the RC app is quite an effort, so theses
      // tweaks probably will not hurt too much. Only -- it is unstable as
      // this hack depends on the UI of the embedded RC app.
      $appPassword = $this->appPasswordService->generateAppPassword('z-app-generated-roundcube');
      $formData['password'] = $appPassword['token'];
      $formData['username'] = $appPassword['loginName']; // why should this differ from the user-id ????
      $formData['_token'] = $this->rcRequestToken;
      // https://dev3.home.claus-justus-heine.de/roundcube/?_task=settings&_framed=1&_action=plugin.carddav.AccSave
      // $this->logInfo('FORM DATA ' . print_r($formData, true));
      $result = $this->sendRequest('?_task=settings&_action=plugin.carddav.AccSave&accountid=' . $accountId, 'POST', $formData);
      // $this->logInfo('TWEAK RESULT ' . print_r($result, true));
      if ($result === null) {
        $this->logError('Unable to patch CardDAV configuration "' . $configTag . '".');
        return false;
      }
      return true; // tag should be unique
    }
    $this->logError('Unable to find pre-configured CardDAV configuration with name "' . $configTag . '".');
    return false;
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
    if ($this->rcRequestToken) {
      $data['_token'] = $this->rcRequestToken;
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
   * @param null|string $text The text where to look for input fields.
   *
   * @return array [name => [key, value]] Input fields indexed by name.
   */
  private static function parseInputs(?string $text):array
  {
    $inputs = [];
    if (preg_match_all('/<input ([^>]*)>/i', $text, $inputMatches)) {
      foreach ($inputMatches[1] as $input) {
        if (preg_match_all('/(\w+)="([^"]*)"/i', $input, $keyvalMatches)) {
          $tmp = [];
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
   *
   * @param string $method POST or GET request.
   *
   * @param null|array $data   Data to send.
   *
   * @return null|array ['headers' => [headers], 'html' => html]
   */
  private function sendRequest(string $rcQuery = '', string $method = 'POST', ?array $data = null):?array
  {
    $response = null;

    if (!empty($rcQuery) && $rcQuery[0] != '/') {
      $rcQuery = '/'.$rcQuery;
    }
    $rcQuery = $this->externalURL().$rcQuery;
    // $this->logInfo("URL: '$rcQuery'.");
    try {
      $curl = curl_init();
      // general settings
      $curlOpts = [
        CURLOPT_URL            => $rcQuery,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FRESH_CONNECT  => true
      ];
      if ($method === 'POST') {
        $curlOpts[CURLOPT_POST] = true;
        if ($data) {
          $postData = http_build_query($data);
          $curlOpts[CURLOPT_POSTFIELDS] = $postData;
          $curlOpts[CURLOPT_TIMEOUT] = 60;
          $curlOpts[CURLOPT_HTTPHEADER] = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding: identity',
            'Content-Type: ' . self::FORM_URLENCODED,
            'Content-Length: ' . strlen($postData),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
          ];
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
      if ($curlErrorNum === CURLE_OK) {
        if ($respHttpCode >= 400) {
          $this->logWarn("Got the following HTTP Status Code: $respHttpCode.");
        }
        $response = $this->splitResponse($rawResponse, $headerSize);
      } else {
        $this->logWarn("Opening url '$rcQuery' failed with the following HTTP Status Code: '$respHttpCode'. Curl status: '$curlError' ($curlErrorNum).");
      }
      curl_close($curl);
    } catch (Exception $e) {
      $this->logWarn("URL '$rcQuery' open failed.");
    }
    return $response;
  }

  /**
   * Splits a curl response into headers and html.
   *
   * @param string $response
   *
   * @param int    $headerSize
   *
   * @return array ['headers' => [headers], 'html' => html]
   */
  private function splitResponse(string $response, int $headerSize):array
  {
    $headers = $html = "";
    if ($headerSize) {
      $headers = substr($response, 0, $headerSize);
      $html    = substr($response, $headerSize);
    } else {
      list($headers, $html) = explode("\r\n\r\n", $response, 2);
    }
    $headersArray = self::parseResponseHeaders($headers);
    // $this->logInfo('HEADERS ' . print_r($headersArray, true));
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
   * .
   *
   * @return array ['name0' => [header0:0, header0:1], 'name1' => [header1:0], ...]
   */
  private static function parseResponseHeaders(string $rawHeaders):array
  {
    $responseHeaders = [];
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
   * @param array $cookieHeaders ['name=value; text', ...].
   *
   * @return array ['name' => 'value', ...]
   */
  private static function parseCookies(?array $cookieHeaders):array
  {
    $cookies = [];
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
