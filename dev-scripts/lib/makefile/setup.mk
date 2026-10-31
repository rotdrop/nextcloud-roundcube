# Makefile fragment setting the common build variables, e.g. app-name
# and namespaces parsed from info.xml. Needs the variables ABSSRCDIR
# (absolute path of the source directory) set by the including
# Makefile.

APP_INFO = $(ABSSRCDIR)/appinfo/info.xml
XPATH := $(shell which xpath 2> /dev/null)
ifeq ($(XPATH),)
$(error The xpath binary could not be found, it is needed to parse $(APP_INFO))
endif
# evaluate the (expensive) xpath look-ups only once
APP_NAME := $(shell $(XPATH) -q -e '/info/id/text()' $(APP_INFO))
APP_VERSION := $(shell $(XPATH) -q -e '/info/version/text()' $(APP_INFO))
APP_NAMESPACE := $(shell $(XPATH) -q -e '/info/namespace/text()' $(APP_INFO))
SCOPED_NAMESPACE_POSTFIX := $(shell $(XPATH) -q -e '/info/scopednamespace/text()' $(APP_INFO))
CLOUD_MAX_VERSION := $(shell $(XPATH) -q -e 'string(/info/dependencies/nextcloud/@max-version)' $(APP_INFO))

BUILDDIR = ./build
ABSBUILDDIR = $(ABSSRCDIR)/build
BUILD_TOOLS_DIR = $(BUILDDIR)/tools
DOWNLOADS_DIR = ./downloads
CONFIG_DIR = ./config
TYPESCRIPT_CONVERTER = $(ABSSRCDIR)/dev-scripts/php-to-typescript.php
TS_TYPES_DIR = $(ABSBUILDDIR)/ts-types
TS_PHP_SOURCE_DIRS = lib vendor-scoped/rotdrop/nextcloud-app-toolkit/src
WRAPPER_NAMESPACE_POSTFIX = $(SCOPED_NAMESPACE_POSTFIX)
