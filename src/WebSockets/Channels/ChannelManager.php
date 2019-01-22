<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use Ratchet\ConnectionInterface;

interface ChannelManager
{
    /**
     * @param string $appId
     * @param string $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel
     */
    public function findOrCreate(string $appId, string $channelName): Channel;

    /**
     * @param string $appId
     * @param string $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel|null
     */
    public function find(string $appId, string $channelName): ?Channel;

    /**
     * @param string $appId
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel[]
     */
    public function getChannels(string $appId): array;

    /**
     * @param string $appId
     * @return int
     */
    public function getConnectionCount(string $appId): int;

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function removeFromAllChannels(ConnectionInterface $connection): void;
}
