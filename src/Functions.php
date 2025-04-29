<?php

declare(strict_types=1);

namespace Filisko;

use BadFunctionCallException;

/**
 * Used for production environment.
 * It calls PHP native functions.
 */
class Functions
{
    /**
     * "Proxy" for PHP native functions.
     *
     * @param string            $function The function call.
     * @param array<int, mixed> $arguments The arguments of the function call.
     *
     * @return mixed The result of the function call.
     */
    public function __call($function, $arguments)
    {
        if (!function_exists($function)) {
            throw new BadFunctionCallException(sprintf('Function "%s" does not exist', $function));
        }

        return call_user_func_array($function, $arguments);
    }

    /**
      * @return mixed Returns 1 if the file was already included
      *               Otherwise returns the result of the included file (which could be any type)
      *               Returns FALSE on failure.
     */
    public function require_once(string $path)
    {
        return require_once $path;
    }

    /**
     * @return mixed Returns the result of the included file (which could be any type)
     *               Returns FALSE on failure.
     */
    public function require(string $path)
    {
        return require $path;
    }

    /**
     * Includes and evaluates the specified file if it hasn't been included before.
     *
     * @return mixed Returns 1 if the file was already included
     *               Otherwise returns the result of the included file (which could be any type)
     *               Returns FALSE on failure
     */
    public function include_once(string $path)
    {
        return include_once $path;
    }

    /**
     * @return mixed Returns the result of the included file (which could be any type)
     *               Returns FALSE on failure
     */
    public function include(string $path)
    {
        return include $path;
    }

    /**
     * @codeCoverageIgnore
     * @param int|string $status
     * @return void
     */
    public function exit($status)
    {
        exit($status);
    }

    /**
     * @codeCoverageIgnore
     * @param int|string $status
     * @return void
     */
    public function die($status)
    {
        die($status);
    }

    /**
     * @return void
     */
    public function echo($string)
    {
        echo $string;
    }

    /**
     * @return void
     */
    public function print($string)
    {
        print $string;
    }
}
