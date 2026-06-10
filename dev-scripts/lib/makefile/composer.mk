# Makefile fragment in order to tweak composer dependencies not to be
# in conflict with CORE/3rdparty

ifeq (1, $(NO_STDOUT))
POSTFIX = 1>&2
endif

#@private
composer.json: composer.json.in
	cp composer.json.in composer.json

#@private
stamp.composer-core-versions: composer.lock
	date > $@

#@private
composer.lock: DRY:=
#@private
composer.lock: composer.json composer.json.in
	echo '$(POSTFIX)'
	rm -f composer.lock $(POSTFIX)
	$(COMPOSER) install $(COMPOSER_OPTIONS) $(POSTFIX)
	env DRY=$(DRY) $(DEV_LIB_DIR)/scripts/tweak-composer-json.sh "$(ABSSRCDIR)" $(POSTFIX) || {\
 rm -f composer.lock;\
 $(COMPOSER) install $(COMPOSER_OPTIONS);\
} $(POSTFIX)

#@private
composer-download:
	mkdir -p $(BUILD_TOOLS_DIR) $(POSTFIX)
	curl -sS https://getcomposer.org/installer | php $(POSTFIX)
	mv composer.phar $(BUILD_TOOLS_DIR) $(POSTFIX)
.PHONY: composer-download

#@@ Installs and updates the composer dependencies. If composer is not installed
#@@ a copy is fetched from the web
composer: stamp.composer-core-versions
	$(COMPOSER) install $(COMPOSER_OPTIONS) $(POSTFIX)
.PHONY: composer

#@@ Display the composer suggestions
composer-suggest:
	@echo -e "\n*** Regular Composer Suggestions ***\n"
	$(COMPOSER) suggest --all
.PHONY: composer-suggest
