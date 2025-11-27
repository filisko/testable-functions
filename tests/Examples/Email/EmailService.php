<?php

declare(strict_types=1);

namespace Filisko\Tests\Examples\Email;

use Filisko\Functions;
use InvalidArgumentException;

class EmailService
{
    private $functions;

    public function __construct(Functions $functions)
    {
        $this->functions = $functions;
    }

    public function sendEmail($to, $subject, $message)
    {
        if (!$this->functions->filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }

        $result = $this->functions->mail($to, $subject, $message);

        if (!$result) {
            $this->functions->error_log("Failed to send email to: {$to}");
            $this->functions->exit(1);
        }

        return true;
    }
}
