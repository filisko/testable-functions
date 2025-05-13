# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.2.0] - Unreleased

### Added

**New testing helpers**:
  - `$functions->wasRequired($path)`
  - `$functions->wasRequiredOnce($path)`
  - `$functions->wasIncluded($path)`
  - `$functions->wasIncludedOnce($path)`
  - `$functions->errorWasTriggered($message)`

**Support for allowing functions to fallback to their native implementation while using `$failOnMissing=true`.**

Before:
```php
$functions = new FakeFunctions([
    'socket_create' => fn() => socket_create(...func_get_args()),
], true); // fail when a mock is missing, but a call was made
```

After:
```php
$functions = new FakeFunctions([
    'socket_create' => new FakeFallback
], true);

// this is also possible if you want to make the fallback more explicit
$functions = new FakeFunctions([
    'socket_create' => new FakeFallback
]);
```

**Support for static values and callables (FakeStatic) for retrieving the same result multiple times.**

A value that can be used multiple times:
```php
$functions = new FakeFunctions([
    'value' => new FakeStatic(true)
]);

// this will always return 'true'
$functions->value();
$functions->value();
$functions->value();
```

A function that can be called multiple times:
```php
$counter = 3;
$functions = new FakeFunctions([
    'increase' => new FakeStatic(function () use(&$counter) {
        $counter++;
    })
]);

// this can be called
$functions->increase();
$functions->increase();
$functions->increase();

$this->assertSame(3, $counter);
```

### Changed

- Improved verbosity of error messages when the stack was already consumed to include stack's associated function name.

## [1.1.0] - 2025-05-12

### Added

- Support for PHP 7.1. Let's help some legacy projects use this lib!

## [1.0.0] - 2025-05-03

First release.

[1.2.0]: https://github.com/filisko/testable-phpfunctions/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/filisko/testable-phpfunctions/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/filisko/testable-phpfunctions/releases/tag/v1.0.0
