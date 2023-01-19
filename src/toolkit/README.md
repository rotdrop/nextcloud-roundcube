# nextcloud-app-toolkit-js

## Description

Some common javascript code for some of my Nextcloud apps. The Idea is to use `git
subtree` or maybe
[`git-subrepo`](https://github.com/ingydotnet/git-subrepo) and just
"quote" the code into a sub-directory of the `lib/`-folder of a
project.

## Setup

Say we choose `src/toolkit/` as destination folder, then one could do
```
git subrepo clone THIS_REPOS_URL src/toolkit/
```
Nothing more needs to be done except that the javascript files may
need a file `src/config.js` which exports the `appName` symbol.

Also, as this is not a package, there is no automatic dependency
management.

## Why

One could have created a vanilla npm package. However, that has
issues with the Transifex translation integration if the code needs
some strings translated. The hope is also that a subtree or subrepo
simplifies development while still being able to share common code
between different apps.
