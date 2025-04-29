# Fake PHP functions

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-ga]
![Coverage Status][ico-coverage]
[![Total Downloads][ico-downloads]][link-downloads]

This library provides a particularly useful approach for testing code that heavily relies on PHP built-in functions or language constructs that are normally challenging to test.

It's a great package to start adding tests to legacy code, or simply when one wants to use PHP's API. 

## Requirements

* PHP >= 7.2

## Installation

This package is installable and autoloadable via Composer as [filisko/testable-phpfunctions](https://packagist.org/packages/filisko/testable-phpfunctions).

```sh
composer require filisko/testable-phpfunctions
```

## Usage

This package provides two main classes: `FakeFunctions` used for testing environment with many helper methods (e.g.: PHPUnit) and `Functions`; the class you will use in production that provides a clean, injectable abstraction that forwards calls to PHP functions.

Also, this package allows you to test places in your code that use PHP language constructs like require_once, include, echo, print, etc. that are not functions and are really hard to test. With this library you can easily do it.

You can see a basic [example here](tests/Examples/Email) of production code and its tests along with many comments to make the example clearer.

### Functions class

This class accepts any PHP function that you would normally call as a method.

This can be particularly useful for operations that involve IO:

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

These can be mocked in a such a simple manner:

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

It also supports PHP language constructs, which generally are really hard to test.

Legacy projects are usually require/include oriented-architectures, so this can come very handy.

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

An example mocking those would be:

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

#### 

## Other testing utilities

- PSR-3 fake logger: [filisko/fake-psr3-logger](https://github.com/filisko/fake-psr3-logger)
- PSR-15 middleware dispatcher: [middlewares/utils](https://github.com/middlewares/utils?tab=readme-ov-file#dispatcher) (used in conjuction with PSR-7 and PSR-17)
- PSR-16 fake cache: [kodus/mock-cache](https://github.com/kodus/mock-cache)

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/filisko/testable-phpfunctions.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ga]: https://github.com/filisko/testable-phpfunctions/workflows/testing/badge.svg
[ico-coverage]: https://coveralls.io/repos/github/filisko/testable-phpfunctions/badge.svg?branch=main
[ico-downloads]: https://img.shields.io/packagist/dt/filisko/testable-phpfunctions.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/filisko/testable-phpfunctions
[link-downloads]: https://packagist.org/packages/filisko/testable-phpfunctions
