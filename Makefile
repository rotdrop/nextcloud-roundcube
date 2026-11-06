# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
SRCDIR = .
ABSSRCDIR = $(CURDIR)
DEV_LIB_DIR = $(ABSSRCDIR)/dev-scripts/lib

# app specific overrides of the defaults from the shared makefile
TS_PHP_SOURCE_DIRS = lib vendor-$(SCOPED_VENDOR_SUFFIX)/rotdrop/nextcloud-app-toolkit/src

include $(DEV_LIB_DIR)/makefile/app.mk
