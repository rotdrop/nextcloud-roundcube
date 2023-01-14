# nextcloud-app-toolkit

## Description

Some common PHP classes for my Nextcloud apps. The Idea is to use `git
subtree` or maybe
[`git-subrepo`](https://github.com/ingydotnet/git-subrepo) and just
"quote" the code into a sub-directory of the `lib/`-folder of a
project.

## Setup

Say we choose `lib/Toolkit/` as destination folder, then one could do
```
git subrepo clone THIS_REPOS_URL lib/Toolkit
```

Then one needs to add an auto-loading directive to the project's `composer.json`:
```
  "autoload": {
    "psr-4": {
      "OCA\\RotDrop\\Toolkit\\": "lib/Toolkit/"
    }
  },

```

This will instruct the `composer` to generate appropriate auto-loading
files such that the classes can be found.

## Why

One could have created a vanilla composer package. However, that has
issues with the Transifex translation integration if the code needs
some strings translated. The hope is also that a subtree or subrepo
simplifies development while still being able to share common code
between different apps.
