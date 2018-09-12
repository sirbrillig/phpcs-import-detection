# ImportDetection

A set of [phpcs](https://github.com/squizlabs/PHP_CodeSniffer) sniffs to look for unused or unimported symbols.

This adds a sniff which shows warnings if a symbol (function, constant, class) is used and is not defined directly, imported explicitly, nor has its namespace imported.

When code is moved around, it can be problematic if classes which are used in a relative or global context get moved to a different namespace. In those cases it's better if the classes use their fully-qualified namespace, or if they are imported explicitly using `use` (in which case they can be detected by a linter like this one). These warnings should help when refactoring code to avoid bugs.

It also detects imports which are _not_ being used.

For example:

```php
namespace Vehicles;
use Registry;
use function Vehicles\startCar;
use Chocolate; // this will be a warning because `Chocolate` is never used
class Car {
  public function drive() {
    startCar(); // this is fine because `startCar` is imported
    Registry\registerCar($this); // this is fine because `Registry` is imported
    \DrivingTracker\registerDrive($this); // this is fine because it's fully-qualified
    goFaster(); // this will be a warning because `goFaster` was not imported
  }
}
```

## Installation

To use these rules in a project which is set up using [composer](https://href.li/?https://getcomposer.org/), we recommend using the [phpcodesniffer-composer-installer library](https://href.li/?https://github.com/DealerDirect/phpcodesniffer-composer-installer) which will automatically use all installed standards in the current project with the composer type `phpcodesniffer-standard` when you run phpcs.

```
composer require --dev squizlabs/php_codesniffer dealerdirect/phpcodesniffer-composer-installer
composer require --dev sirbrillig/phpcs-import-detection
```

## Configuration

When installing sniff standards in a project, you edit a `phpcs.xml` file with the `rule` tag inside the `ruleset` tag. The `ref` attribute of that tag should specify a standard, category, sniff, or error code to enable. It’s also possible to use these tags to disable or modify certain rules. The [official annotated file](https://href.li/?https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-ruleset.xml) explains how to do this.

```xml
<?xml version="1.0"?>
<ruleset name="MyStandard">
 <description>My library.</description>
 <rule ref="ImportDetection"/>
</ruleset>
```

## Sniff Codes

There are two sniff codes that are reported by this sniff. Both are warnings.

- `ImportDetection.Imports.RequireImports.Symbol`: A symbol has been used but not imported
- `ImportDetection.Imports.RequireImports.Import`: A symbol has been imported and not used

In any given file, you can use phpcs comments to disable these sniffs. For example, if you have a global class called `MyGlobalClass` which you don't want to import, you could use it like this:

```php
<?php

$instance = new MyGlobalClass(); // phpcs:ignore ImportDetection.Imports.RequireImports.Symbol -- this class is global
$instance->doSomething();
```

For a whole file, you can ignore a sniff like this:

```php
<?php
// phpcs:disable ImportDetection.Imports.RequireImports.Symbol

$instance = new MyGlobalClass();
$instance->doSomething();
```

For a whole project, you can use the `phpcs.xml` file to disable these sniffs or modify their priority. For example, to disable checks for unused imports, you could use a configuration like this:

```xml
<?xml version="1.0"?>
<ruleset name="MyStandard">
 <description>My library.</description>
 <rule ref="ImportDetection"/>
 <rule ref="ImportDetection.Imports.RequireImports.Import">
   <severity>0</severity>
 </rule>
</ruleset>
```

## Ignoring Symbol Patterns

Oftentimes there might be global symbols that you want to use without importing or using a fully-qualified path.

(Remember that function call resolution first searches the current namespace, then the global namespace, but constant and class resolution only searches the current namespace! You still have to import things like `Exception` or use the fully-qualified `\Exception`.)

You can ignore certain patterns by using the `ignoreUnimportedSymbols` config option. It is a regular expression. Here is an example for some common WordPress symbols:

```xml
<?xml version="1.0"?>
<ruleset name="MyStandard">
 <description>My library.</description>
 <rule ref="ImportDetection"/>
 <rule ref="ImportDetection.Imports.RequireImports">
   <properties>
    <property name="ignoreUnimportedSymbols" value="/^(wp_parse_args|OBJECT\S*|ARRAY_\S+|is_wp_error|__|esc_html__|get_blog_\S+)$/"/>
  </properties>
 </rule>
</ruleset>
```

Despite the name, you can also use the `ignoreUnimportedSymbols` pattern to ignore specific unused imports.

## Ignoring Global Symbols in Global Namespace

If a file is in the global namespace, then sometimes it may be unnecessary to import functions that are also global. If you'd like to ignore global symbol use in the global namespace, you can enable the `ignoreGlobalsWhenInGlobalScope` option, like this:

```xml
<?xml version="1.0"?>
<ruleset name="MyStandard">
 <description>My library.</description>
 <rule ref="ImportDetection"/>
 <rule ref="ImportDetection.Imports.RequireImports">
   <properties>
    <property name="ignoreGlobalsWhenInGlobalScope" value="true"/>
  </properties>
 </rule>
</ruleset>
```

## Ignoring WordPress Patterns

A common use-case is to ignore all the globally available WordPress symbols. Rather than trying to come up with a pattern to ignore them all yourself, you can set the config option `ignoreWordPressSymbols` which will ignore as many of them as it knows about. For example:

```xml
<?xml version="1.0"?>
<ruleset name="MyStandard">
 <description>My library.</description>
 <rule ref="ImportDetection"/>
 <rule ref="ImportDetection.Imports.RequireImports">
   <properties>
    <property name="ignoreWordPressSymbols" value="true"/>
  </properties>
 </rule>
</ruleset>
```

## Usage

Most editors have a phpcs plugin available, but you can also run phpcs manually. To run phpcs on a file in your project, just use the command-line as follows (the `-s` causes the sniff code to be shown, which is very important for learning about an error).

```
vendor/bin/phpcs -s src/MyProject/MyClass.php
```

## See Also

- [VariableAnalysis](https://github.com/sirbrillig/phpcs-variable-analysis): Find undefined and unused variables.
