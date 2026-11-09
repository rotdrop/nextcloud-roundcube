<?php

namespace OCA\RoundCube\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

use OCA\RoundCube\Controller\SettingsController;
use OCA\RoundCube\Service\Config;

class SettingsControllerAdminTextTest extends TestCase
{
  private function makeController(Config $config): SettingsController
  {
    return new SettingsController(
      'roundcube',
      $this->createStub(IRequest::class),
      'admin',
      $this->createStub(LoggerInterface::class),
      $this->createStub(IURLGenerator::class),
      $this->createStub(IL10N::class),
      $config,
    );
  }

  public static function textSettingProvider(): array
  {
    return [
      'unset URL' => [Config::EXTERNAL_LOCATION, null, ''],
      'saved URL' => [Config::EXTERNAL_LOCATION, 'https://mail.example.com/', 'https://mail.example.com/'],
      'unset domain' => [Config::EMAIL_DEFAULT_DOMAIN, null, ''],
      'saved domain' => [Config::EMAIL_DEFAULT_DOMAIN, 'example.com', 'example.com'],
      'unset fixed address' => [Config::FIXED_SINGLE_EMAIL_ADDRESS, null, ''],
      'saved fixed address' => [Config::FIXED_SINGLE_EMAIL_ADDRESS, 'user@example.com', 'user@example.com'],
    ];
  }

  #[DataProvider('textSettingProvider')]
  public function testGetAdminReturnsTextValues(string $setting, ?string $stored, string $expected): void
  {
    $config = $this->createStub(Config::class);
    $config->method('getAppValue')->willReturnCallback(
      fn(string $key) => $key === $setting ? $stored : null,
    );
    $config->method('loginMode')->willReturn(Config::LOGIN_MODE_AUTO);

    $controller = $this->makeController($config);
    $all = $controller->getAdmin()->getData();
    $single = $controller->getAdmin($setting)->getData();

    $this->assertSame($expected, $all[$setting]);
    $this->assertSame($expected, $all['human' . ucfirst($setting)]);
    $this->assertSame(['value' => $expected, 'humanValue' => $expected], $single);
  }

  public function testClearingUrlDeletesSettingAndReturnsAnEmptyString(): void
  {
    $config = $this->createMock(Config::class);
    $config->method('getAppValue')->willReturn('https://mail.example.com/');
    $config->expects($this->once())->method('deleteAppValue')->with(Config::EXTERNAL_LOCATION);
    $config->expects($this->never())->method('setAppValue');

    $result = $this->makeController($config)->setAdmin(Config::EXTERNAL_LOCATION, '')->getData();

    $this->assertSame('https://mail.example.com/', $result['oldValue']);
    $this->assertSame('', $result['newValue']);
    $this->assertSame('', $result['humanValue']);
  }
}
