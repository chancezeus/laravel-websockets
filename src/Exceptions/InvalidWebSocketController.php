<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

use Ratchet\WebSocket\MessageComponentInterface;

class InvalidWebSocketController extends \Exception
{
    /**
     * @param string $controllerClass
     * @return static
     */
    public static function withController(string $controllerClass): InvalidWebSocketController
    {
        $messageComponentInterfaceClass = MessageComponentInterface::class;

        return new static("Invalid WebSocket Controller provided. Expected instance of `{$messageComponentInterfaceClass}`, but received `{$controllerClass}`.");
    }
}
