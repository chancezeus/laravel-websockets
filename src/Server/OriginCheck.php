<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\CloseResponseTrait;
use Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;

class OriginCheck implements HttpServerInterface
{
    use CloseResponseTrait;

    /** @var \Ratchet\MessageComponentInterface */
    protected $_component;

    /** @var string[] */
    protected $allowedOrigins = [];

    /**
     * @param \Ratchet\MessageComponentInterface $component
     * @param string[] $allowedOrigins
     */
    public function __construct(MessageComponentInterface $component, array $allowedOrigins = [])
    {
        $this->_component = $component;

        $this->allowedOrigins = $allowedOrigins;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Psr\Http\Message\RequestInterface|null $request
     * @return mixed
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        if ($request->hasHeader('Origin')) {
            $this->verifyOrigin($connection, $request);
        }

        return $this->_component->onOpen($connection, $request);
    }

    /**
     * @param \Ratchet\ConnectionInterface $from
     * @param string $msg
     * @return mixed
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        return $this->_component->onMessage($from, $msg);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @return mixed
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $connection)
    {
        return $this->_component->onClose($connection);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Exception $e
     * @return mixed
     * @throws \Exception
     */
    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        return $this->_component->onError($connection, $e);
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Psr\Http\Message\RequestInterface $request
     */
    protected function verifyOrigin(ConnectionInterface $connection, RequestInterface $request): void
    {
        $header = (string) $request->getHeader('Origin')[0];
        $origin = parse_url($header, PHP_URL_HOST) ?: $header;

        if (! empty($this->allowedOrigins) && ! in_array($origin, $this->allowedOrigins)) {
            $this->close($connection, 403);
        }
    }
}
