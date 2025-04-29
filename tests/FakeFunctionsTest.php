<?php

declare(strict_types=1);

namespace Filisko\Tests;

use Filisko\FakeFunctions;
use Filisko\FakeStack;
use Filisko\FakeStack\EmptyFakeStackException;
use Filisko\FakeStack\NotMockedFunction;
use PHPUnit\Framework\TestCase;

class FakeFunctionsTest extends TestCase
{
    public function dataProvider_for_callables(): array
    {
        return [
            [
                [
                    'some_function' => function (string $param) {
                        return $param;
                    }
                ],
                'test'
            ],
            [
                [
                    'some_function' => function () {
                        return true;
                    }
                ],
                true
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_for_callables
     * @param array<string,callable> $input
     * @param array<string,bool> $expected
     */
    public function test_it_supports_callables(array $input, $expected): void
    {
        $functions = new FakeFunctions([
            'some_function' => function (string $param) {
                return $param;
            }
        ]);

        $result = $functions->some_function('test');

        $this->assertEquals($expected, $result);
    }

    public function test_it_supports_values_directly(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => true
        ]);

        $result = $functions->function_exists('test');

        $this->assertSame(true, $result);
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
    }

    public function test_it_throws_an_exception_when_value_is_needed_but_stack_is_empty(): void
    {
        $functions = new FakeFunctions([
            'function_exists' => new FakeStack(['one'])
        ]);

        // first execution
        $this->assertSame('one', $functions->function_exists('test'));

        // second execution requires a value but the stack is empty
        $this->expectException(EmptyFakeStackException::class);
        $this->assertSame(true, $functions->function_exists('test'));
    }

    public function test_it_throws_an_exception_when_result_for_function_is_not_set(): void
    {
        $functions = new FakeFunctions();

        // trim is note defined
        $this->expectException(NotMockedFunction::class);
        $result = $functions->trim('       test       ');

        $this->assertSame('test', $result);
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

    public function test_includeOnce(): void
    {
        $functions = new FakeFunctions([
            'include_once' => new FakeStack(['test'])
        ]);

        $this->assertSame('test', $functions->include_once('file.php'));
    }

    public function test_include(): void
    {
        $functions = new FakeFunctions([
            'include' => new FakeStack([''])
        ]);

        $this->assertSame('', $functions->include('file.php'));
    }

    public function test_exit(): void
    {
        $functions = new FakeFunctions();

        $this->assertFalse($functions->didExit());

        $functions->exit(1);

        $this->assertTrue($functions->didExit());
        $this->assertSame(1, $functions->exitCode());
    }

    public function test_die(): void
    {
        $functions = new FakeFunctions();

        $this->assertFalse($functions->died());

        $functions->die("Bye bye");

        $this->assertTrue($functions->died());
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
    }
}
