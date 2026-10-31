<?php
/**
 * @copyright 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

$appDir = dirname(__DIR__);
$toolkitNamespace = 'OCA\\RoundCube\\Scoped\\OCA\\RotDrop\\Toolkit\\';

// Nextcloud reloads app autoloading before migrations, even if the old Application is still loaded.
\OC::$composerAutoloader->addPsr4('OCA\\RoundCube\\', $appDir . '/lib/', true);
\OC::$composerAutoloader->addPsr4(
  $toolkitNamespace,
  $appDir . '/vendor-scoped/rotdrop/nextcloud-app-toolkit/src/',
  true,
);

// Both names must resolve to the same type when old and new app code coexist during an update.
spl_autoload_register(static function (string $class) use ($toolkitNamespace): void {
  $legacyNamespace = 'OCA\\RoundCube\\Toolkit\\';
  if (str_starts_with($class, $legacyNamespace)) {
    $legacyClass = $class;
    $scopedClass = $toolkitNamespace . substr($class, strlen($legacyNamespace));
  } elseif (str_starts_with($class, $toolkitNamespace)) {
    $scopedClass = $class;
    $legacyClass = $legacyNamespace . substr($class, strlen($toolkitNamespace));
  } else {
    return;
  }
  $isDefined = static fn(string $name): bool =>
    class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false);
  if ($isDefined($legacyClass)) {
    class_alias($legacyClass, $scopedClass);
    return;
  }
  if (!$isDefined($scopedClass)) {
    \OC::$composerAutoloader->loadClass($scopedClass);
  }
  if ($isDefined($scopedClass)) {
    class_alias($scopedClass, $legacyClass);
  }
}, true, true);
