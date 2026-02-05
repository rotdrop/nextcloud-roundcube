# Makefile fragment for generating a typescript ts-app-config.ts exporting
# appName as string literal and AppName as its type.

ifeq ($(TS_TYPES_DIR),)
TS_TYPES_DIR := $(BUILDDIR)/ts-types
endif
ifeq ($(SCSS_VARIABLES_DIR),)
SCSS_VARIABLES_DIR := $(ABSBUILDDIR)/scss-variables
endif

TS_APP_CONFIG_IN = $(ABSSRCDIR)/dev-scripts/lib/templates/app-config.ts.in
TS_APP_CONFIG = $(TS_TYPES_DIR)/app-config.ts
SCSS_APP_CONFIG = $(SCSS_VARIABLES_DIR)/app-config.scss

#@private
ts-app-config: $(TS_APP_CONFIG) $(SCSS_APP_CONFIG)
.PHONY: ts-app-config

#@private
$(TS_APP_CONFIG): Makefile $(DEV_LIB_DIR)/makefile/ts-app-config.mk $(APP_INFO) $(TS_APP_CONFIG_IN)
	mkdir -p $$(dirname $@)
	sed -e 's/@@APP_NAME@@/$(APP_NAME)/g' -e 's/@@APP_VERSION@@/$(APP_VERSION)/g' $(TS_APP_CONFIG_IN) > $@

#@private
$(SCSS_APP_CONFIG): Makefile $(DEV_LIB_DIR)/makefile/ts-app-config.mk $(APP_INFO)
	mkdir -p $$(dirname $@)
	echo '$$appName: "$(APP_NAME)";' > $@
	echo '$$appVersion: "$(APP_VERSION)";' >> $@
