<?php

namespace OCA\RoundCube\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IURLGenerator;

use OCA\RoundCube\Controller\PageController;
use OCA\RoundCube\Service\AssetService;
use OCA\RoundCube\Service\AuthRoundCube;
use OCA\RoundCube\Service\Config;

class PageControllerLoginModeTest extends TestCase
{
  private const FAILURES = ['noemail', 'login', 'carddav'];

  /**
   * Run PageController::index() and return the "config" initial state.
   *
   * @param string $failure One of self::FAILURES or '' for a working setup.
   */
  private function runIndex(string $mode, string $failure = '', string $url = 'https://rc.example.com/'): array
  {
    $config = $this->createMock(Config::class);
    $config->method('loginMode')->willReturn($mode);
    $config->method('emailAddress')->willReturn('user@example.com');
    $config->method('getAppValue')->willReturn(false);
    $config->method('emailCredentials')->willReturn(
      $failure === 'noemail' ? false : ['userId' => 'user@example.com', 'password' => 'secret']
    );

    $authenticator = $this->createMock(AuthRoundCube::class);
    $authenticator->method('externalURL')->willReturn($url);
    $authenticator->method('login')->willReturn($failure !== 'login');
    $authenticator->method('cardDavConfig')->willReturn($failure !== 'carddav');

    $assets = $this->createMock(AssetService::class);
    $assets->method('getJSAsset')->willReturn('app.js');
    $assets->method('getCSSAsset')->willReturn('app.css');

    $state = null;
    $initialState = $this->createMock(IInitialState::class);
    $initialState->method('provideInitialState')->willReturnCallback(
      function (string $key, $value) use (&$state) {
        if ($key === 'config') {
          $state = $value;
        }
      }
    );

    $controller = new PageController(
      'roundcube',
      $this->createMock(IRequest::class),
      'user',
      $authenticator,
      $config,
      $assets,
      $initialState,
      $this->createMock(IURLGenerator::class),
      $this->createMock(LoggerInterface::class),
    );
    $controller->index();

    return $state;
  }

  public static function modeProvider(): array
  {
    return array_combine(Config::LOGIN_MODES, array_map(fn($m) => [$m], Config::LOGIN_MODES));
  }

  public static function failureProvider(): array
  {
    return array_combine(self::FAILURES, array_map(fn($f) => [$f], self::FAILURES));
  }

  #[DataProvider('modeProvider')]
  public function testMissingRoundCubeUrlIsAnErrorInEveryMode(string $mode): void
  {
    $state = $this->runIndex($mode, url: '');
    $this->assertSame('error', $state['state']);
    $this->assertSame('norcurl', $state['reason']);
  }

  public function testAutoModeWithWorkingCredentials(): void
  {
    $state = $this->runIndex(Config::LOGIN_MODE_AUTO);
    $this->assertSame('success', $state['state']);
    $this->assertNull($state['reason']);
    $this->assertFalse($state['loginFormShown']);
    $this->assertSame(Config::LOGIN_MODE_AUTO, $state['loginMode']);
  }

  #[DataProvider('failureProvider')]
  public function testAutoModeReportsTheError(string $failure): void
  {
    $state = $this->runIndex(Config::LOGIN_MODE_AUTO, $failure);
    $this->assertSame('error', $state['state']);
    $this->assertSame($failure, $state['reason']);
    $this->assertFalse($state['loginFormShown']);
  }

  public function testFallbackModeWithWorkingCredentialsLogsInAutomatically(): void
  {
    $state = $this->runIndex(Config::LOGIN_MODE_AUTO_WITH_FALLBACK);
    $this->assertSame('success', $state['state']);
    $this->assertNull($state['reason']);
    $this->assertFalse($state['loginFormShown']);
  }

  #[DataProvider('failureProvider')]
  public function testFallbackModeShowsTheLoginFormOnFailure(string $failure): void
  {
    $state = $this->runIndex(Config::LOGIN_MODE_AUTO_WITH_FALLBACK, $failure);
    $this->assertSame('success', $state['state']);
    $this->assertNull($state['reason']);
    $this->assertTrue($state['loginFormShown']);
  }

  public function testFormOnlyShowsTheLoginFormWithoutCheckingCredentials(): void
  {
    $config = $this->createMock(Config::class);
    $config->method('loginMode')->willReturn(Config::LOGIN_MODE_FORM_ONLY);
    $config->method('getAppValue')->willReturn(false);
    $config->expects($this->never())->method('emailCredentials');

    $authenticator = $this->createMock(AuthRoundCube::class);
    $authenticator->method('externalURL')->willReturn('https://rc.example.com/');
    $authenticator->expects($this->never())->method('login');
    $authenticator->expects($this->never())->method('cardDavConfig');

    $assets = $this->createMock(AssetService::class);
    $state = null;
    $initialState = $this->createMock(IInitialState::class);
    $initialState->method('provideInitialState')->willReturnCallback(
      function (string $key, $value) use (&$state) {
        $state = $value;
      }
    );

    (new PageController(
      'roundcube',
      $this->createMock(IRequest::class),
      'user',
      $authenticator,
      $config,
      $assets,
      $initialState,
      $this->createMock(IURLGenerator::class),
      $this->createMock(LoggerInterface::class),
    ))->index();

    $this->assertSame('success', $state['state']);
    $this->assertTrue($state['loginFormShown']);
  }
}
