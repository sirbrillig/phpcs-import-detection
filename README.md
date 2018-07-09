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

## Ignoring Symbols

You can ignore certain patterns by using the `ignoreUnimportedSymbols` config option. It is a regular expression. Here is an example:

```xml
	<rule ref="ImportDetection.Imports.RequireImports">
		<properties>
			<property name="ignoreUnimportedSymbols" value="/^(wp_parse_args|OBJECT\S*|ARRAY_\S+|is_wp_error|__|esc_html__|get_blog_\S+)$/"/>
		</properties>
	</rule>
```

## Usage

Most editors have a phpcs plugin available, but you can also run phpcs manually. To run phpcs on a file in your project, just use the command-line as follows (the `-s` causes the sniff code to be shown, which is very important for learning about an error).

```
vendor/bin/phpcs -s src/MyProject/MyClass.php
```
