<?php

declare(strict_types=1);

namespace Filisko;

use Filisko\FakeStack\EmptyStack;

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
     * @return mixed
     *@throws EmptyStack
     */
    public function value($args)
    {
        if (count($this->fakes) === 0) {
            throw new EmptyStack('Stack is empty');
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
