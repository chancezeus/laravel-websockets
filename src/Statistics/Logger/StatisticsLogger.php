<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use Ratchet\ConnectionInterface;

interface StatisticsLogger
{
    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function webSocketMessage(ConnectionInterface $connection): void;

    /**
     * @param string $appId
     */
    public function apiMessage(string $appId): void;

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function connection(ConnectionInterface $connection): void;

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function disconnection(ConnectionInterface $connection): void;

    public function save(): void;
}
