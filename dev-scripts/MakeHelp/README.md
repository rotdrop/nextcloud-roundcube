# MakeHelp: A Documentation Framework for Makefiles
**MakeHelp** is a *documentation framework* for Makefiles that solves a fundamental Makefile problem: Makefiles are hard to document.

Suppose you have a large Makefile with many rules, some of them are probably ambiguously named, like `make build-full` or `make clean-products`. What's the difference between `make build` and `make build-full`? And what are the products cleaned by `make clean-products`? No one can tell.
If you are familiar with the situation, you probably maintain a text document next to your Makefile or on your documentation server, explaining each rule. But that's highly unmaintainable, as the documentation resides outside of your Makefile.

With MakeHelp, Makefile rules documentation moves **into the Makefile** and is **easily accessible** by the developer. For example:

```makefile
MAKE_HELP_DIR=./MakeHelp
include $(MAKE_HELP_DIR)/MakeHelp.mk

#@@ Compile intermediate products only
#@ Compiles the intermediate products, such as .obj files and such.
#@ Requires some things and some more things.
compile-intermediate compile: ;

#@@ Run only the something
#@@ And thats it
#@ Run the something including the something else, but without the third thing
#@ Blablabla Blablabla Blablabla Blabla Blablabla
# This line is not part of the docs
run-something: ;

undocumented-rule: ;

#@private
private-rule: ;

```

The documentation of a rule is placed right above the rule, in specially crafted comments. The comment starting with `#@@` is the *short documentation* of the rule, and the comments starting with `#@` are the *long documentation* of the rule.

After documenting your Makefile rules, you can use `make help` to see a list of all rules and their short documentation:

```bash
$ make help
Below are the rules provided by this Makefile.
For extended help on a specific rule, try `make help-rule` or `make rule-help`

compile-intermediate compile  Compile intermediate products only
run-something                 Run only the something
                              And thats it
help                          Show this help message
help-with-private-rules       Show this help message for all rules, including private rules


Undocumented Rules
------------------
undocumented-rule
```

Then, to see the long documentation of a rule, you can type `make help-<rule>` or `make <rule>-help`, for example:

```bash
$ make help-compile
Help about `make compile`:

Compiles the intermediate products, such as .obj files and such.
Requires some things and some more things.
```

## Private and Undocumented Rules

As a documentation framework, MakeHelp notices any **undocumented rules** you may have left in your Makefile, and when running `make help`, the undocumented rules appear in the *Undocumented Rules* section at the bottom of the help message. This is useful for finding and documenting undocumented rules.

However, sometimes the developer might want a rule to stay undocumented, as it is a private, implementation-specific rule, that shouldn't be executed by the arbitrary user or developer. In that case, the developer can **mark the rule as private**, using the `#@private` comment above the rule. This way, the rule won't show up in the undocumented rules section.

A private rule may also have documentation, just like any other rule, but it won't show up when running `make help`, as it is declared private. To see documentation for all rules, including private rules, use `make help-with-private-rules`.

# Getting Started with MakeHelp

Getting started with MakeHelp couldn't be easier! Just follow these simple steps:

1. Make sure you have the required [dependencies](#Dependencies) installed.

2. Add MakeHelp as a submodule or download the sources. Either way, have MakeHelp as a subdirectory in your project.

3. In your Makefile, add the following two lines:

   ```makefile
   MAKE_HELP_DIR=./MakeHelp
   include $(MAKE_HELP_DIR)/MakeHelp.mk
   ```

   Where `MAKE_HELP_DIR` should point to where MakeHelp is relative to your project's root directory.

And that's it! You have MakeHelp all configured and ready to go.

## Dependencies

MakeHelp requires Python 3 with the `tabulate` library installed. To install dependencies:

```bash
sudo apt install python3
sudo apt install python3-pip
sudo pip3 install tabulate
```
