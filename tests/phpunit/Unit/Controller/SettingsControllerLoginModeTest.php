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

class SettingsControllerLoginModeTest extends TestCase
{
  private function makeController(Config $config): SettingsController
  {
    $l10n = $this->createMock(IL10N::class);
    $l10n->method('t')->willReturnCallback(fn(string $text, $params = []) => vsprintf($text, (array)$params));

    return new SettingsController(
      'roundcube',
      $this->createMock(IRequest::class),
      'admin',
      $this->createMock(LoggerInterface::class),
      $this->createMock(IURLGenerator::class),
      $l10n,
      $config,
    );
  }

  public static function modeProvider(): array
  {
    return [
      [Config::LOGIN_MODE_AUTO],
      [Config::LOGIN_MODE_AUTO_WITH_FALLBACK],
      [Config::LOGIN_MODE_FORM_ONLY],
    ];
  }

  #[DataProvider('modeProvider')]
  public function testSetModeStoresItAndDropsLegacyFlag(string $mode): void
  {
    $config = $this->createMock(Config::class);
    $config->expects($this->once())->method('setAppValue')->with(Config::LOGIN_MODE, $mode);
    // the legacy flag must go, else it would resurrect "formOnly"
    $config->expects($this->once())->method('deleteAppValue')->with(Config::ALLOW_MANUAL_LOGIN);

    $response = $this->makeController($config)->setAdmin(Config::LOGIN_MODE, $mode);
    $this->assertSame(Http::STATUS_OK, $response->getStatus());
    $this->assertSame($mode, $response->getData()['newValue']);
  }

  public function testInvalidModeIsRejectedAndNothingIsSaved(): void
  {
    $config = $this->createMock(Config::class);
    $config->expects($this->never())->method('setAppValue');
    $config->expects($this->never())->method('deleteAppValue');

    $response = $this->makeController($config)->setAdmin(Config::LOGIN_MODE, 'bogus');
    $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
  }

  public function testLegacySettingIsNoLongerAnAdminSetting(): void
  {
    $config = $this->createMock(Config::class);
    $config->expects($this->never())->method('setAppValue');

    $response = $this->makeController($config)->setAdmin(Config::ALLOW_MANUAL_LOGIN, '1');
    $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
  }

  public function testGetAdminReportsTheEffectiveMode(): void
  {
    $config = $this->createMock(Config::class);
    $config->method('loginMode')->willReturn(Config::LOGIN_MODE_FORM_ONLY);
    $config->method('getAppValue')->willReturn(null);

    $response = $this->makeController($config)->getAdmin(Config::LOGIN_MODE);
    $this->assertSame(Http::STATUS_OK, $response->getStatus());
    $this->assertSame(Config::LOGIN_MODE_FORM_ONLY, $response->getData()['value']);
  }
}
