<?php

namespace BeyondCode\LaravelWebSockets\WebSockets;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\WebSocketException;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessageFactory;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebSocketHandler implements MessageComponentInterface
{
    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    /**
     * @param \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager $channelManager
     */
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @throws \BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $this
            ->verifyAppKey($connection)
            ->generateSocketId($connection)
            ->establishConnection($connection);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Ratchet\RFC6455\Messaging\MessageInterface $message
     */
    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $message = PusherMessageFactory::createForMessage($message, $connection, $this->channelManager);

        $message->respond();

        StatisticsLogger::webSocketMessage($connection);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function onClose(ConnectionInterface $connection): void
    {
        $this->channelManager->removeFromAllChannels($connection);

        DashboardLogger::disconnection($connection);

        StatisticsLogger::disconnection($connection);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Exception $exception
     */
    public function onError(ConnectionInterface $connection, \Exception $exception): void
    {
        if ($exception instanceof WebSocketException) {
            $connection->send(json_encode(
                $exception->getPayload()
            ));
        }
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @return $this
     * @throws \BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey
     */
    protected function verifyAppKey(ConnectionInterface $connection): WebSocketHandler
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        if (! $app = App::findByKey($appKey)) {
            throw new UnknownAppKey($appKey);
        }

        $connection->app = $app;

        return $this;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @return $this
     * @throws \Exception
     */
    protected function generateSocketId(ConnectionInterface $connection): WebSocketHandler
    {
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));

        $connection->socketId = $socketId;

        return $this;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @return $this
     */
    protected function establishConnection(ConnectionInterface $connection): WebSocketHandler
    {
        $connection->send(json_encode([
            'event' => 'pusher:connection_established',
            'data' => json_encode([
                'socket_id' => $connection->socketId,
                'activity_timeout' => 30,
            ]),
        ]));

        DashboardLogger::connection($connection);

        StatisticsLogger::connection($connection);

        return $this;
    }
}
