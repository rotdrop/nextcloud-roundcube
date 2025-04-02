# Makefile fragment for generating a typescript ts-app-config.ts exporting
# appName as string literal and AppName as its type.

TS_APP_CONFIG_IN = $(ABSSRCDIR)/dev-scripts/lib/templates/app-config.ts.in
TS_APP_CONFIG = $(BUILDDIR)/ts-types/app-config.ts

#@private
ts-app-config: $(TS_APP_CONFIG)
.PHONY: ts-app-config

#@private
$(TS_APP_CONFIG): Makefile $(APP_INFO) $(TS_APP_CONFIG_IN)
	mkdir -p $$(dirname $@)
	sed 's/@@APP_NAME@@/$(APP_NAME)/g' $(TS_APP_CONFIG_IN) > $@
