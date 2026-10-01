#!/usr/bin/env php
<?php
/**
 * Archive Manager for Nextcloud
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2024, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *"
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// phpcs:disable PSR1.Files.SideEffects

ini_set('display_errors', 'stderr');

$appDir = realpath(__DIR__) . '/..';

try {
  $autoloader = require_once(__DIR__ . '/lib/scripts/vendor/autoload.php');
  require_once($appDir . '/vendor/autoload.php');
  // require_once($appDir . '/vendor-scoped/autoload.php');
  require_once($appDir . '/vendor-bin/typescript-transformer/vendor/autoload.php');
} catch (\Throwable $t) {
  fwrite(STDERR, 'Composer autoloads not set up: ' . $t->getMessage() . PHP_EOL);
  exit(1);
}

// can also be achieved by "autoload-dev" in composer.json
$autoloader->addPsr4(
  \OCA\Roundcube::class . '\\',
  __DIR__ . '(/../lib',
  true,
);
$autoloader->addPsr4(
  \OCA\RotDrop\DevScripts\PhpToTypeScript::class . '\\',
  __DIR__ . '/lib/scripts/php-to-typescript',
  true,
);
$autoloader->addPsr4(
  \OCA\RotDrop\Toolkit::class . '\\',
  $appDir . '/php-toolkit/',
  true,
);

use OCA\Roundcube\Toolkit\Console\ConsoleOutput;
use OCA\RotDrop\DevScripts\PhpToTypeScript;

// store output of different transformers in different files

$excludes = [
  'lib/Mount', // there is nothing to convert, and code diversion between NC versions triggers errors.
  'lib/Storage',
  'lib/Toolkit/Common',
  'lib/Toolkit/Doctrine',
  'lib/Toolkit/Backend',
  'lib/Toolkit/Service/ArchiveService.php',
];

$scopedNamespaces = [
  // \Doctrine::class,
  // \Carbon::class,
  // \Ramsey\Uuid::class,
];

use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use OCA\RotDrop\DevScripts\PhpToTypeScript\DTOCollector;

$collectors = [
  EnumCollector::class,
  DTOCollector::class,
];

$phpToTypeScript = new PhpToTypeScript\PhpToTypeScript(
  devScriptsFolder: __DIR__,
  excludes: $excludes,
  scopedNamespaces: $scopedNamespaces,
  collectors: $collectors,
);

try {
  $phpToTypeScript->run(
    input: new \Symfony\Component\Console\Input\ArgvInput,
    output: new \Symfony\Component\Console\Output\ConsoleOutput,
  );
} catch (Throwable $t) {
  fwrite(STDERR, 'Dependency injection not set up: ' . $t->getMessage() . PHP_EOL . print_r($t->getTrace(), true) . PHP_EOL);
  exit(1);
}
