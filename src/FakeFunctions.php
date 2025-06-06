<?php

declare(strict_types=1);

namespace Filisko;

use BadMethodCallException;
use Filisko\FakeStack\ConsumedFunction;
use Filisko\FakeStack\NotMockedFunction;
use Filisko\FakeStack\StackConsumed;
use Filisko\FakeStack\WasNotCalled;

/**
 * Used for testing environment.
 * It allows you to hardcode the result of PHP native functions (passed to the constructor).
 */
class FakeFunctions extends Functions
{
    /**
     * @var array<string,mixed|FakeStack> Functions and their results.
     */
    protected $functions;

    /**
     * @var bool Whether to fail or not when a function result is missing.
     */
    protected $failOnMissing;

    /**
     * @var array<string,array> calls made to functions.
     */
    protected $calls = [];

    /**
     * @param array<string,mixed|FakeStack> $functions
     */
    public function __construct(array $functions = [], bool $failOnMissing = false)
    {
        $this->functions = $functions;
        $this->failOnMissing = $failOnMissing;
    }

    private function addCall(string $function, array $args): void
    {
        if (!isset($this->calls[$function])) {
            $this->calls[$function] = [];
        }

        $this->calls[$function][] = $args;
    }

    public function calls(?string $function = null): array
    {
        if (null !== $function) {
            return $this->calls[$function] ?? [];
        }

        return $this->calls;
    }

    /**
     * @throws WasNotCalled
     */
    public function first(string $function): array
    {
        if (!isset($this->calls[$function])) {
            throw new WasNotCalled(sprintf('Function "%s" was not called yet', $function));
        }

        return $this->calls[$function][0];
    }

    /**
     * @return mixed
     * @throws WasNotCalled
     */
    public function firstArgument(string $function, int $number = 0)
    {
        $first = $this->first($function);

        return $first[$number];
    }

    public function wasCalled(string $function): bool
    {
        return isset($this->calls[$function]);
    }

    public function wasCalledTimes(string $function): int
    {
        if (!isset($this->calls[$function])) {
            return 0;
        }

        return count($this->calls[$function]);
    }

    public function wasRequired(string $file): bool
    {
        return $this->wasRequiredOrIncluded('require', $file);
    }

    public function wasRequiredOnce(string $file): bool
    {
        return $this->wasRequiredOrIncluded('require_once', $file);
    }

    public function wasIncluded(string $file): bool
    {
        return $this->wasRequiredOrIncluded('include', $file);
    }

    public function wasIncludedOnce(string $file): bool
    {
        return $this->wasRequiredOrIncluded('include_once', $file);
    }

    /**
     * Helper for require, require_once, include, include_once.
     */
    private function wasRequiredOrIncluded(string $function, string $file): bool
    {
        if (!isset($this->calls[$function])) {
            return false;
        }

        $calls = array_filter($this->calls[$function], function ($actualFile) use ($file) {
            return $actualFile[0] === $file;
        });

        return count($calls) > 0;
    }

    /**
     * @return array|int
     */
    public function pendingCalls(?string $requestedFunction = null)
    {
        $pending = [];

        foreach ($this->functions as $function => $value) {
            if ($requestedFunction && $function !== $requestedFunction) {
                continue;
            }

            if (!isset($pending[$function])) {
                $pending[$function] = 0;
            }

            if ($value instanceof FakeStack) {
                $pending[$function] = $value->remaining();
            } elseif ($value instanceof ConsumedFunction) {
                $pending[$function] = 0;
            } else {
                $pending[$function] += 1;
            }
        }

        if ($requestedFunction) {
            if ($pending === []) {
                throw new NotMockedFunction(sprintf('Function "%s" was not mocked a call was triggered', $requestedFunction));
            } else {
                return $pending[$requestedFunction];
            }
        }

        return $pending;
    }

    public function pendingCallsCount(): int
    {
        $pending = 0;

        foreach ($this->pendingCalls() as $count) {
            $pending += $count;
        }

        return $pending;
    }

    /**
     * @return mixed
     * @throws StackConsumed
     * @throws NotMockedFunction
     */
    protected function run($function, $args)
    {
        if ($this->failOnMissing && !isset($this->functions[$function])) {
            throw new NotMockedFunction(sprintf('Function "%s" was not mocked', $function));
        }

        if (!$this->failOnMissing && !isset($this->functions[$function])) {
            if (self::isRequireOrInclude($function)) {
                return parent::$function(...$args);
            } else {
                $this->addCall($function, $args);
                return $function(...$args);
            }
        }

        $fake = $this->functions[$function];

        // throw exception for already consumed values
        if ($fake instanceof ConsumedFunction) {
            throw new StackConsumed(sprintf('Mocked result of "%s" function was already consumed', $function));
        }

        // handle stacks
        if ($fake instanceof FakeStack) {
            $this->addCall($function, $args);
            return $fake->value($function, $args);
        }

        // handle static stacks
        if ($fake instanceof FakeStatic) {
            $this->addCall($function, $args);

            $staticValue = $fake->value();
            if (is_callable($staticValue)) {
                return $staticValue(...$args);
            }

            return $staticValue;
        }

        // handle fallbacks
        if ($fake instanceof FakeFallback) {
            $this->addCall($function, $args);

            return call_user_func_array($function, $args);
        }

        // handle one-time callables
        if (is_callable($fake)) {
            $this->addCall($function, $args);
            $this->functions[$function] = new ConsumedFunction();

            return call_user_func_array($fake, $args);
        }

        // handle one-time values
        $this->addCall($function, $args);
        $this->functions[$function] = new ConsumedFunction();

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

    private static function isRequireOrInclude(string $function): bool
    {
        return in_array($function, [
            'require_once',
            'require',
            'include_once',
            'include',
        ], true);
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
        $this->addCall('exit', func_get_args());
    }

    /**
     * @return string|int|bool False when didn't exit.
     */
    public function exitCode()
    {
        if (!isset($this->calls['exit'])) {
            throw new BadMethodCallException('Exit was never called. Use: exited() first');
        }

        // first call, first argument
        return $this->calls['exit'][0][0];
    }

    public function exited(): bool
    {
        if (!isset($this->calls['exit'])) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function die($status = "")
    {
        $this->addCall('die', func_get_args());
    }

    public function died(): bool
    {
        if (!isset($this->calls['die'])) {
            return false;
        }

        return true;
    }

    /**
     * @return string|int|bool False when didn't exit.
     */
    public function dieCode()
    {
        if (!isset($this->calls['die'])) {
            throw new BadMethodCallException('Die was never called. Use: died() first');
        }

        // die function -> first call -> first argument
        return $this->calls['die'][0][0];
    }

    /**
     * @inheritDoc
     */
    public function echo($string)
    {
        $this->addCall('echo', func_get_args());
    }

    /**
     * @return string[]
     */
    public function echos(): array
    {
        if (!isset($this->calls['echo'])) {
            return [];
        }

        $echos = $this->calls['echo'];

        return array_merge(...$echos);
    }

    public function wasEchoed(string $text): bool
    {
        return in_array($text, $this->echos());
    }

    /**
     * @inheritDoc
     */
    public function print($string)
    {
        $this->addCall('print', func_get_args());
    }

    /**
     * @return string[]
     */
    public function prints(): array
    {
        if (!isset($this->calls['print'])) {
            return [];
        }

        $prints = $this->calls['print'];

        return array_merge(...$prints);
    }

    public function wasPrinted(string $text): bool
    {
        return in_array($text, $this->prints());
    }
}
