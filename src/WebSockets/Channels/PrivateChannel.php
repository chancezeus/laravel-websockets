<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use Ratchet\ConnectionInterface;

class PrivateChannel extends Channel
{
    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \stdClass $payload
     * @throws \BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, \stdClass $payload): void
    {
        $this->verifySignature($connection, $payload);

        parent::subscribe($connection, $payload);
    }
}
