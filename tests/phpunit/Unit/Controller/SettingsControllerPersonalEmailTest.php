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

class SettingsControllerPersonalEmailTest extends TestCase
{
  private function makeController(Config $config): SettingsController
  {
    return new SettingsController(
      'roundcube',
      $this->createStub(IRequest::class),
      'user',
      $this->createStub(LoggerInterface::class),
      $this->createStub(IURLGenerator::class),
      $this->createStub(IL10N::class),
      $config,
    );
  }

  public static function emailProvider(): array
  {
    return [
      'unset' => [null, ''],
      'saved' => ['user@example.com', 'user@example.com'],
    ];
  }

  #[DataProvider('emailProvider')]
  public function testGetPersonalReturnsAStringForEmail(?string $stored, string $expected): void
  {
    $config = $this->createStub(Config::class);
    $config->method('getPersonalValue')->willReturnCallback(
      fn(string $key) => $key === SettingsController::EMAIL_ADDRESS ? $stored : null,
    );
    $config->method('getAppValue')->willReturn(null);

    $controller = $this->makeController($config);
    $all = $controller->getPersonal()->getData();
    $single = $controller->getPersonal(SettingsController::EMAIL_ADDRESS)->getData();

    $this->assertSame($expected, $all['emailAddress']);
    $this->assertSame($expected, $all['humanEmailAddress']);
    $this->assertSame(['value' => $expected, 'humanValue' => $expected], $single);
  }

  public function testClearingEmailDeletesSettingAndReturnsAnEmptyString(): void
  {
    $config = $this->createMock(Config::class);
    $config->method('getPersonalValue')->willReturn('user@example.com');
    $config->expects($this->once())->method('deletePersonalValue')->with(SettingsController::EMAIL_ADDRESS);
    $config->expects($this->never())->method('setPersonalValue');

    $result = $this->makeController($config)->setPersonal(SettingsController::EMAIL_ADDRESS, '')->getData();

    $this->assertSame('user@example.com', $result['oldValue']);
    $this->assertSame('', $result['newValue']);
    $this->assertSame('', $result['humanValue']);
  }
}
