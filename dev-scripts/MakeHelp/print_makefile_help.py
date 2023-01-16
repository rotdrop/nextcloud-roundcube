import argparse
import re
import os
import textwrap

from tabulate import tabulate


class MakeRule(object):
    def __init__(self, comments, rules):
        self.short = os.linesep.join(re.findall(r'#@@[ \t]*(.*)', comments))
        self.long = os.linesep.join(map(str.strip, re.findall(r'#@([^@][ \t]*.*)', comments)))
        self._is_private = re.search(r'#@private[ \t]*', comments)
        self.joint_name = rules
        self.names = re.findall(r'[\w-]+', rules)

    @property
    def is_undocumented(self):
        return len(self.short) == 0

    @property
    def is_private(self):
        return self._is_private


def _extract_rules(makefiles):
    return [MakeRule(match.group('comments'), match.group('rules'))
            for makefile in makefiles
            for match in re.finditer(r'(?P<comments>(#.*\n)*)^(?P<rules>[\w -]*):',
                                     makefile.read(),
                                     flags=re.MULTILINE)]


def make_help(makefiles, should_show_private_rules):
    rules = _extract_rules(makefiles)
    help_items = []
    undocumented_rules = []
    for rule in rules:
        if rule.is_private and not should_show_private_rules:
            continue
        if rule.is_undocumented:
            undocumented_rules.append(rule.joint_name)
            continue
        help_items.append((rule.joint_name, rule.short))

    return textwrap.dedent("""\
    {rules}


    Undocumented Rules
    ------------------
    {undocumented_rules}
    """).format(rules=tabulate(help_items, tablefmt='plain'),
                undocumented_rules=os.linesep.join(undocumented_rules))


def make_help_rule(makefiles, rule_name):
    rules = _extract_rules(makefiles)
    for rule in rules:
        if rule_name in rule.names:
            return textwrap.dedent("""\
            Help about `make {}`:

            {}
            """).format(rule_name, rule.long)
    return 'Rule `{}` not found'.format(rule_name)


def main():
    parser = argparse.ArgumentParser(description='Print help from a Makefile')
    parser.add_argument('--show-private-rules', '-p', help='Show private rules', action='store_true')
    parser.add_argument('--rule', '-r', help=textwrap.dedent("""\
        The rule to show help about.
        If none is given, help is given for all rules
    """))
    parser.add_argument('makefiles', type=argparse.FileType('r'), nargs='+', help='The path of the Makefile to show help about')
    args = parser.parse_args()
    try:
        if args.rule is None:
            print(textwrap.dedent("""\
                Below are the rules provided by this Makefile.
                For extended help on a specific rule, try `make help-rule` or `make rule-help`
            """))
            print(make_help(args.makefiles, args.show_private_rules))
        else:
            print(make_help_rule(args.makefiles, args.rule))
    finally:
        for makefile in args.makefiles:
            makefile.close()


if __name__ == '__main__':
    main()
