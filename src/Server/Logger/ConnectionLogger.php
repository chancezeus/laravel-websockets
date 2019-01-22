<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Illuminate\Support\Facades\App;
use Ratchet\ConnectionInterface;

class ConnectionLogger extends Logger implements ConnectionInterface
{
    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    /**
     * @param \Ratchet\ConnectionInterface $app
     * @return \BeyondCode\LaravelWebSockets\Server\Logger\ConnectionLogger
     */
    public static function decorate(ConnectionInterface $app): self
    {
        /** @var \BeyondCode\LaravelWebSockets\Server\Logger\ConnectionLogger $logger */
        $logger = App::make(self::class);

        return $logger->setConnection($app);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection): ConnectionLogger
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return \Ratchet\ConnectionInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $data
     * @return \Ratchet\ConnectionInterface
     */
    public function send($data)
    {
        $socketId = $this->connection->socketId ?? null;

        $this->info("Connection id {$socketId} sending message {$data}");

        $this->connection->send($data);

        return $this->connection;
    }

    public function close()
    {
        $this->warn("Connection id {$this->connection->socketId} closing.");

        $this->connection->close();
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        return $this->connection->$name = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->connection->$name;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->connection->$name);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->connection->$name);
    }
}
