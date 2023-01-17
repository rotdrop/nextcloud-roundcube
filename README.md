# RoundCube Web Mail

<!-- markdown-toc start - Don't edit this section. Run M-x markdown-toc-refresh-toc -->
**Table of Contents**

- [RoundCube Web Mail](#roundcube-web-mail)
    - [Intro](#intro)
    - [Installation](#installation)
    - [More docs to follow ...](#more-docs-to-follow-)

<!-- markdown-toc end -->


## Intro

Ok, what is this:

This was originally a fork from

https://github.com/LeonardoRM/owncloud-roundcube

However, now this fork just concentrates to embed an external
Roundcube installation into a Nextcloud installation, there is no intent whatseover to keep
compatibility with Owncloud.

Knowning that there is nowaday a dedicated native Nextcloud email app this might be
questionable. OTOH, Roundcube is **really** a **very** **mature** email web app. So.

Currently the focus is on Roundcube version v1.6 and Nextcloud version
25 and on pushing this fork into the Nextcloud app-store.

Status is: hey, it works for me! Surprise!

Please feel free to submit issues, discussions, pull-request (<- most welcome, please do!).

NO guarantees. However, I am using a version of this beast in a
production setup as part of a groupware managing a quite active layman
orchestra in Germany. This means: I will at least see that it works
for me. However, the version you find here is highly experimental and
*not* what I am currently using.

## Installation

- Download a (pre-)release tarbal
- The assets are also contained in the git repo, so may a simple clone  just works. Maybe not ...
- Compile from source, do a `make dev` or `make build`. You need `composer` and `node` (`npm`).

## More docs to follow ...
