<?php

namespace BeyondCode\LaravelWebSockets\Dashboard;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Support\Facades\App;
use Ratchet\ConnectionInterface;

class DashboardLogger
{
    const LOG_CHANNEL_PREFIX = 'private-websockets-dashboard-';
    const TYPE_DISCONNECTION = 'disconnection';
    const TYPE_CONNECTION = 'connection';
    const TYPE_VACATED = 'vacated';
    const TYPE_OCCUPIED = 'occupied';
    const TYPE_SUBSCRIBED = 'subscribed';
    const TYPE_CLIENT_MESSAGE = 'client-message';
    const TYPE_API_MESSAGE = 'api-message';

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public static function connection(ConnectionInterface $connection): void
    {
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $connection->httpRequest;

        static::log($connection->app->getId(), static::TYPE_CONNECTION, [
            'details' => "Origin: {$request->getUri()->getScheme()}://{$request->getUri()->getHost()}",
            'socketId' => $connection->socketId,
        ]);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param string $channelName
     */
    public static function occupied(ConnectionInterface $connection, string $channelName): void
    {
        static::log($connection->app->getId(), static::TYPE_OCCUPIED, [
            'details' => "Channel: {$channelName}",
        ]);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param string $channelName
     */
    public static function subscribed(ConnectionInterface $connection, string $channelName): void
    {
        static::log($connection->app->getId(), static::TYPE_SUBSCRIBED, [
            'socketId' => $connection->socketId,
            'details' => "Channel: {$channelName}",
        ]);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \stdClass $payload
     */
    public static function clientMessage(ConnectionInterface $connection, \stdClass $payload)
    {
        static::log($connection->app->getId(), static::TYPE_CLIENT_MESSAGE, [
            'details' => "Channel: {$payload->channel}, Event: {$payload->event}",
            'socketId' => $connection->socketId,
            'data' => json_encode($payload),
        ]);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public static function disconnection(ConnectionInterface $connection): void
    {
        static::log($connection->app->getId(), static::TYPE_DISCONNECTION, [
            'socketId' => $connection->socketId,
        ]);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param string $channelName
     */
    public static function vacated(ConnectionInterface $connection, string $channelName): void
    {
        static::log($connection->app->getId(), static::TYPE_VACATED, [
            'details' => "Channel: {$channelName}",
        ]);
    }

    /**
     * @param string $appId
     * @param string $channel
     * @param string $event
     * @param string $payload
     */
    public static function apiMessage(string $appId, string $channel, string $event, string $payload): void
    {
        static::log($appId, static::TYPE_API_MESSAGE, [
            'details' => "Channel: {$channel}, Event: {$event}",
            'data' => $payload,
        ]);
    }

    /**
     * @param string $appId
     * @param string $type
     * @param array $attributes
     */
    public static function log(string $appId, string $type, array $attributes = []): void
    {
        $channelName = static::LOG_CHANNEL_PREFIX . $type;

        $channel = App::make(ChannelManager::class)->find($appId, $channelName);

        optional($channel)->broadcast([
            'event' => 'log-message',
            'channel' => $channelName,
            'data' => [
                    'type' => $type,
                    'time' => strftime('%H:%M:%S'),
                ] + $attributes,
        ]);
    }
}
