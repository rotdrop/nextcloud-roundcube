<?php

namespace OCA\RoundCube\Tests\Unit\Migration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use OCP\IConfig;
use OCP\Migration\IOutput;

use OCA\RoundCube\Migration\MigrateAllowManualLogin;
use OCA\RoundCube\Service\Config;

class MigrateAllowManualLoginTest extends TestCase
{
  private const APP = 'mail_roundcube';

  /** @var array<string, string> */
  private array $values;

  private function migrate(array $values): array
  {
    $this->values = $values;
    $ocConfig = $this->createStub(IConfig::class);
    $ocConfig->method('getAppValue')->willReturnCallback(
      fn(string $app, string $key, string $default = '') => $this->values[$key] ?? $default,
    );
    $ocConfig->method('setAppValue')->willReturnCallback(
      function(string $app, string $key, string $value): void {
        $this->values[$key] = $value;
      },
    );
    $ocConfig->method('deleteAppValue')->willReturnCallback(
      function(string $app, string $key): void {
        unset($this->values[$key]);
      },
    );
    (new MigrateAllowManualLogin(self::APP, $ocConfig))->run($this->createStub(IOutput::class));
    return $this->values;
  }

  /** @return array<string, array{array<string, string>, array<string, string>}> */
  public static function provideCases(): array
  {
    return [
      'legacy true becomes formOnly' => [
        [Config::ALLOW_MANUAL_LOGIN => 'true'],
        [Config::LOGIN_MODE => Config::LOGIN_MODE_FORM_ONLY],
      ],
      'legacy 1 becomes formOnly' => [
        [Config::ALLOW_MANUAL_LOGIN => '1'],
        [Config::LOGIN_MODE => Config::LOGIN_MODE_FORM_ONLY],
      ],
      'legacy false is just removed' => [
        [Config::ALLOW_MANUAL_LOGIN => 'false'],
        [],
      ],
      'explicit mode wins over legacy true' => [
        [Config::ALLOW_MANUAL_LOGIN => 'true', Config::LOGIN_MODE => Config::LOGIN_MODE_AUTO],
        [Config::LOGIN_MODE => Config::LOGIN_MODE_AUTO],
      ],
      'nothing to do without legacy key' => [
        [Config::LOGIN_MODE => Config::LOGIN_MODE_AUTO_WITH_FALLBACK],
        [Config::LOGIN_MODE => Config::LOGIN_MODE_AUTO_WITH_FALLBACK],
      ],
      'empty' => [[], []],
    ];
  }

  #[DataProvider('provideCases')]
  public function testMigration(array $before, array $after): void
  {
    $this->assertSame($after, $this->migrate($before));
  }

  public function testIsIdempotent(): void
  {
    $once = $this->migrate([Config::ALLOW_MANUAL_LOGIN => 'true']);
    $this->assertSame($once, $this->migrate($once));
  }
}
