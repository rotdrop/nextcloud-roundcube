# Makefile fragment in order to automate the "scoping"

# The consuming Makefile has to define the following three variables
# and ABSSRCDIR (values as examples):
#
# APP_TOOLKIT_DIR = $(ABSSRCDIR)/php-toolkit
# APP_TOOLKIT_DEST = $(ABSSRCDIR)/lib/Toolkit
# APP_TOOLKIT_NS = CAFEVDB
#
# optional
# APP_WRAPPER_NS = Wrapped

ifeq (1, $(NO_STDOUT))
POSTFIX = 1>&2
endif

APP_TOOLKIT_BUILD_HASH = app-toolkit-build-hash

APP_TOOLKIT_PREV_BUILD_HASH = $(shell cat $(ABSSRCDIR)/$(APP_TOOLKIT_BUILD_HASH) 2> /dev/null || echo)
APP_TOOLKIT_GIT_BUILD_HASH = $(shell { $(APP_TOOLKIT_DIR:%=D=%; echo $$D; git -C $$D rev-parse HEAD;) })

$(APP_TOOLKIT_BUILD_HASH):
	@echo "GIT dependencies of the wrapped app-toolkit have changed, need to rebuild the wrapper" $(POSTFIX)
	@echo "OLD HASH $(APP_TOOLKIT_PREV_BUILD_HASH)" $(POSTFIX)
	@echo "NEW HASH $(APP_TOOLKIT_GIT_BUILD_HASH)" $(POSTFIX)
	echo $(APP_TOOLKIT_GIT_BUILD_HASH) > $@
ifneq ($(APP_TOOLKIT_PREV_BUILD_HASH), $(APP_TOOLKIT_GIT_BUILD_HASH))
.PHONY: $(APP_TOOLKIT_BUILD_HASH)
endif

$(APP_TOOLKIT_DEST)/README.md: $(APP_TOOLKIT_BUILD_HASH) Makefile $(APP_TOOLKIT_DIR)/tools/scopeme.sh
	$(APP_TOOLKIT_DIR)/tools/scopeme.sh $(APP_TOOLKIT_DEST) $(APP_TOOLKIT_NS) "$(APP_WRAPPER_NS)" $(POSTFIX)

#@@ Copy the PHP-toolkit to the configured directory and replace the namespace prefix by the configured one.
app-toolkit: $(APP_TOOLKIT_DEST)/README.md
.PHONY: app-toolkit
