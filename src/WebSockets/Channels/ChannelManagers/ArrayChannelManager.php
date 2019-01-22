<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;

class ArrayChannelManager implements ChannelManager
{
    /** @var string */
    protected $appId;

    /** @var array */
    protected $channels = [];

    /**
     * @param string $appId
     * @param string $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel
     */
    public function findOrCreate(string $appId, string $channelName): Channel
    {
        if (! isset($this->channels[$appId][$channelName])) {
            $channelClass = $this->determineChannelClass($channelName);

            $this->channels[$appId][$channelName] = new $channelClass($channelName);
        }

        return $this->channels[$appId][$channelName];
    }

    /**
     * @param string $appId
     * @param string $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel|null
     */
    public function find(string $appId, string $channelName): ?Channel
    {
        return $this->channels[$appId][$channelName] ?? null;
    }

    /**
     * @param string $channelName
     * @return string
     */
    protected function determineChannelClass(string $channelName): string
    {
        if (starts_with($channelName, 'private-')) {
            return PrivateChannel::class;
        }

        if (starts_with($channelName, 'presence-')) {
            return PresenceChannel::class;
        }

        return Channel::class;
    }

    /**
     * @param string $appId
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel[]
     */
    public function getChannels(string $appId): array
    {
        return $this->channels[$appId] ?? [];
    }

    /**
     * @param string $appId
     * @return int
     */
    public function getConnectionCount(string $appId): int
    {
        return Collection::make($this->getChannels($appId))
            ->sum(function (Channel $channel) {
                return count($channel->getSubscribedConnections());
            });
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @return void
     */
    public function removeFromAllChannels(ConnectionInterface $connection): void
    {
        if (! isset($connection->app)) {
            return;
        }

        // Remove the connection from all channels.
        Collection::make(array_get($this->channels, $connection->app->getId(), []))
            ->each(function (Channel $channel) use ($connection) {
                $channel->unsubscribe($connection);
            });

        // Unset all channels that have no connections so we don't leak memory.
        Collection::make(array_get($this->channels, $connection->app->getId(), []))
            ->reject(function (Channel $channel) {
                return $channel->hasConnections();
            })
            ->keys()
            ->each(function (string $channelName) use ($connection) {
                unset($this->channels[$connection->app->getId()][$channelName]);
            });

        if (count(array_get($this->channels, $connection->app->getId(), [])) === 0) {
            unset($this->channels[$connection->app->getId()]);
        }
    }
}
