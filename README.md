# Testable PHP functions

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-tests]
![Coverage Status][ico-coverage]
[![Total Downloads][ico-downloads]][link-packagist]

A library that provides an approach for testing code that heavily relies on PHP's built-in functions or language constructs (great for "include/require-oriented architectures", such as legacy projects) that are normally really hard to test. 

There are no excuses for not testing PHP functions anymore!

## Requirements

* PHP >= 7.1

## Installation

This package is installable and autoloadable via Composer as [filisko/testable-functions](https://packagist.org/packages/filisko/testable-functions).

```sh
composer require filisko/testable-functions
```

## Usage

The package provides two main classes: `Functions` used in production and `FakeFunctions` used for testing.

These two classes allow you to use PHP's built-in functions and language constructs (require_once, include, echo, print, etc.) without having to worry about tests.

You can see a basic [example here](tests/Examples/Email) of production code and its tests, along with many comments to make the example clearer.

### Functions class

This class is like a proxy for PHP functions. It uses the `__call` hook internally to forward function calls to PHP, and it also wraps PHP's language constructs like `require_once` inside functions so that they can be testable.

Using this class can be particularly useful for code that involves I/O operations because the results of those operations can be easily altered for testing purposes later on.

Imagine the following production code:

```php
// this is passed to the constructor of the client class
$functions = new \Filisko\Functions();

// filesystem
$functions->file_exists($path);
$functions->is_dir($dirname);
$functions->is_file($filename);

// network
$functions->checkdnsrr($hostname);
$functions->fsockopen($hostname);

// clock
$functions->time();
$functions->date_create();
```

Then, by using the `FakeFuctions` class in the testing environment, the results of the functions can be easily altered like this:

```php
// ----- inside the PHPUnit test case -----
use PHPUnit\Framework\Assert;

// this is the default value, and it means that
// undefined functions will fall back to their implementation
$failOnMissing = false;

$functions = new \Filisko\FakeFunctions([
    // if you don't add it here, it will fallback to PHP
    'time' => 1417011228,
    'date_create' => new DateTime('2025-05-15'),
    'is_dir' => function(string $path)  {
        // you can assert arguments here
        Assert::assertEquals('/path/to/dir', $path);

        return false;
    },
], $failOnMissing);

$fileManager = new FileManager($functions);

$this->assertEquals(false, $client->do());

// ------- inside the class under test -------

// returns 1417011228
$functions->time();

// returns DateTime('2025-05-15')
$functions->date_create();

// returns false
$functions->is_dir($dirname);
```

Legacy projects are usually "require/include oriented architectures", so the following can be very handy.

As you've read before, this package supports PHP language constructs (parsed differently than functions by PHP) wrapped in functions:

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

This makes it possible to alter them for testing purposes:

```php
// ---------- inside a PHP Unit test ----------

$functions = new \Filisko\FakeFunctions([
    // simulating a file loading global vars
    'require_once' => function() {
        // you should never do this unless you're testing legacy code
        global $var
        $var = 1;
    },
    // you can also load anything (functions, classes, etc.) from a file
    'require' => function() {
        eval(file_get_contents(__DIR__ . '/functions.php'));
    },
    // simulating a file returning a value
    'include' => false,
]);

// ------- inside the class under test --------

// $var now is available
$functions->require_once($path);
global $var;

// returns false
$functions->include($dirname);
```

Keep in mind that loading things like globals will make them available across all the other tests and you may not want this:

- globals (`@backupGlobals`)
- classes or functions (`@runInSeparateProcess`, `@preserveGlobalState disabled`)
- static variables or properties (`@backupStaticAttributes`)

To solve this issue, use PHPUnit's docblocks shown above for each test method or [at class level with `@runTestsInSeparateProcesses`](https://docs.phpunit.de/en/10.5/annotations.html#runtestsinseparateprocesses):

```php
/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
public function test_with_functions_loading(): void
{
    // ...
}
```

Furthermore, passing `--process-isolation` to PHPUnit will globally apply `@runInSeparateProcess` to each single test, but that's not a good practice.

### FakeFunctions class

As shown in the previous examples, this class is used as a replacement for the `Functions` class in the testing environment, but it also provides many helper methods for the tests.

```php
$functions = new \Filisko\FakeFunctions([
    // any value  (can only be used once, otherwise an exception will be thrown)
    'some_function' => true,

    // callables (can only be used once, otherwise an exception will be thrown)
    'some_function' => function() {
        return true;
    },
    
    // this will return the same result no matter how many times it's called
    'some_function' => new FakeStatic($mixed),
   
    // a stack of values that will be used for each function call
    // it throws an exception when the stack is already consumed
    'some_function' => new FakeStack([true, false, 1, 2]),
]);

// We can adjust whether we want to throw an exception when a result for a function is not set,
// yet the function was called anyway (like an unexpected call).
// This configuration defaults to false, which causes a fallback to PHP's native functions when a mock was not set.
// On the other hand, enabling it can be very useful to make sure that only expected calls are made.
// (e.g.: avoid unexpected DB calls in legacy code or external HTTP requests)
$failOnMissing = true;
$functions = new \Filisko\FakeFunctions([
    // this is used to fallback to the native implementation
    // even though $failOnMissing is enabled (it will fail otherwise)
    'mail' => new FakeFallback(),
    
    // if a call is made and not mocked here, it will fail
    'other_function' => true
], $failOnMissing);

// returns a bool of whether a function was called or not
$functions->wasCalled('require_once');

// returns an int of the number of times that a function was called
$functions->wasCalledTimes('require_once');

// returns a list (array<string,int>) of function names together with the pending calls
// e.g.: [ 'function_exists' => 2 ]  
$functions->pendingCalls();

// returns an int for the pending calls of a specific function (it will throw an exception for non-defined function calls)
$functions->pendingCalls('filter_var');

// returns the total of all the pending calls
// (this can be used to assert that all the values were consumed by the end of the test)
$functions->pendingCallsCount();

// returns an array of all the calls together with the arguments of each call
// the example below is the result of two calls for the same function with different argument each time
// e.g.: [ 'require_once' => [['file.php'], ['test.php']] ]
$functions->calls();

// returns an array of calls for one specific function
// e.g.: [['file.php'], ['test.php']]
$functions->calls('require_once');
// e.g.: ['test.php']
$functions->calls('require_once')[1];

// returns the first call of a function (throws an exception if it wasn't called yet)
// e.g.: ['argument']
$functions->first('filter_var');
// e.g.: 'argument'
$functions->first('filter_var')[0];

// returns the first argument of the first function call (throws an exception if it wasn't called yet)
// e.g.: 'argument'
$functions->firstArgument('filter_var');

// returns an array of string[] of all the echos
$functions->echos();

// returns a bool of whether the string was echoed or not
$functions->wasEchoed('Was I echoed?');

// returns an array of string[] of all the prints
$functions->prints();

// returns a bool of whether the string was printed or not
$functions->wasPrinted('Was I printed?');

// returns a bool of whether die() was called or not
$functions->died();

// returns the die code or string that was passed to die($status)
$functions->dieCode();

// returns a bool of whether exit() was called or not
$functions->exited();

// returns the exit code or string that was passed to exit($status)
$functions->exitCode();

// returns a bool of whether a file was included or not
$functions->wasIncluded('file.php');

// returns a bool of whether a file was included once or not
$functions->wasIncludedOnce('file.php');

// returns a bool of whether a file was required or not
$functions->wasRequired('file.php');

// returns a bool of whether a file was required once or not
$functions->wasRequiredOnce('file.php');
```

## Why to use this package?

Why to choose this package over a hundred other tools out there?

Because we prefer simplicity over complicated mocking tools, and we just want enough to accomplish our goal.

This is a common example from other mocking tools:

```php
$builder = new MockBuilder();
$builder->setNamespace(__NAMESPACE__)
        ->setName("time")
        ->setFunction(
            function () {
                return 1417011228;
            }
        );
                    
$mock = $builder->build()

$result = $mock->time();
```

While for us it's simply:

```php
$functions = new FakeFunctions([
    'time' => 1417011228
]);

$result = $functions->time();
```

## Cons

### Injecting `Functions` into the class.

For some, this is a con; for us, it's simply how it should be. We follow the DI principle for almost everything, so why not for this?

In any case, we consider a good practice to always set the `Functions` dependency as the last one so that it doesn't get in our way and also set it as default so that nothing needs to be passed to the constructor on production. Only when testing.

```php
class Filesystem
{
    public function __construct(
        private SomeService $service,
        private Functions $functions = new Functions()
    ) {}
}
```

## Other testing utilities

- PSR-3 fake logger: [filisko/fake-psr3-logger](https://github.com/filisko/fake-psr3-logger)
- PSR-15 middleware dispatcher: [middlewares/utils](https://github.com/middlewares/utils?tab=readme-ov-file#dispatcher) (used in conjuction with PSR-7 and PSR-17)
- PSR-16 fake cache: [kodus/mock-cache](https://github.com/kodus/mock-cache)

---

## License and Contribution

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/filisko/testable-functions.svg?style=flat
[ico-license]: https://img.shields.io/badge/license-MIT-informational.svg?style=flat
[ico-tests]: https://github.com/filisko/testable-functions/workflows/testing/badge.svg
[ico-coverage]: https://coveralls.io/repos/github/filisko/testable-functions/badge.svg?branch=main
[ico-downloads]: https://img.shields.io/packagist/dt/filisko/testable-functions.svg?style=flat

[link-packagist]: https://packagist.org/packages/filisko/testable-functions

