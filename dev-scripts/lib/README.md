# nextcloud-app-build-system
Kind of library of Makefile fragments and such. Intended as git sub-repo.

## PHP to TypeScript

The converter is now provided by the Composer library
`rotdrop/php-to-typescript`. Applications using `makefile/ts-types-files.mk`
must install it in their own `vendor-bin/php-to-typescript/` environment,
managed by `bamarni/composer-bin-plugin`. The updated Makefile fragment uses
the Composer package.

### Application setup

Enable `bamarni/composer-bin-plugin` in the application's root Composer
manifest and install the development dependencies:

```sh
composer config allow-plugins.bamarni/composer-bin-plugin true
composer require --dev bamarni/composer-bin-plugin:^1.8
```

In `vendor-bin/php-to-typescript/composer.json`, declare the converter
dependency and, until it is available on Packagist, its VCS repository:

```json
{
  "require-dev": {
    "rotdrop/php-to-typescript": "*@dev"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/rotdrop/php-to-typescript-"
    }
  ]
}
```

The repository name currently has a trailing hyphen; the Composer package
name does not. Replace `*@dev` with a release constraint when available.
Run `composer bin php-to-typescript install` from the application root.
Commit the bin environment's manifest and lock file, not its `vendor/` tree.
The Makefile installs this environment if its autoloader is missing or its
manifest/lock file changes. It also tracks the installed package's manifest
and all nested PHP sources as generation dependencies.

### Application launcher migration

The package is a library, not a standalone executable. Keep
`TYPESCRIPT_CONVERTER` pointing at the application's existing PHP launcher
and retain its constructor options, exclusions, collector choices, and
Nextcloud/toolkit bootstrap. Update the launcher as follows:

- Load `$appDir . '/vendor-bin/php-to-typescript/vendor/autoload.php'`
  instead of the old `vendor-bin/typescript-transformer/` autoloader.
- Remove the manual `addPsr4()` mapping of
  `OCA\RotDrop\DevScripts\PhpToTypeScript` to the bundled
  `lib/scripts/php-to-typescript/` directory. Composer now supplies it.
- Retain application and toolkit autoloaders and namespace mappings.
  Imports remain unchanged, including the `Collectors` and `Attributes`
  subnamespaces (for example,
  `OCA\RotDrop\DevScripts\PhpToTypeScript\Collectors\DTOCollector`).
- Pass the application's development-scripts directory as
  `devScriptsFolder`, not the installed package's directory.

The shared `scripts/vendor/autoload.php` bootstrap remains supported: the
Makefile still installs `scripts/composer.json`, including its Nextcloud OCP
dependency. It no longer deletes that environment or its lock files before
every generation.

Once the launcher has migrated, remove the old
`vendor-bin/typescript-transformer/` environment if nothing else uses it.
Spatie's transformer and Symfony Finder are dependencies of the new package.
Compare generated declarations and modules in the consuming application,
especially when replacing a fork of Spatie's transformer. Database metadata
generation still requires the existing Nextcloud/CAFEVDB integration; apps
without it should retain their explicit non-database collectors.
