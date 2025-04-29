<?php

declare(strict_types=1);

namespace Filisko\Tests;

use BadFunctionCallException;
use Filisko\Functions;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function test_it_delegates_to_php(): void
    {
        $functions = new Functions();

        $result = $functions->trim('       test       ');

        $this->assertSame('test', $result);
    }

    public function test_it_throws_type_error_when_function_doesnt_exist(): void
    {
        $functions = new Functions();

        $this->expectException(BadFunctionCallException::class);
        $this->expectExceptionMessage('Function "nope" does not exist');

        $functions->nope();
    }

    public function test_requireOnce(): void
    {
        $functions = new Functions();

        $result = $functions->requireOnce(__DIR__.'/FunctionsTests/require_me_once.php');
        $this->assertSame(['required_once' => true], $result);
    }

    public function test_require(): void
    {
        $functions = new Functions();

        $result = $functions->requireOnce(__DIR__.'/FunctionsTests/require_me.php');
        $this->assertSame(['required' => true], $result);
    }

    public function test_includeOnce(): void
    {
        $functions = new Functions();

        $result = $functions->includeOnce(__DIR__.'/FunctionsTests/include_me_once.php');
        $this->assertSame(['included_once' => true], $result);
    }

    public function test_include(): void
    {
        $functions = new Functions();

        $result = $functions->include(__DIR__.'/FunctionsTests/include_me.php');
        $this->assertSame(['included' => true], $result);
    }

    public function test_exit(): void
    {
        $functions = new class() extends Functions {
            public function exit($status)
            {
                return $status;
            }
        };

        $this->assertSame(2, $functions->exit(2));
    }

    public function test_die(): void
    {
        $functions = new class() extends Functions {
            public function die($status)
            {
                return $status;
            }
        };

        $this->assertSame(125, $functions->die(125));
    }

    public function test_echo(): void
    {
        $functions = new Functions();

        ob_start();
        $functions->echo("from echo");
        $output = ob_get_clean();

        $this->assertSame("from echo", $output);
    }

    public function test_print(): void
    {
        $functions = new Functions();

        ob_start();
        $functions->print("from print");
        $output = ob_get_clean();

        $this->assertSame("from print", $output);
    }
}
