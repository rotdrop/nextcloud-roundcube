# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
SRCDIR = .
ABSSRCDIR = $(CURDIR)
DEV_LIB_DIR = $(ABSSRCDIR)/dev-scripts/lib

# app specific overrides of the defaults from the shared makefile
TS_PHP_SOURCE_DIRS = lib vendor-$(SCOPED_VENDOR_SUFFIX)/rotdrop/nextcloud-app-toolkit/src

include $(DEV_LIB_DIR)/makefile/app.mk

include $(DEV_LIB_DIR)/makefile/phpunit.mk

#@private
vendor-bin/phpunit/composer.lock: vendor-bin/phpunit/composer.json
	$(COMPOSER) bin phpunit update

#@private
vendor-bin/phing/composer.lock: vendor-bin/phing/composer.json
	$(COMPOSER) bin phing update

$(PHPUNIT): composer.lock vendor-bin/phpunit/composer.lock
	$(COMPOSER) bin phpunit install

$(PHING): composer.lock vendor-bin/phing/composer.lock
	$(COMPOSER) bin phing install
