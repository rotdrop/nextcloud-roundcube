# Makefile fragment setting some variables like app-name and cloud
# versions. Needs ABSSRCDIR variable set to the absolute path of the
# source directory.

#
# try to parse the info.xml if we can, only then fall-back to the directory name
#
APP_INFO = $(ABSSRCDIR)/appinfo/info.xml
XPATH = $(shell which xpath 2> /dev/null)
ifneq ($(XPATH),)
APP_NAME = $(shell $(XPATH) -q -e '/info/id/text()' $(APP_INFO))
CLOUD_MIN_VERSION = $(shell $(XPATH) -q -e 'string(/info/dependencies/nextcloud/@max-version)' $(APP_INFO))
CLOUD_MAX_VERSION = $(shell $(XPATH) -q -e 'string(/info/dependencies/nextcloud/@max-version)' $(APP_INFO))
else
$(warning The xpath binary could not be found, falling back to using the CWD as app-name)
APP_NAME = $(notdir $(ABSSRCDIR))
CLOUD_MIN_VERSION = $(shell grep OC_VersionString $(ABSSRCDIR)/../../version.php|sed -E -e 's/^[^0-9]+([0-9]{2}).*$/\1/g')
CLOUD_MAX_VERSION = $(CLOUD_MAX_VERSION)
endif
