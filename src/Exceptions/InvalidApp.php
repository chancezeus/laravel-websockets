<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

use Exception;

class InvalidApp extends Exception
{
    /**
     * @param string $appId
     * @return static
     */
    public static function notFound(string $appId): InvalidApp
    {
        return new static("Could not find app for app id `{$appId}`.");
    }

    /**
     * @param string $name
     * @param string $appId
     * @return static
     */
    public static function valueIsRequired($name, $appId): InvalidApp
    {
        return new static("{$name} is required but was empty for app id `{$appId}`.");
    }
}
