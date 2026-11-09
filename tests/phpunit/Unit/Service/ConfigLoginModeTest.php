<?php

namespace OCA\RoundCube\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use OCP\Authentication\LoginCredentials\IStore;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Security\ICrypto;

use OCA\RoundCube\Service\Config;

class ConfigLoginModeTest extends TestCase
{
  /** @param array<string, string> $stored */
  private function makeConfig(array $stored): Config
  {
    $ocConfig = $this->createStub(IConfig::class);
    $ocConfig->method('getAppValue')->willReturnCallback(
      fn(string $app, string $key, mixed $default = '') => $stored[$key] ?? $default
    );
    return new Config(
      $this->createStub(IUserSession::class),
      $this->createStub(IStore::class),
      'roundcube',
      $ocConfig,
      $this->createStub(ICrypto::class),
      $this->createStub(LoggerInterface::class),
    );
  }

  public static function loginModeProvider(): array
  {
    return [
      'nothing stored' => [[], Config::LOGIN_MODE_AUTO],
      'legacy true' => [[Config::ALLOW_MANUAL_LOGIN => '1'], Config::LOGIN_MODE_FORM_ONLY],
      'legacy "true"' => [[Config::ALLOW_MANUAL_LOGIN => 'true'], Config::LOGIN_MODE_FORM_ONLY],
      'legacy false' => [[Config::ALLOW_MANUAL_LOGIN => '0'], Config::LOGIN_MODE_AUTO],
      'legacy "false"' => [[Config::ALLOW_MANUAL_LOGIN => 'false'], Config::LOGIN_MODE_AUTO],
      'explicit auto' => [[Config::LOGIN_MODE => 'auto'], Config::LOGIN_MODE_AUTO],
      'explicit fallback' => [[Config::LOGIN_MODE => 'autoWithFallback'], Config::LOGIN_MODE_AUTO_WITH_FALLBACK],
      'explicit form only' => [[Config::LOGIN_MODE => 'formOnly'], Config::LOGIN_MODE_FORM_ONLY],
      'explicit wins over legacy' => [
        [Config::LOGIN_MODE => 'autoWithFallback', Config::ALLOW_MANUAL_LOGIN => '1'],
        Config::LOGIN_MODE_AUTO_WITH_FALLBACK,
      ],
      'invalid mode falls back to legacy' => [
        [Config::LOGIN_MODE => 'garbage', Config::ALLOW_MANUAL_LOGIN => '1'],
        Config::LOGIN_MODE_FORM_ONLY,
      ],
      'invalid mode without legacy' => [[Config::LOGIN_MODE => 'garbage'], Config::LOGIN_MODE_AUTO],
    ];
  }

  #[DataProvider('loginModeProvider')]
  public function testLoginMode(array $stored, string $expected): void
  {
    $this->assertSame($expected, $this->makeConfig($stored)->loginMode());
  }
}
