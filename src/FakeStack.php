<?php

declare(strict_types=1);

namespace Filisko;

use Filisko\FakeStack\EmptyFakeStackException;

class FakeStack
{
    private $fakes;

    public function __construct(array $fakes)
    {
        $this->fakes = $fakes;
    }

    /**
     * @return mixed
     */
    public function value($args)
    {
        if (count($this->fakes) === 0) {
            throw new EmptyFakeStackException('Stack is empty');
        }

        $result = array_shift($this->fakes);

        if (is_callable($result)) {
            return call_user_func_array($result, $args);
        }

        return $result;
    }
}
