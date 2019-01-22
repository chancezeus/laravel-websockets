<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class InvalidConnection extends WebSocketException
{
    public function __construct()
    {
        parent::__construct('Invalid Connection', 4009);
    }
}
