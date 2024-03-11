# nextcloud-app-toolkit

<!-- markdown-toc start - Don't edit this section. Run M-x markdown-toc-refresh-toc -->
**Table of Contents**

    - [Description](#description)
    - [Setup](#setup)
        - [Direct Use as GIT Sub-Repo](#direct-use-as-git-sub-repo)
        - [Indirect "Scoped" Use](#indirect-scoped-use)
    - [Why](#why)

<!-- markdown-toc end -->

## Description

Some common PHP classes for my Nextcloud apps. The Idea is to use `git
subtree` or maybe
[`git-subrepo`](https://github.com/ingydotnet/git-subrepo) and just
"quote" the code into a sub-directory of the `lib/`-folder of a
project.

## Setup

### Direct Use as GIT Sub-Repo

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

### Indirect "Scoped" Use

When using this toolkit in more than one app then the usual
compatibility problems occur when using the code
[directly](#direct-use-as-git-sub-repo) as described above. Different
apps may depend on different versions but only one shared instance of
the package is used. To work around this it is possible to wrap the
entire package into a namespace. This is done by simple exchanging the
`Rotdrop` PHP namespace by another one. When using `make` for the
build process in an app this can be done with the following `Makefile`
snippet:

``` makefile
APP_TOOLKIT_DIR = $(ABSSRCDIR)/php-toolkit
APP_TOOLKIT_DEST = $(ABSSRCDIR)/lib/Toolkit
APP_TOOLKIT_NS = CAFEVDB

include $(APP_TOOLKIT_DIR)/tools/scopeme.mk
```

Here `ABSSRCDIR` is assumed to contain the absolute path to the
consuming package and `php-toolkit` is a folder which contains the
sources of this package.

## Why

One could have created a vanilla composer package. However, that has
issues with the Transifex translation integration if the code needs
some strings translated. The hope is also that a subtree or subrepo
simplifies development while still being able to share common code
between different apps.
