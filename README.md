RoundCube Web Mail
==================

<!-- markdown-toc start - Don't edit this section. Run M-x markdown-toc-refresh-toc -->
**Table of Contents**

- [Intro](#intro)
- [Installation](#installation)
  - [Nextcloud App](#nextcloud-app)
  - [Roundcube App](#roundcube-app)
- [Configuration](#configuration)
  - [Adminstration, Web-Server Setup](#adminstration-web-server-setup)
    - [TL;DR](#tldr)
    - [NC Domain equals RC Domain](#nc-domain-equals-rc-domain)
    - [Different Domains, but same Web-Server](#different-domains-but-same-web-server)
      - [Example for Apache](#example-for-apache)
      - [Example for NGINX](#example-for-nginx)
    - [Different Domains, different Web-Server](#different-domains-different-web-server)
      - [Necessary Roundcube Setup](#necessary-roundcube-setup)
      - [Example for Apache](#example-for-apache-1)
      - [Example for NGINX](#example-for-nginx-1)
  - [Admistrators Settings](#admistrators-settings)
    - [Roundcube Installation](#roundcube-installation)
    - [Email Address Selection](#email-address-selection)
      - [Cloud Login-Id](#cloud-login-id)
      - [User's Preferences](#users-preferences)
      - [User's Choice](#users-choice)
    - [Advanced Settings](#advanced-settings)
      - [Force Single Sign On](#force-single-sign-on)
      - [Show Roundcube Top Bar](#show-roundcube-top-bar)
      - [Enable SSL Verification](#enable-ssl-verification)
      - [Per-User Encryption of Config-Values](#per-user-encryption-of-config-values)
  - [Personal Settings](#personal-settings)
    - [Email Login Name](#email-login-name)
    - [Email Password](#email-password)
- [Screenshots](#screenshots)
  - [Main Window](#main-window)
  - [Preferences](#preferences)
    - [Admin Settings](#admin-settings)
    - [Personal Settings](#personal-settings-1)

<!-- markdown-toc end -->

# Intro

This is a [Nextcloud app](https://nextcloud.com/) app which embeds an
separate [Roundcube](https://roundcube.net/) web-mailer installation
by means of an IFrame into you Nextcloud server installation.

The app can be configured to do some sort of single sign on (SSO) if
the email-server and Nextcloud share a common user and authentication
framework. Otherwise the users can configure their email credentials
in the app's personal settings.

This was originally a fork from

https://github.com/LeonardoRM/owncloud-roundcube

which in turn is based on a very early Owncloud app (discontinued)

https://github.com/hypery2k/owncloud

However, now this fork just concentrates to embed an external
Roundcube installation into a Nextcloud installation, there is no intent to keep
compatibility with Owncloud.

Knowning that there is nowaday a dedicated native Nextcloud email app this might be
questionable. OTOH, Roundcube is a very mature email web app with many nice plugins. 

Currently the focus is on Roundcube version v1.6 and Nextcloud version
25 and on pushing this fork into the Nextcloud app-store.

# Installation

## Nextcloud App

Hopefully an installation is possible by one of the following alternatives:

- install from the Nextcloud app-store
- download a (pre-)release tarball and extract it into you app directory

- pre-compiled assets are also contained in the git repository, but
  only on the release branches. The master branch typically does not
  contain any files which could be generated. So simply cloning the
  git-repo into your app folder and checking out an appropriate
  release branch like `stable25` *maybe* just works. Maybe not ...
- clone into your app-folder and compile from source, do a `make dev`
  or `make build`. You need `composer` and `node` (`npm`). `make help`
  or just `make` will list the available targets.

## Roundcube App

Please refere to the [Roundcube](https://roundcube.net/) documentation for general installation instructions.

# Configuration

## Adminstration, Web-Server Setup

### TL;DR

Due to the technology used -- Roundcube just runs in an
[iframe](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe) --
there are some restrictions caused by the [same origin
policy](https://developer.mozilla.org/en-US/docs/Web/Security/Same-origin_policy)
which at least nowadays is widely in use.

In principle this boils down to the point that the Nextcloud server
installation and the Roundcube installation must be served in the same
DNS domain.

### NC Domain equals RC Domain

In this case nothing special has to be done. The administrator can
simply enter the location of the Roundcube installation in the
adminstrator settings of the app.

### Different Domains, but same Web-Server

In this case the simplest thing is to just map the Roundcube
installation a second time by a simple `Alias` directive.

#### Example for Apache

Example for [Apache mod_alias](https://httpd.apache.org/docs/2.4/mod/mod_alias.html):

```
Alias /SOME_WEB_PATH PATH_TO_EXISTING_ROUNDCUBE_INSTALLATION

```

This directive should be placed in the virtual host definition of
the Nextcloud server installation.

In the administration settings for the NC app you can then enter
whatever you have chosen for `/SOME_WEB_PATH`.

#### Example for NGINX

**Please Doc Me!**

### Different Domains, different Web-Server

In this case it is possible to map the existing external Roundcube
installation into the Nextcloud server domain by means of a proxy
configuration mapping a local web-path to the external Roundcube
server.

*If you try this then please first check the proxy settings
independent from the use of it in this app, i.e. just open the
proxied-location in you web-browser, log-in manually and check if it
works.*

#### Necessary Roundcube Setup

Caused by changes in the transition from Roundcube verison 1.5 to
Roundcube version 1.6 we have now the problem that all web-paths used
by Roundcube are absolute. And this severely breaks any reverse proxy
setup unless you have access to the Roundcube installation. The point
is the new configuration directive

```
$config['request_path'] = REPLACE_ME_WITH_SOMETHING_WORKING;
```

Please have a look at the explanations in
[defaults.inc.php](https://github.com/roundcube/roundcubemail/blob/e2370544907034679d47a8be348a5b2a796fcdf9/config/defaults.inc.php#L821-L829).

**Please note that the configuration directive is only available since Roundcube 1.6.1**. But the proxy setup has been broken before in the progress of moving to 1.6.0.

A working setting -- but I suppose it undermines the security
improvements which were the cause for the new setting -- is the
following which in essence restores the previous behaviour to
have only relative links:

```
$config['request_path'] = '.';
```

**BIG FAT NOTE**: if you use Roundcube 1.6 and do nothing then
proxying will just not work (but please feel free to convince me from
the opposite by providing a configuration example ;)).

#### Example for Apache

Place something like the following into the virtual host setup for
your Nextcloud server:

```
ProxyRequests Off
SSLProxyEngine on
<Location /SOME_WEB_PATH/>
    ProxyPass https://webmail.my-domain.tld/
    ProxyPassReverse https://webmail.my-domain.tld/
    ProxyPreserveHost Off
</Location>
```

Please note that you probably have `ProxyPreserveHost On` in the
configuration for the push notifications service.

#### Example for NGINX

```
location /roundcube/ {
	proxy_pass https://YOURroundcubeINSTALL.tld/;
	proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
}
```
The "location /roundcube/" folder paramter is used only as example. This is the location you have to enter in Administrator seetings of this nextcloud module.
The "https://YOURroundcubeINSTALL.tld" domain is used only as example. Point to the domain where your Roundcube is served from.

## Admistrators Settings

Please have also a look at the [screenshot](#admin-settings).

### Roundcube Installation

This is just a text-box for the web-address of the Roundcube
installation. **Please read** the [notes about the web-server
setup](#adminstration-web-server-setup).

### Email Address Selection

The default is "User's Choice". Please note that the term
"address-selection" is a bit misleading: here you configure the
login-id into the email-server which may or may not be an
email-address.

#### Cloud Login-Id

Use the user-id of the logged-in user and add a to-be-configured
email-domain to the login-name. The idea here is that in a
single-sign-on (SSO) scenario the email accounts and cloud login-ids
more-or-less naturally coincide. Checking this option disables the
[email address choice](#email-login-name) in the personal preferences
of this Roundcube-integration app.

#### User's Preferences

Just take the email-address from the Nextcloud user
preferences. Checking this option disabled the [email address
choice](#email-login-name) in the personal preferences of this
Roundcube-integration app.

#### User's Choice

Make the login-id into the email server freely configurable by the
user through the personal settings page of this app.

### Advanced Settings

#### Force Single Sign On

Checking this option disables the [custom password setting in the
user's preferences](#email-password) section and enforces it to
coincide with the cloud password.

#### Show Roundcube Top Bar

Checking this option keeps the information bar -- including the logout
button -- of the Roundcube web-mailer. Concerning logout: the default
is to log-out the user out of Roundcube if it logs out of the cloud.

#### Enable SSL Verification

Uncheck to disable SSL certificate verification, e.g. in a setup using
self-signed certificates.

#### Per-User Encryption of Config-Values

If checked the [user configurable values](#personal-settings) are
encrypted with the user password. Otherwise they are encrypted with
the server password. The extra gain in security is questionable as any
installed app has access to the password of the currently logged in
user.

#### CardDAV Integration

If you install the [RCM CardDAV
plugin](https://github.com/mstilkerich/rcmcarddav) then it is possible
to autoconfigure the plugin such that the Nextcloud contacts are
accessible from inside Roundcube. In order to do so, you have to
define a "RoundCube CardDAV Tag" in the respective text-input of this
app and copy the configuration snippet shown there to the RCM CardDAV
plugin config. This should be
``` shell
PATH_TO_ROUNDCUBE/plugins/carddav/config.inc.php
```
The configuration snippet looks similar to this one:
``` php
$prefs['cloud'] = [
  'accountname'    => 'cloud',
  'discovery_url'  => 'https://nextcloud.example.com/remote.php/dav/addressbooks/users/%l',
  'username'       => '%l',
  'password'       => '%p',
  'name'           => '%N (%a)',
  'active'         =>  true,
  'readonly'       =>  false,
  'refresh_time'   => '00:15:00',
  'fixed'          => ['discovery_url',],
  'hide'           =>  false,
  'use_categories' => true,
]
```
Please note that the password-setting "%p" will not work if 2FA is
enabled. If this app detects that this is the case, it will try to
generate a suitable app-token automatically and register it with the
RoundCube CardDAV plugin -- which may work or not.

In order to have auto-configuration working it is vital to NOT include
"username" and "password" into the "fixed" array. The simple choice of
"%l" for the username and "%p" for the password will only work without
2Fa and if the local part of the email address is the same as the
cloud user-id.

## Personal Settings

Please have also a look at the
[screenshot](#personal-settings).

### Email Login Name

Configure the login-id into the email-server, or to be more precise:
into the Roundcube web-mailer. This setting is not available if the
administrator has pinned the login-id to the [email-address specified
in the user-preferences](#users-preferences). Of course, the users may
be able to change their email addresses there, but the setting in this
app is not available in this case. Likewise, for a single-sign-on
(SSO) setup this choice is disabled if the email login-id is [pinned
to coincide](#cloud-login-id) with the cloud login-id.

### Email Password

Configure the login-password for the email-server. This setting is not
available if the administrator has configured this app to attempt
single-sign-on in which case use of the Nextcloud password is enforced
for the login into the email-server.

# Screenshots

## Main Window

![file list](contrib/screenshots/main-window.png)

## Preferences

### Admin Settings

![file list](contrib/screenshots/admin-settings.png)

### Personal Settings

![file list](contrib/screenshots/personal-settings.png)
