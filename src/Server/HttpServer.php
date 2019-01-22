<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Ratchet\Http\HttpServerInterface;

class HttpServer extends \Ratchet\Http\HttpServer
{
    /**
     * @param \Ratchet\Http\HttpServerInterface $component
     * @param int $maxRequestSize
     */
    public function __construct(HttpServerInterface $component, int $maxRequestSize = 4096)
    {
        parent::__construct($component);

        $this->_reqParser->maxSize = $maxRequestSize;
    }
}
