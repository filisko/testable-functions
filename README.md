# Fake PHP functions

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-tests]
![Coverage Status][ico-coverage]
[![Total Downloads][ico-downloads]][link-downloads]

This library provides an approach for testing code that heavily relies on PHP's built-in functions or language constructs that are normally really hard to test.

That's why it's great for include/require-oriented architectures, such as legacy projects.

## Requirements

* PHP >= 7.2

## Installation

This package is installable and autoloadable via Composer as [filisko/testable-phpfunctions](https://packagist.org/packages/filisko/testable-phpfunctions).

```sh
composer require filisko/testable-phpfunctions
```

## Usage

The package provides two main classes: `Functions` used in production and `FakeFunctions` used for testing.

Those two classes allow you to use PHP's built-in functions and language constructs (require_once, include, echo, print, etc.) without having to worry about tests.

You can see a basic [example here](tests/Examples/Email) of production code and its tests along with many comments to make the example clearer.

### Functions class

This class is like a proxy to PHP functions. It uses the `__call` hook internally to forward function calls to PHP, and it also wraps PHP's language constructs like `require` inside functions. This way, you have already abstracted yourself from using PHP directly.

Using this class can be particularly useful for code that involves IO operations because later, the result of those can be easily altered for testing purposes.

Imagine the following code as production code:

```php
$functions = new \Filisko\Functions();

// file related
$functions->file_exists($path);
$functions->is_dir($dirname);
$functions->is_file($filename);

// network related
$functions->checkdnsrr($hostname);
$functions->fsockopen($hostname);

// etc...
$functions->password_verify($password);
```

Then, by using the `FakeFuctions` class in the testing environment, the results of the functions can be easily altered like this:

```php
$functions = new \Filisko\FakeFunctions([
    'file_exists' => true,
    'is_dir' => false,
    // ...
]);

// returns true
$functions->file_exists($path);

// returns false
$functions->is_dir($dirname);
```

Legacy projects are usually require/include oriented architectures, so the following can be very handy.

As you've seen before, this package supports PHP language constructs (parsed differently than functions by PHP) wrapped in functions:

```php
$functions->require_once($path);
$functions->require($path);
$functions->include_once($path);
$functions->include($path);
$functions->echo($text);
$functions->print($text);
$functions->exit($statusOrText);
$functions->die($statusOrText);
```

Then these can be easily altered for testing too:

```php
$functions = new \Filisko\FakeFunctions([
    // simulating a file loading global vars
    'require_once' => function() {
        // you should never do this unless you're testing legacy code
        global $var
        $var = 1;
    },
    // simulating a file returning a value
    'include' => false,
]);

// $var now is available
$functions->require_once($path);
global $var;

// returns false
$functions->include($dirname);
```

### FakeFunctions class

As shown in the previous examples, this class is used as a replacement for the Functions class in a testing environment, but it also provides many helper methods.

```php
$functions = new \Filisko\FakeFunctions([
    // any value is supported
    'some_function' => true,

    // callables are supported
    'some_function' => function() {
        return true;
    },

    // a stack of values is supported that will be used for the next function call
    // it will throw a EmptyStackException if you trigger a call but the stack is empty
    'some_function' => new FakeStack([true, false, 1, 2]),
]);

// also, we can adjust if we want FakeFunctions to throw a NotMockedFunction
// when a result for a function was not set, but the function was called anyway (like an unexpected call)
// This configuration defaults to false, which causes the code to fallback to native PHP functions
// e.g.: trim, filter_var, etc. will work normally
$failOnMissing = true;
$functions = new \Filisko\FakeFunctions($mocks, $failOnMissing);

// returns true/false when die() is called
$functions->died();

// returns die code or string passed to die($status)
$functions->dieCode();
```

## Other testing utilities

- PSR-3 fake logger: [filisko/fake-psr3-logger](https://github.com/filisko/fake-psr3-logger)
- PSR-15 middleware dispatcher: [middlewares/utils](https://github.com/middlewares/utils?tab=readme-ov-file#dispatcher) (used in conjuction with PSR-7 and PSR-17)
- PSR-16 fake cache: [kodus/mock-cache](https://github.com/kodus/mock-cache)

---

## License and Contribution

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/filisko/testable-phpfunctions.svg?style=flat
[ico-license]: https://img.shields.io/badge/license-MIT-informational.svg?style=flat
[ico-tests]: https://github.com/filisko/testable-phpfunctions/workflows/testing/badge.svg
[ico-coverage]: https://coveralls.io/repos/github/filisko/testable-phpfunctions/badge.svg?branch=main
[ico-downloads]: https://img.shields.io/packagist/dt/filisko/testable-phpfunctions.svg?style=flat

[link-packagist]: https://packagist.org/packages/filisko/testable-phpfunctions
[link-downloads]: https://packagist.org/packages/filisko/testable-phpfunctions

