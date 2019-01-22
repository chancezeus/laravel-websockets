<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Ratchet\ConnectionInterface;

class PusherChannelProtocolMessage implements PusherMessage
{
    /** @var \stdClass */
    protected $payload;

    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    /**
     * PusherChannelProtocolMessage constructor.
     * @param \stdClass $payload
     * @param \Ratchet\ConnectionInterface $connection
     * @param \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager $channelManager
     */
    public function __construct(\stdClass $payload, ConnectionInterface $connection, ChannelManager $channelManager)
    {
        $this->payload = $payload;

        $this->connection = $connection;

        $this->channelManager = $channelManager;
    }

    public function respond(): void
    {
        $eventName = camel_case(str_after($this->payload->event, ':'));

        if (method_exists($this, $eventName)) {
            call_user_func([$this, $eventName], $this->connection, $this->payload->data ?? new \stdClass());
        }
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @link https://pusher.com/docs/pusher_protocol#ping-pong
     */
    protected function ping(ConnectionInterface $connection): void
    {
        $connection->send(json_encode([
            'event' => 'pusher:pong',
        ]));
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \stdClass $payload
     * @link https://pusher.com/docs/pusher_protocol#pusher-subscribe
     */
    protected function subscribe(ConnectionInterface $connection, \stdClass $payload): void
    {
        $channel = $this->channelManager->findOrCreate($connection->app->getId(), $payload->channel);

        $channel->subscribe($connection, $payload);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \stdClass $payload
     */
    public function unsubscribe(ConnectionInterface $connection, \stdClass $payload): void
    {
        $channel = $this->channelManager->findOrCreate($connection->app->getId(), $payload->channel);

        $channel->unsubscribe($connection);
    }
}
