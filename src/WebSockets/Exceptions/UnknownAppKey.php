<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class UnknownAppKey extends WebSocketException
{
    /**
     * @param string $appKey
     */
    public function __construct(string $appKey)
    {
        parent::__construct("Could not find app key `{$appKey}`.", 4001);
    }
}
