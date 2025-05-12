<?php

declare(strict_types=1);

namespace Filisko;

use Filisko\FakeStack\StackConsumed;

class FakeStack
{
    private $fakes;

    public function __construct(array $fakes)
    {
        $this->fakes = $fakes;
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
        if (count($this->fakes) === 0) {
            throw new StackConsumed(sprintf('Stack of "%s" function was already consumed', $function));
        }

        $result = array_shift($this->fakes);

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
        return count($this->fakes);
    }
}
