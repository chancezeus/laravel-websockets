<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Ratchet\ConnectionInterface;

class PusherClientMessage implements PusherMessage
{
    /** \stdClass */
    protected $payload;

    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    /**
     * PusherClientMessage constructor.
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
        if (! starts_with($this->payload->event, 'client-')) {
            return;
        }

        if (! $this->connection->app->isClientMessagesEnabled()) {
            return;
        }

        DashboardLogger::clientMessage($this->connection, $this->payload);

        $channel = $this->channelManager->find($this->connection->app->getId(), $this->payload->channel);

        optional($channel)->broadcastToOthers($this->connection, $this->payload);
    }
}
