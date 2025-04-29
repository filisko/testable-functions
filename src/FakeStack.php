<?php

declare(strict_types=1);

namespace Filisko;

use Filisko\FakeStack\EmptyStackException;

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
     * @throws EmptyStackException
     * @return mixed
     */
    public function value($args)
    {
        if (count($this->fakes) === 0) {
            throw new EmptyStackException('Stack is empty');
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
