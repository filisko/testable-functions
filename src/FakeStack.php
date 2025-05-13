<?php

declare(strict_types=1);

namespace Filisko;

use Filisko\FakeStack\StackConsumed;

class FakeStack
{
    private $mocks;

    public function __construct(array $fakes)
    {
        $this->mocks = $fakes;
    }

    /**
     * Gives the next value and removes it from the stack.
     *
     * @param string $function Only used to improve the error message.
     * @return mixed
     * @throws StackConsumed
     */
    public function value(string $function, $args)
    {
        if (count($this->mocks) === 0) {
            throw new StackConsumed(sprintf('Stack of "%s" function was already consumed', $function));
        }

        $result = array_shift($this->mocks);

        if (is_callable($result)) {
            return call_user_func_array($result, $args);
        }

        return $result;
    }

    /**
     * Remaining values in the stack.
     */
    public function remaining(): int
    {
        return count($this->mocks);
    }
}
