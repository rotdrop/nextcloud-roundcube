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
- curl

## Tested with
- ownCloud 10.0.10
- Roundcube Webmail 1.1.5
- Roundcube in a different machine/subdomain than ownCloud

## Installation
- Install app by cloning this repository.
- The RC installation must be accessible from the same ownCloud server (same domain).

## Configuration
- You may need to configure a virtual host with a proxypass alias to somewhere else.
  - Apache would need mods proxy, proxy_http
- OwnCloud settings (as admin), Additional:
  - Set at least the default RC path: e.g. roundcube1/
  - Save settings

### Apache example:

```apache
ServerName owncloud.domain.com

SSLProxyEngine on
ProxyPass /roundcube1/ https://proxymail1.domain.com/
ProxyPass /roundcube2/ https://proxymail2.domain.com/
ProxyPassReverse /roundcube1/ https://proxymail1.domain.com/
ProxyPassReverse /roundcube2/ https://proxymail2.domain.com/
```
