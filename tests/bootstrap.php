<?php
/**
 * Bootstrap for the unit tests. A Nextcloud server tree is needed for the
 * OCP interfaces, it is not booted. Point NEXTCLOUD_ROOT to it if the app is
 * not installed below its apps/ directory.
 */

namespace OCP {
  // OCP\Util needs a booted server, the controllers only register assets.
  if (!class_exists(Util::class, false)) {
    class Util
    {
      public static function addScript(string $application, ?string $file = null, string $afterAppId = 'core', bool $prepend = false): void
      {
      }

      public static function addStyle(string $application, ?string $file = null, bool $prepend = false): void
      {
      }
    }
  }
}

namespace {
  $appDir = dirname(__DIR__);
  $serverRoot = getenv('NEXTCLOUD_ROOT') ?: dirname($appDir, 2);
  $serverAutoloader = $serverRoot . '/lib/composer/autoload.php';
  if (!is_file($serverAutoloader)) {
    fwrite(STDERR, 'Nextcloud server not found, set NEXTCLOUD_ROOT (tried "' . $serverRoot . '").' . PHP_EOL);
    exit(1);
  }

  $loader = require $serverAutoloader;
  $loader->addPsr4('OCA\\RoundCube\\', $appDir . '/lib/');
  $loader->addPsr4(
    'OCA\\RoundCube\\Scoped\\OCA\\RotDrop\\Toolkit\\',
    $appDir . '/vendor-scoped/rotdrop/nextcloud-app-toolkit/src/',
  );
  if (is_file($serverRoot . '/3rdparty/autoload.php')) {
    require_once $serverRoot . '/3rdparty/autoload.php';
  }
}
