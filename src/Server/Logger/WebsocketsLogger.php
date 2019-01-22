<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use BeyondCode\LaravelWebSockets\QueryParameters;
use Illuminate\Support\Facades\App;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebsocketsLogger extends Logger implements MessageComponentInterface
{
    /** @var \Ratchet\Http\HttpServerInterface */
    protected $app;

    /**
     * @param \Ratchet\WebSocket\MessageComponentInterface $app
     * @return static
     */
    public static function decorate(MessageComponentInterface $app): self
    {
        /** @var WebsocketsLogger $logger */
        $logger = App::make(self::class);

        return $logger->setApp($app);
    }

    /**
     * @param \Ratchet\WebSocket\MessageComponentInterface $app
     * @return $this
     */
    public function setApp(MessageComponentInterface $app): WebsocketsLogger
    {
        $this->app = $app;

        return $this;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        $this->warn("New connection opened for app key {$appKey}.");

        $this->app->onOpen(ConnectionLogger::decorate($connection));
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Ratchet\RFC6455\Messaging\MessageInterface $message
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $this->info("{$connection->app->getId()}: connection id {$connection->socketId} received message: {$message->getPayload()}.");

        $this->app->onMessage(ConnectionLogger::decorate($connection), $message);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $connection)
    {
        $socketId = $connection->socketId ?? null;

        $this->warn("Connection id {$socketId} closed.");

        $this->app->onClose(ConnectionLogger::decorate($connection));
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Exception $exception
     * @throws \Exception
     */
    public function onError(ConnectionInterface $connection, \Exception $exception)
    {
        $exceptionClass = get_class($exception);

        $appId = $connection->app->getId() ?? 'Unknown app id';

        $message = "{$appId}: exception `{$exceptionClass}` thrown: `{$exception->getMessage()}`.";

        if ($this->verbose) {
            $message .= $exception->getTraceAsString();
        }

        $this->error($message);

        $this->app->onError(ConnectionLogger::decorate($connection), $exception);
    }
}
