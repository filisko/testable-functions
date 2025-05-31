<?php

declare(strict_types=1);

namespace Filisko\Tests;

use BadMethodCallException;
use Filisko\FakeFallback;
use Filisko\FakeFunctions;
use Filisko\FakeStack;
use Filisko\FakeStack\StackConsumed;
use Filisko\FakeStack\NotMockedFunction;
use Filisko\FakeStack\WasNotCalled;
use Filisko\FakeStatic;
use PHPUnit\Framework\TestCase;

class FakeFunctionsTest extends TestCase
{
    public function test_it_supports_callables(): void
    {
        $functions = new FakeFunctions([
            'some_function' => function (string $param) {
                return $param;
            }
        ]);

        $result = $functions->some_function('test');

        $this->assertEquals('test', $result);

        // callable values are logged
        $this->assertEquals([
            'some_function' => [
                ['test'],
            ],
        ], $functions->calls());

        $this->assertTrue($functions->wasCalled('some_function'));
    }

    public function test_it_supports_values_directly(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => true
        ]);

        $result = $functions->function_exists('test');

        $this->assertSame(true, $result);

        // values are logged
        $this->assertEquals([
            'function_exists' => [
                ['test'],
            ],
        ], $functions->calls());

        $this->assertTrue($functions->wasCalled('function_exists'));
    }

    public function test_it_supports_a_stack_of_values(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => new FakeStack([
                // first execution
                false,
                // second execution
                function ($param) {
                    return '1'.$param;
                },
                // third execution
                true
            ])
        ]);

        $this->assertSame(false, $functions->function_exists());
        $this->assertEquals('1test', $functions->function_exists('test'));


        $this->assertSame(true, $functions->function_exists());
        // functions using stacks are logged
        $this->assertEquals([
            'function_exists' => [
                [],
                ['test'],
                [],
            ],
        ], $functions->calls());

        $this->assertTrue($functions->wasCalled('function_exists'));
    }

    public function test_it_throws_an_exception_when_value_is_needed_but_all_the_stack_was_consumed(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => new FakeStack(['one'])
        ]);

        // first execution
        $this->assertSame('one', $functions->function_exists('test'));

        // second execution requires a value but the stack is empty
        $this->expectException(StackConsumed::class);
        $this->assertSame(true, $functions->function_exists('test'));
    }

    public function test_it_throws_an_exception_when_value_is_needed_but_it_was_already_consumed(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => false
        ]);

        // first execution
        $this->assertEquals(false, $functions->function_exists('test'));

        // second execution
        $this->expectException(StackConsumed::class);
        $this->expectExceptionMessage('Mocked result of "function_exists" function was already consumed');

        $functions->function_exists('test');
    }

    public function test_it_throws_an_exception_when_callable_is_called_but_it_was_already_consumed(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => function ($param) {
                return $param;
            }
        ]);

        // first execution
        $this->assertEquals('test', $functions->function_exists('test'));

        // second execution
        $this->expectException(StackConsumed::class);
        $this->expectExceptionMessage('Mocked result of "function_exists" function was already consumed');

        $functions->function_exists('test');
    }

    public function test_it_throws_an_exception_when_a_function_is_called_but_the_stack_was_already_consumed(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => new FakeStack([true])
        ]);

        // first execution
        $this->assertEquals('test', $functions->function_exists('test'));

        // second execution
        $this->expectException(StackConsumed::class);
        $this->expectExceptionMessage('Stack of "function_exists" function was already consumed');

        $functions->function_exists('test');
    }

    public function test_static_fakes_values_can_be_used_multiple_times(): void
    {
        $functions = new FakeFunctions([
            'extract' => new FakeStatic(1)
        ]);

        $this->assertSame(1, $functions->extract());
        $this->assertSame(1, $functions->extract());
        $this->assertSame(1, $functions->extract());

        $this->assertSame(3, $functions->wasCalledTimes('extract'));
    }

    public function test_static_fakes_callables_can_be_called_multiple_times(): void
    {
        $counter = 0;

        $functions = new FakeFunctions([
            'increase' => new FakeStatic(function () use(&$counter) {
                $counter++;
            })
        ]);

        $this->assertSame(0, $counter);

        $functions->increase();
        $this->assertSame(1, $counter);

        $functions->increase();
        $this->assertSame(2, $counter);

        $functions->increase();
        $this->assertSame(3, $counter);

        $this->assertSame(3, $functions->wasCalledTimes('increase'));
    }

    public function test_static_fakes_callables_support_arguments(): void
    {
        $functions = new FakeFunctions([
            'increase' => new FakeStatic(function (string $argument1, int $argument2) {
                return [$argument1, $argument2];
            })
        ]);

        $this->assertEquals(['1', 1], $functions->increase('1', 1));
    }

    public function test_it_throws_an_exception_when_result_for_function_is_not_set_and_failOnMissing_is_set(): void
    {
        $functions = new FakeFunctions([], true);

        // trim is note defined
        $this->expectException(NotMockedFunction::class);
        $result = $functions->trim('       test       ');

        $this->assertSame('test', $result);
    }

    public function test_it_delegates_to_php_when_result_for_function_is_not_set_and_failOnMissing_is_disabled(): void
    {
        $functions = new FakeFunctions();

        $result = $functions->trim('       test       ');

        $this->assertSame('test', $result);

        // native functions are logged
        $this->assertEquals([
            'trim' => [
                ['       test       '],
            ],
        ], $functions->calls());

        $this->assertTrue($functions->wasCalled('trim'));
    }

    public function test_fake_fallback_forwards_the_call_to_php(): void
    {
        // with failOnMissing disabled it also works
        $functions = new FakeFunctions([
            'trim' => new FakeFallback,
        ]);
        $this->assertSame('test', $functions->trim('       test       '));

        $functions = new FakeFunctions([
            'trim' => new FakeFallback,
        ], true);

        $this->assertSame('test2', $functions->trim('       test2       '));
    }

    /**
     * @runInSeparateProcess It has $test global.
     */
    public function test_require_once(): void
    {
        $functions = new FakeFunctions([
            'require_once' => new FakeStack(['one', function () {
                // simulating a file defining globals
                global $test;
                $test = 123;
            }])
        ]);

        $this->assertSame('one', $functions->require_once('file.php'));

        // accessing the global again
        global $test;
        $functions->require_once('test');

        $this->assertSame(123, $test);

        // run() functions logged
        $this->assertEquals([
            'require_once' => [
                ['file.php'],
                ['test'],
            ],
        ], $functions->calls());

        // run() functions logged
        $this->assertEquals([
            ['file.php'],
            ['test'],
        ], $functions->calls('require_once'));

        $this->assertTrue($functions->wasCalled('require_once'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_require_once_fallback(): void
    {
        $functions = new FakeFunctions();

        $this->assertSame(100, $functions->require_once(__DIR__.'/FakeFunctionsTest/file.php'));

        // already loaded
        $this->assertSame(true, $functions->require_once(__DIR__.'/FakeFunctionsTest/file.php'));
    }

    public function test_require(): void
    {
        $functions = new FakeFunctions([
            'require' => new FakeStack([function ($path) {
                return $path;
            }, 'two'])
        ]);

        $this->assertSame('file.php', $functions->require('file.php'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_require_fallback(): void
    {
        $functions = new FakeFunctions();

        $this->assertSame(100, $functions->require(__DIR__.'/FakeFunctionsTest/file.php'));
        $this->assertSame(100, $functions->require(__DIR__.'/FakeFunctionsTest/file.php'));
    }

    public function test_include_once(): void
    {
        $functions = new FakeFunctions([
            'include_once' => new FakeStack(['test'])
        ]);

        $this->assertSame('test', $functions->include_once('file.php'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_include_once_fallback(): void
    {
        $functions = new FakeFunctions();

        $this->assertSame(100, $functions->include_once(__DIR__.'/FakeFunctionsTest/file.php'));
        // already loaded
        $this->assertSame(true, $functions->include_once(__DIR__.'/FakeFunctionsTest/file.php'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_include(): void
    {
        $functions = new FakeFunctions([
            'include' => new FakeStack([''])
        ]);

        $this->assertSame('', $functions->include('file.php'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_include_fallback(): void
    {
        $functions = new FakeFunctions();

        $this->assertSame(100, $functions->include(__DIR__.'/FakeFunctionsTest/file.php'));
    }

    public function test_exit(): void
    {
        $functions = new FakeFunctions();

        $this->assertFalse($functions->exited());

        $functions->exit(1);

        $this->assertTrue($functions->exited());
        $this->assertSame(1, $functions->wasCalledTimes('exit'));
        $this->assertSame(1, $functions->exitCode());
    }

    public function test_exitCode_throws_exception(): void
    {
        $functions = new FakeFunctions();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Exit was never called. Use: exited() first');
        $this->assertSame(1, $functions->exitCode());
        $functions->exit(1);
    }


    public function test_dieCode_throws_exception(): void
    {
        $functions = new FakeFunctions();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Die was never called. Use: died() first');
        $this->assertSame(1, $functions->dieCode());
        $functions->exit(1);
    }

    public function test_die(): void
    {
        $functions = new FakeFunctions();

        $this->assertFalse($functions->died());

        $functions->die("Bye bye");

        $this->assertTrue($functions->died());
        $this->assertSame(1, $functions->wasCalledTimes('die'));
        $this->assertSame("Bye bye", $functions->dieCode());
    }

    public function test_echo(): void
    {
        $functions = new FakeFunctions();

        $this->assertEmpty($functions->echos());
        $this->assertFalse($functions->wasEchoed('Second one'));

        $functions->echo("Bye bye");
        $this->assertSame(["Bye bye"], $functions->echos());

        $functions->echo('Second one');
        $this->assertSame([
            "Bye bye",
            "Second one",
        ], $functions->echos());

        $this->assertTrue($functions->wasEchoed('Second one'));

        $this->assertEquals([
            'echo' => [
                ['Bye bye'],
                ['Second one'],
            ],
        ], $functions->calls());

        $this->assertTrue($functions->wasCalled('echo'));
        $this->assertSame(2, $functions->wasCalledTimes('echo'));
    }

    public function test_print(): void
    {
        $functions = new FakeFunctions();

        $this->assertEmpty($functions->prints());
        $this->assertFalse($functions->wasPrinted('Bye bye'));

        $functions->print("Bye bye");
        $this->assertSame(["Bye bye"], $functions->prints());

        $functions->print('Second one');
        $this->assertSame([
            "Bye bye",
            "Second one",
        ], $functions->prints());

        $this->assertTrue($functions->wasPrinted('Bye bye'));

        $this->assertEquals([
            'print' => [
                ['Bye bye'],
                ['Second one'],
            ],
        ], $functions->calls());

        $this->assertTrue($functions->wasCalled('print'));
    }

    public function test_wasRequired(): void
    {
        $functions = new FakeFunctions([
            'require' => true
        ]);

        $this->assertFalse($functions->wasRequired('require.php'));

        $functions->require("require.php");
        $this->assertTrue($functions->wasRequired('require.php'));
    }

    public function test_wasRequiredOnce(): void
    {
        $functions = new FakeFunctions([
            'require_once' => true
        ]);

        $this->assertFalse($functions->wasRequiredOnce('require_once.php'));

        $functions->require_once("require_once.php");
        $this->assertTrue($functions->wasRequiredOnce('require_once.php'));
    }

    public function test_wasIncluded(): void
    {
        $functions = new FakeFunctions([
            'include' => true
        ]);

        $this->assertFalse($functions->wasIncluded('include.php'));

        $functions->include("include.php");
        $this->assertTrue($functions->wasIncluded('include.php'));
    }

    public function test_wasIncludedOnce(): void
    {
        $functions = new FakeFunctions([
            'include_once' => true
        ]);

        $this->assertFalse($functions->wasIncludedOnce('include_once.php'));

        $functions->include_once("include_once.php");
        $this->assertTrue($functions->wasIncludedOnce('include_once.php'));
    }

    public function test_errorWasTriggered(): void
    {
        $functions = new FakeFunctions([
            'include_once' => true
        ]);

        $this->assertFalse($functions->wasIncludedOnce('include_once.php'));

        $functions->include_once("include_once.php");
        $this->assertTrue($functions->wasIncludedOnce('include_once.php'));
    }

    public function test_pending_calls(): void
    {
        $functions = new FakeFunctions([
            'some_function' => new FakeStack([true, false]),
            'function_exists' => new FakeStack([true]),
            'value' => true,
            'callable' => function () {
                return true;
            }
        ]);

        $this->assertEquals([
            'some_function' => 2,
            'function_exists' => 1,
            'value' => 1,
            'callable' => 1,
        ], $functions->pendingCalls());

        $this->assertSame(2, $functions->pendingCalls('some_function'));
        $this->assertSame(1, $functions->pendingCalls('function_exists'));
        $this->assertSame(1, $functions->pendingCalls('value'));
        $this->assertSame(1, $functions->pendingCalls('callable'));

        $this->assertSame(5, $functions->pendingCallsCount());

        $functions->some_function();
        $this->assertSame([
            'some_function' => 1,
            'function_exists' => 1,
            'value' => 1,
            'callable' => 1,
        ], $functions->pendingCalls());
        $this->assertSame(4, $functions->pendingCallsCount());

        $functions->some_function();
        $functions->value();

        $this->assertSame([
            'some_function' => 0,
            'function_exists' => 1,
            'value' => 0,
            'callable' => 1,
        ], $functions->pendingCalls());
        $this->assertSame(2, $functions->pendingCallsCount());

        $this->assertSame(0, $functions->wasCalledTimes('trim'));
        $functions->function_exists('test');
        $functions->trim(" test ");
        $functions->callable();
        $this->assertSame([
            'some_function' => 0,
            'function_exists' => 0,
            'value' => 0,
            'callable' => 0,
        ], $functions->pendingCalls());
        $this->assertSame(0, $functions->pendingCallsCount());

        $this->assertSame(1, $functions->wasCalledTimes('trim'));
        $this->assertSame([[' test ']], $functions->calls('trim'));

        $this->assertSame(0, $functions->pendingCalls('some_function'));
        $this->assertSame(0, $functions->pendingCalls('function_exists'));
        $this->assertSame(0, $functions->pendingCalls('value'));
        $this->assertSame(0, $functions->pendingCalls('callable'));

        $this->expectException(StackConsumed::class);;
        $functions->some_function();
    }

    public function test_pending_calls_throws_exception_when_function_was_no_set(): void
    {
        $functions = new FakeFunctions();

        $this->expectException(NotMockedFunction::class);
        $this->expectExceptionMessage('Function "some_function" was not mocked a call was triggered');

        $functions->pendingCalls('some_function');
    }

    public function test_first(): void
    {
        $functions = new FakeFunctions([
            'trim' => 'test',
        ]);

       $functions->trim(' test ');

       // first trim call, first argument
        $this->assertSame(' test ', $functions->first('trim')[0]);
    }

    public function test_first_throws_exception_when_function_was_no_called_yet(): void
    {
        $functions = new FakeFunctions([
            'trim' => 'test',
        ]);

        $this->expectException(WasNotCalled::class);
        $this->expectExceptionMessage('Function "trim" was not called yet');;
        $this->assertSame(' test ', $functions->first('trim')[0]);
    }

    public function test_firstArgument(): void
    {
        $functions = new FakeFunctions([
            'trim' => 'test',
        ]);

        $this->expectException(WasNotCalled::class);
        $this->expectExceptionMessage('Function "trim" was not called yet');;
        $this->assertSame(' test ', $functions->firstArgument('trim'));
    }

    public function test_firstArgument_throws_exception_when_function_was_no_called_yet(): void
    {
        $functions = new FakeFunctions([
            'trim' => new FakeStack(['test', 'asd']),
        ]);

        $functions->trim(' test ', 'second argument');

        // it returns first argument by default
        $this->assertSame(' test ', $functions->firstArgument('trim'));

        // choose second argument
        $this->assertSame('second argument', $functions->firstArgument('trim', 1));
    }
}
