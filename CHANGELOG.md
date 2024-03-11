# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.1.0-rc3] - unreleased

### Added

- support Nextcloud v28

- CardDAV integration with Nextcloud, needs RCMCardDav plugin. See README.md.

- Nginx conf for "Different Domains, but same Web-Server" setup (courtesy @HLFH)

### Changed

- drop support for Nextcloud < v26

- Required dependencies (jq, python-tabulate have been added) (courtesy @HLFH)

### Fixed

- avoid warnings with PHP 8.2: explicitly declare some properties (courtesy @HLFH)

- improve error message when not configured properly, in particular
  when the Roundcube location has not been set.

## [1.0.2] - 2023-03-24

### Added

- Email setup: optional single global shared email account

## [1.0.1] - 2023-03-01

### Fixed

- spelling errors

### Added

- PHP NC app-tookit moved into the app-namespace in order to avoid
  collission with confliciting versions of the toolkit in other apps.

## [1.0.0] - 2023-02-23

Nothing changed, just re-label rc7 as "final".

## [1.0.0-rc7] - 2023-01-28

### Added

- translations by Transifex

- Fill the [README.md](README.md) with contents

### Changed

- Replace legacy templates + jQuery by Vue and vanilla JS

## [1.0.0-rc6] - 2023-01-19

### Added

- screenshots
- cleanup l10n

## [1.0.0-rc5] - 2023-01-19

### Fixed

- CSS styles after changing the app name

## [1.0.0-rc4] - 2023-01-19

### Changed

- Minor README.md update

## [1.0.0-rc3] - 2023-01-18

### Added

- Rename app from roundcube to mail_roundcube in order to get out of
  the way of existing abandoned apps in the app-store

## [1.0.0-rc2] - 2023-01-18

### Fixed

- Broken error handling in case of login errors

### Added

- Ongoing code cleanup

## [1.0.0-rc1] - 2023-01-17

### Fixed

- Broken namespace references

## [1.0.0-rc0] - 2023-01-16

### Added

- First pre-release
