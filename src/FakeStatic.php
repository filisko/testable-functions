<?php

declare(strict_types=1);

namespace Filisko;

class FakeStatic
{
    private $mock;

    /**
     * @param mixed $mock
     */
    public function __construct($mock)
    {
        $this->mock = $mock;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->mock;
    }
}
