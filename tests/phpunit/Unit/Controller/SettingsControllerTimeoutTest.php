<?php

namespace OCA\RoundCube\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

use OCA\RoundCube\Controller\SettingsController;
use OCA\RoundCube\Service\Config;

class SettingsControllerTimeoutTest extends TestCase
{
  private function makeController(Config $config): SettingsController
  {
    $l10n = $this->createStub(IL10N::class);
    $l10n->method('t')->willReturnCallback(fn(string $text, $params = []) => vsprintf($text, (array)$params));

    return new SettingsController(
      'roundcube',
      $this->createStub(IRequest::class),
      'admin',
      $this->createStub(LoggerInterface::class),
      $this->createStub(IURLGenerator::class),
      $l10n,
      $config,
    );
  }

  public static function settingProvider(): array
  {
    return [
      [Config::CONNECT_TIMEOUT, Config::CONNECT_TIMEOUT_DEFAULT],
      [Config::REQUEST_TIMEOUT, Config::REQUEST_TIMEOUT_DEFAULT],
    ];
  }

  #[DataProvider('settingProvider')]
  public function testNonDefaultValueIsStored(string $setting, int $default): void
  {
    $config = $this->createMock(Config::class);
    $config->expects($this->once())->method('setAppValue')->with($setting, $default + 5);
    $config->expects($this->never())->method('deleteAppValue');

    $response = $this->makeController($config)->setAdmin($setting, (string)($default + 5));
    $this->assertSame(Http::STATUS_OK, $response->getStatus());
    $this->assertSame($default + 5, $response->getData()['newValue']);
  }

  #[DataProvider('settingProvider')]
  public function testDefaultValueResetsTheSetting(string $setting, int $default): void
  {
    $config = $this->createMock(Config::class);
    $config->expects($this->never())->method('setAppValue');
    $config->expects($this->once())->method('deleteAppValue')->with($setting);

    $response = $this->makeController($config)->setAdmin($setting, (string)$default);
    $this->assertSame(Http::STATUS_OK, $response->getStatus());
    $this->assertSame($default, $response->getData()['newValue']);
  }

  public static function invalidValueProvider(): array
  {
    return [
      [''], ['0'], ['-3'], ['abc'], ['1.5'],
    ];
  }

  #[DataProvider('invalidValueProvider')]
  public function testInvalidValueIsRejectedAndNothingIsSaved(string $value): void
  {
    $config = $this->createMock(Config::class);
    $config->expects($this->never())->method('setAppValue');
    $config->expects($this->never())->method('deleteAppValue');

    $response = $this->makeController($config)->setAdmin(Config::REQUEST_TIMEOUT, $value);
    $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
  }

  #[DataProvider('settingProvider')]
  public function testGetAdminReturnsAnInteger(string $setting, int $default): void
  {
    $config = $this->createStub(Config::class);
    $config->method('getAppValue')->willReturn((string)($default + 1));

    $response = $this->makeController($config)->getAdmin($setting);
    $this->assertSame(Http::STATUS_OK, $response->getStatus());
    $this->assertSame($default + 1, $response->getData()['value']);
  }
}
