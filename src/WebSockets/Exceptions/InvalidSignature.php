<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class InvalidSignature extends WebSocketException
{
    public function __construct()
    {
        parent::__construct('Invalid Signature', 4009);
    }
}
