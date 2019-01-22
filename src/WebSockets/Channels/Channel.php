<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;

class Channel
{
    /** @var string */
    protected $channelName;

    /** @var \Ratchet\ConnectionInterface[] */
    protected $subscribedConnections = [];

    /**
     * @param string $channelName
     */
    public function __construct(string $channelName)
    {
        $this->channelName = $channelName;
    }

    /**
     * @return bool
     */
    public function hasConnections(): bool
    {
        return count($this->subscribedConnections) > 0;
    }

    /**
     * @return \Ratchet\ConnectionInterface[]
     */
    public function getSubscribedConnections(): array
    {
        return $this->subscribedConnections;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \stdClass $payload
     * @throws \BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature
     */
    protected function verifySignature(ConnectionInterface $connection, \stdClass $payload): void
    {
        $signature = "{$connection->socketId}:{$this->channelName}";

        if (isset($payload->channel_data)) {
            $signature .= ":{$payload->channel_data}";
        }

        if (str_after($payload->auth, ':') !== hash_hmac('sha256', $signature, $connection->app->getSecret())) {
            throw new InvalidSignature();
        }
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \stdClass $payload
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     */
    public function subscribe(ConnectionInterface $connection, \stdClass $payload): void
    {
        $this->saveConnection($connection);

        $connection->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelName,
        ]));
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function unsubscribe(ConnectionInterface $connection): void
    {
        unset($this->subscribedConnections[$connection->socketId]);

        if (! $this->hasConnections()) {
            DashboardLogger::vacated($connection, $this->channelName);
        }
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    protected function saveConnection(ConnectionInterface $connection): void
    {
        $hadConnectionsPreviously = $this->hasConnections();

        $this->subscribedConnections[$connection->socketId] = $connection;

        if (! $hadConnectionsPreviously) {
            DashboardLogger::occupied($connection, $this->channelName);
        }

        DashboardLogger::subscribed($connection, $this->channelName);
    }

    /**
     * @param mixed $payload
     */
    public function broadcast($payload): void
    {
        foreach ($this->subscribedConnections as $connection) {
            $connection->send(json_encode($payload));
        }
    }

    /**
     * @param ConnectionInterface $connection
     * @param mixed $payload
     */
    public function broadcastToOthers(ConnectionInterface $connection, $payload): void
    {
        $this->broadcastToEveryoneExcept($payload, $connection->socketId);
    }

    /**
     * @param $payload
     * @param string|null $socketId
     */
    public function broadcastToEveryoneExcept($payload, ?string $socketId = null): void
    {
        if (is_null($socketId)) {
            $this->broadcast($payload);

            return;
        }

        foreach ($this->subscribedConnections as $connection) {
            if ($connection->socketId !== $socketId) {
                $connection->send(json_encode($payload));
            }
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'occupied' => count($this->subscribedConnections) > 0,
            'subscription_count' => count($this->subscribedConnections),
        ];
    }
}
