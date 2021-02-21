SRCDIR=.
ABSSRCDIR=$(CURDIR)
SRC_BASE=$(notdir $(ABSSRCDIR))
ABSBUILDDIR=$(CURDIR)/build
DOC_BUILD_DIR=$(ABSBUILDDIR)/artifacts/doc
APP_NAME=$(shell xmllint --xpath 'string(/info/id)' $(ABSSRCDIR)/appinfo/info.xml|tr '[:upper:]' '[:lower:]')
APPSTORE_BUILD_DIRECTORY=$(ABSBUILDDIR)/artifacts/appstore
APPSTORE_PACKAGE_NAME=$(APPSTORE_BUILD_DIRECTORY)/$(APP_NAME)

COMPOSER_SYSTEM=$(shell which composer 2> /dev/null)
ifeq (, $(COMPOSER_SYSTEM))
COMPOSER=php $(build_tools_directory)/composer.phar
else
COMPOSER=$(COMPOSER_SYSTEM)
endif
COMPOSER_OPTIONS=--no-dev --prefer-dist

PHPDOC=/opt/phpDocumentor/bin/phpdoc
PHPDOC_TEMPLATE=--template=default

#--template=clean --template=xml
#--template=responsive-twig

all: build

build: npm # composer

.PHONY: composer
composer:
	$(COMPOSER) install $(COMPOSER_OPTIONS)

.PHONY: npm-update
npm-update:
	npm update

.PHONY: npm-init
npm-init:
	npm install

# Installs npm dependencies
.PHONY: npm
npm: npm-init
	npm run dev

.PHONY: doc
doc: $(PHPDOC) $(DOC_BUILD_DIR)
	rm -rf $(DOC_BUILD_DIR)/phpdoc/*
	$(PHPDOC) run \
 $(PHPDOC_TEMPLATE) \
 --force \
 --parseprivate \
 --visibility api,public,protected,private,internal \
 --sourcecode \
 --defaultpackagename $(app_name) \
 -d $(ABSSRCDIR)/lib -d $(ABSSRCDIR)/appinfo \
 --setting graphs.enabled=true \
 --cache-folder $(ABSBUILDDIR)/phpdoc/cache \
 -t $(DOC_BUILD_DIR)/phpdoc

$(DOC_BUILD_DIR):
	mkdir -p $@

# Removes the appstore build
.PHONY: clean
clean:
	rm -rf js/*
	rm -rf css/*
	rm -rf ./build

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules
	rm -f *.html
	find . -name "*~" -exec rm -f {} \;

.PHONY: realclean
realclean: distclean
	rm -f composer.lock
	rm -f composer.json
	rm -f stamp.composer-core-versions
	rm -f package-lock.json

# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(APPSTORE_BUILD_DIRECTORY)
	mkdir -p $(APPSTORE_BUILD_DIRECTORY)
	tar cvzf $(APPSTORE_PACKAGE_NAME).tar.gz \
	--exclude-vcs \
	--exclude="*~" \
	--exclude="../$(SRC_BASE)/src" \
	--exclude="../$(SRC_BASE)/style" \
	--exclude="../$(SRC_BASE)/build" \
	--exclude="../$(SRC_BASE)/tests" \
	--exclude="../$(SRC_BASE)/Makefile" \
	--exclude="../$(SRC_BASE)/*.log" \
	--exclude="../$(SRC_BASE)/phpunit*xml" \
	--exclude="../$(SRC_BASE)/composer.*" \
	--exclude="../$(SRC_BASE)/node_modules" \
	--exclude="../$(SRC_BASE)/translationfiles" \
	--exclude="../$(SRC_BASE)/tests" \
	--exclude="../$(SRC_BASE)/test" \
	--exclude="../$(SRC_BASE)/*.log" \
	--exclude="../$(SRC_BASE)/*.html" \
	--exclude="../$(SRC_BASE)/webpack.config.js" \
	--exclude="../$(SRC_BASE)/package.json" \
	--exclude="../$(SRC_BASE)/package-lock.json" \
	--exclude="../$(SRC_BASE)/bower.json" \
	--exclude="../$(SRC_BASE)/karma.*" \
	--exclude="../$(SRC_BASE)/protractor.*" \
	--exclude="../$(SRC_BASE)/package.json" \
	--exclude="../$(SRC_BASE)/bower.json" \
	--exclude="../$(SRC_BASE)/karma.*" \
	--exclude="../$(SRC_BASE)/protractor\.*" \
	--exclude="../$(SRC_BASE)/.*" \
	--exclude="../$(SRC_BASE)/blah" \
        ../$(SRC_BASE)
