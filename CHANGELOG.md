# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.3.0] - 2025-11-27

### Added

#### `$fakeFunctions->set($function, $value)`

```php
/**
 * @param mixed|FakeStack|FakeStatic $value
 */
public function set(string $string, $value): FakeFunctions;
```

A new method that allows postponing the setting of a function's preset result until it is needed.

Before this addition, everything had to be set in the constructor:

```php
$fakeFunctions = new FakeFunctions([
    'function_exists' => true
]);
```

Now, it's also much easier to keep track of the interacting parts:

```php
$fakeFunctions = new FakeFunctions();

// ... a big chunk of code ...

$fakeFunctions->set('function_exists', true);
```

#### `$fakeFunctions->lastResult($function)` and `$fakeFunctions->results($function)`

These functions allow one to read the results of real function calls (not echo; print; require_once, etc.).

This may come in handy when you want to use the result of a function call from a different context: tests.

Snippet copied from unit tests `\Filisko\Tests\FakeFunctionsTest::test_results`:

```php
$functions = new FakeFunctions();

// e.g.: this returns 5
$result = $functions->mt_rand(1, 5);

// now this will return the same value
$generalWayOfReadingIt = $functions->results('mt_rand')[0];

// and this will too (it will always return the last returned value of a function)
$fasterWayOfReadingIt = $functions->lastResult('mt_rand'));
```

Now imagine this real-world scenario where you need the user ID after creating a new user:

```php
$functions = new FakeFunctions();

$userService = new UserService($functions)

// this method called uuid() inside which generates an UUID,
// and yes, it could have returned a User object with the user ID inside,
// but what if this is not the case? It would force us to return the user ID somehow only for testing
$userService->create('John', 'Doe');

// now we can get the actual user UUID
$userId = $functions->lastResult('uuid');

// and do more things with it
doMoreThingsWithTheUserId($userId);
```

## [1.2.0] - 2025-05-31

### Added

**New testing helpers**:

- `$functions->wasRequired($path)`
- `$functions->wasRequiredOnce($path)`
- `$functions->wasIncluded($path)`
- `$functions->wasIncludedOnce($path)`

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

[1.3.0]: https://github.com/filisko/testable-functions/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/filisko/testable-functions/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/filisko/testable-functions/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/filisko/testable-functions/releases/tag/v1.0.0
