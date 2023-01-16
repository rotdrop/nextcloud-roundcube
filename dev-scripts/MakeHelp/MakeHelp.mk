# When included, provides the following commands:
# `make help`
# `make`
# that shows all the rules supported and their short documentation.
#
# And provides the commands `make help-<rule>` and `make <rule>-help` for each existing rule.
#
# How to short-document a rule?
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Above the rule, add a comment starting with #@@ to set the short documentation for the rule.
# The short documentation will be printed in the `make help` command.
# There can be additional comment lines between the #@@ and the rule itself
#
# How to long-document a rule?
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Above the rule, add a comment starting with #@ to set the long documentation.
# The long documentation will be printed in the `make help-rule` or `make rule-help` commands.
# The long documentation can be multiline. There can be additional comment lines below, that aren't part of the doc.
#
# What about undocumented rules?
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Rules that are not documented will be printed at the end of the help message, in the "Undocumented Rules" section.
#
# Defining a Private Rule
# ~~~~~~~~~~~~~~~~~~~~~~~
# You can define a private rule by adding the following marker above the rule:
#
# 	#@private
#
# A private rule won't show up on `make help`.
#
# To get help about all rules including private rules, use `make help-with-private-rules`.

ifndef MAKE_HELP_DIR
$(error Variable MAKE_HELP_DIR must be defined and point to MakeHelp root directory)
endif

.DEFAULT_GOAL: help
.PHONY: help
#@@ Show this help message
#@ Show the list of rules supported by the Makefile and their description
help:
	@python $(MAKE_HELP_DIR)/print_makefile_help.py $(MAKEFILE_LIST)

#@@ Show this help message for all rules, including private rules
#@ Similar to `make help`, including private rules
help-with-private-rules:
	@python $(MAKE_HELP_DIR)/print_makefile_help.py --show-private-rules $(MAKEFILE_LIST)

%-help help-%:
	@python $(MAKE_HELP_DIR)/print_makefile_help.py --rule "$*" $(MAKEFILE_LIST)
