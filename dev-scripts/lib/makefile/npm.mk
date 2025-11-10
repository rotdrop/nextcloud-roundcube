# Make fragment for some generic npm / webpack related targets

WEBPACK_TARGETS = $(ABSSRCDIR)/js/asset-meta.json

WEBPACK_DEPS := $(sort $(WEBPACK_DEPS) Makefile node_modules package-lock.json package.json webpack.config.js .eslintrc.js)

#@private
package-lock.json: package.json webpack.config.js Makefile $(THIRD_PARTY_NPM_DEPS)
	{ [ -d package-lock.json ] && [ test -d node_modules ]; } || $(NPM) install
	$(NPM) update
	touch package-lock.json

#@private
node_modules:
	$(NPM) install
	touch package-lock.json
	touch node_modules

BUILD_FLAVOUR_FILE = $(ABSSRCDIR)/build-flavour
PREV_BUILD_FLAVOUR = $(shell cat $(BUILD_FLAVOUR_FILE) 2> /dev/null || echo)

#@private
build-flavour-dev:
ifneq ($(PREV_BUILD_FLAVOUR), dev)
	make clean
	echo dev > $(BUILD_FLAVOUR_FILE)
endif
.PHONY: build-flavour-dev

#@private
build-flavour-build:
ifneq ($(PREV_BUILD_FLAVOUR), build)
	make clean
	echo build > $(BUILD_FLAVOUR_FILE)
endif
.PHONY: build-flavour-build

#@private
$(WEBPACK_TARGETS): $(BUILD_FLAVOUR_FILE) $(WEBPACK_DEPS)
	make webpack-clean
	@env LC_ALL=C make $(WEBPACK_DEPS) 2>&1 | grep -vE '(Nothing to be done for|is up to date)'
	$(NPM) run $(shell cat $(BUILD_FLAVOUR_FILE)) || rm -f $(WEBPACK_TARGETS)

#@private
npm-dev: build-flavour-dev $(WEBPACK_TARGETS)
.PHONY: npm-dev

#@private
npm-build: build-flavour-build $(WEBPACK_TARGETS)
.PHONY: npm-build

#@@ Linting
lint:
	$(NPM) run lint
.PHONY: lint

#@@ Lint and fix (be careful!)
lint-fix:
	$(NPM) run lint:fix
.PHONY: lint-fix

#@@ Style linting
stylelint:
	$(NPM) run stylelint
.PHONY: stylelint

#@@ Style linting and apply fixes (be carful!)
stylelint-fix:
	$(NPM) run stylelint:fix
.PHONY: stylelint-

#@@ Removes WebPack builds
webpack-clean:
	rm -rf ./js/*
	rm -rf ./css/*
.PHONY: webpack-clean
