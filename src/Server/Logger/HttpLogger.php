<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Illuminate\Support\Facades\App;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class HttpLogger extends Logger implements MessageComponentInterface
{
    /** @var \Ratchet\Http\HttpServerInterface */
    protected $app;

    /**
     * @param \Ratchet\MessageComponentInterface $app
     * @return \BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger
     */
    public static function decorate(MessageComponentInterface $app): self
    {
        /** @var HttpLogger $logger */
        $logger = App::make(self::class);

        return $logger->setApp($app);
    }

    /**
     * @param \Ratchet\MessageComponentInterface $app
     * @return $this
     */
    public function setApp(MessageComponentInterface $app): HttpLogger
    {
        $this->app = $app;

        return $this;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $this->app->onOpen($connection);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param string $message
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $connection, $message)
    {
        $this->app->onMessage($connection, $message);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $connection)
    {
        $this->app->onClose($connection);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Exception $exception
     * @throws \Exception
     */
    public function onError(ConnectionInterface $connection, \Exception $exception)
    {
        $exceptionClass = get_class($exception);

        $message = "Exception `{$exceptionClass}` thrown: `{$exception->getMessage()}`";

        if ($this->verbose) {
            $message .= $exception->getTraceAsString();
        }

        $this->error($message);

        $this->app->onError($connection, $exception);
    }
}
