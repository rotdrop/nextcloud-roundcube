SRCDIR=.
ABSSRCDIR=$(CURDIR)
ABSBUILDDIR=$(CURDIR)/build
DOC_BUILD_DIR=$(ABSBUILDDIR)/artifacts/doc

PHPDOC=/opt/phpDocumentor/bin/phpdoc
PHPDOC_TEMPLATE=--template=default

#--template=clean --template=xml
#--template=responsive-twig

all:

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
	rm -rf ./build

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules

.PHONY: realclean
realclean: distclean
	rm -f composer.lock
	rm -f composer.json
	rm -f stamp.composer-core-versions
