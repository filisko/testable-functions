<?php

declare(strict_types=1);

namespace Filisko;

use Filisko\FakeStack\EmptyFakeStackException;
use Filisko\FakeStack\NotMockedFunction;

/**
 * Used for testing environment.
 * It allows you to hardcode the result of PHP native functions (passed to the constructor).
 */
class FakeFunctions extends Functions
{
    /** @var array<string,mixed|FakeStack> */
    protected $functions;

    /**
     * @var int|string|bool It is false when it was not triggered.
     */
    protected $exitCode = false;

    /**
     * @var int|string|bool It is false when it was not triggered.
     */
    protected $dieCode = false;

    /** @var string[] */
    protected $echos = [];

    /** @var string[] */
    protected $prints = [];

    /**
     * @param array<string,mixed> $functions
     */
    public function __construct(array $functions = [])
    {
        $this->functions = $functions;
    }

    /**
     * @return mixed
     * @throws EmptyFakeStackException
     * @throws NotMockedFunction
     */
    protected function run($function, $args)
    {
        if (!isset($this->functions[$function])) {
            throw new NotMockedFunction(sprintf('Function "%s" was not mocked', $function));
        }

        $fake = $this->functions[$function];

        if (is_callable($fake)) {
            return call_user_func_array($fake, $args);
        }

        if ($fake instanceof FakeStack) {
            return $fake->value($args);
        }

        return $fake;
    }

    /**
     * Returns faked functions passed in the constructor, otherwise it delegates the operation to parent.
     *
     * @inheritDoc
     */
    public function __call($func, $args)
    {
        return $this->run($func, $args);
    }

    /**
     * @inheritDoc
     */
    public function require_once(string $path)
    {
        return $this->run('require_once', func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function require(string $path)
    {
        return $this->run('require', func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function include_once(string $path)
    {
        return $this->run('include_once', func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function include(string $path)
    {
        return $this->run('include', func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function exit($status = 0)
    {
        $this->exitCode = $status;
    }

    /**
     * @return string|int|bool False when didn't exit.
     */
    public function exitCode()
    {
        return $this->exitCode;
    }

    /**
     * @return bool
     */
    public function didExit()
    {
        return $this->exitCode !== false;
    }

    /**
     * @inheritDoc
     */
    public function die($status = "")
    {
        $this->dieCode = $status;
    }

    /**
     * @return bool
     */
    public function died()
    {
        return $this->dieCode !== false;
    }

    /**
     * @return string|int|bool False when didn't exit.
     */
    public function dieCode()
    {
        return $this->dieCode;
    }

    /**
     * @inheritDoc
     */
    public function echo($string)
    {
        $this->echos[] = $string;
    }

    /**
     * @return string[]
     */
    public function echos(): array
    {
        return $this->echos;
    }

    public function wasEchoed(string $expected): bool
    {
        return in_array($expected, $this->echos);
    }

    /**
     * @inheritDoc
     */
    public function print($string)
    {
        $this->prints[] = $string;
    }

    public function wasPrinted(string $expected): bool
    {
        return in_array($expected, $this->prints);
    }

    /**
     * @return string[]
     */
    public function prints(): array
    {
        return $this->prints;
    }
}
