<?php
declare(strict_types=1);

namespace Filisko\Tests\Examples\Email;

use Filisko\FakeFunctions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailServiceTest extends TestCase
{
    public function test_sendEmail_exits_on_failure(): void
    {
        // create FakeFunctions
        $functions = new FakeFunctions([
            // make mail fail
            'mail' => false,
        ]);

        // pass it to the constructor
        $service = new EmailService($functions);

        // sendEmail exits in production if it fails, meaning: PHP execution has ended
        $service->sendEmail('test@example.com', 'Test', 'Hello');

        // ... but here, we can assert that exit was called without it actually exiting
        $this->assertTrue($functions->exited());
        $this->assertEquals(1, $functions->exitCode());

        // we can also assert that error_log was called with the correct message
        $this->assertEquals(
            'Failed to send email to: test@example.com',
            // first call, first argument
            $functions->firstArgument('error_log')
        );
    }

    public function test_sendEmail_returns_true_on_success()
    {
        // create FakeFunctions
        $functions = new FakeFunctions([
            // make mail succeed
            'mail' => true
        ]);

        $service = new EmailService($functions);

        // call the method
        $result = $service->sendEmail('test@example.com', 'Test', 'Hello');

        // assert the method returned true
        $this->assertTrue($result);

        // assert no exit was called
        $this->assertFalse($functions->exited());
    }

    public function test_sendEmail_throws_exception_on_invalid_email()
    {
        // create FakeFunctions
        $functions = new FakeFunctions([
            // could be mocked, but better to test the real thing
            // 'filter_var' => true,
        ]);

        $service = new EmailService($functions);

        $this->expectException(InvalidArgumentException::class);

        $service->sendEmail('invalid', 'Test', 'Hello');
    }
}
