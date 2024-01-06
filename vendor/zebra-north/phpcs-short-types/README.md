Short Type Names for PHP Codesniffer
====================================

PHP Codesniffer requires that scalar types be written in their long form, eg. `boolean` or `integer`.

As of PHP 7+, scalars are required to be written in their short form, eg. `bool` or `int`.

This patch allows you to use the short form in doc-block comments.

Before:

```php
/**
 * Test function.
 *
 * @param integer $test A numeric argument.
 *
 * @return boolean Returns true or false.
 */
function test(int $test): bool
{
    return false;
}
```

After:

```php
/**
 * Test function.
 *
 * @param int $test A numeric argument.
 *
 * @return bool Returns true or false.
 */
function test(int $test): bool
{
    return false;
}
```

Errors Fixed
------------

- **Squiz.Commenting.FunctionComment.IncorrectParamVarName**
  - Expected "boolean" but found "bool" for parameter type
  - Expected "integer" but found "int" for parameter type
- **Squiz.Commenting.VariableComment.IncorrectVarType**
  - Expected "boolean" but found "bool" for @var tag in member variable comment
  - Expected "integer" but found "int" for @var tag in member variable comment
- **Squiz.Commenting.FunctionComment.InvalidReturn**
  - Expected "boolean" but found "bool" for function return type
  - Expected "integer" but found "int" for function return type

Installation
------------

1. Install into composer with: `composer require --dev zebra-north/phpcs-short-types`
2. Edit your project's `phpcs.xml` to add a bootstrap file:

```xml
<?xml version="1.0"?>
<ruleset>

    <arg name="bootstrap" value="vendor/zebra-north/phpcs-short-types/short-types.php"/>

    ...

</ruleset>
```

Known Issues
------------

This will _not_ suggest `bool`/`int` if you have used `boolean`/`integer` - that would require patching PHP Codesniffer.  A patch has been created, but not yet merged:

https://github.com/squizlabs/PHP_CodeSniffer/pull/3139
