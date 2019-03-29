# owncloud-roundcube
OwnCloud app to integrate RoundCube Webmail. The app embeds the [RoundCube webmail](https://roundcube.net/ "RoundCube's homepage") interface in ownCloud.

## History
This app uses idea and code from [this app](https://github.com/hypery2k/owncloud/tree/master/roundcube).
The app needed an update to work with newer versions of ownCloud. This app doesn't have all features but at least you can auto-login.

## Features
- Auto login
- Enable/disable SSL verification
- Show/hide RC topline bar
- Default path to RC
- Per email domain path to RC

## Requirements
- ownCloud >= 10
- Roundcube Webmail >= 1.1

## Tested with
- ownCloud 10.0.10
- Roundcube Webmail 1.1.5

## Installation
- Install app by cloning this repository.
- The RC installation must be accessible from the same ownCloud server (same domain).
