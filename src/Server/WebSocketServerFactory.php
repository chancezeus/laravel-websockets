<?php

namespace BeyondCode\LaravelWebSockets\Server;

use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;
use Illuminate\Support\Facades\Config;
use Ratchet\Http\Router;
use Ratchet\Server\IoServer;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Socket\SecureServer;
use React\Socket\Server;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class WebSocketServerFactory
{
    /** @var string */
    protected $host = '127.0.0.1';

    /** @var int */
    protected $port = 8080;

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    /** @var \Symfony\Component\Routing\RouteCollection */
    protected $routes;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $consoleOutput;

    public function __construct()
    {
        $this->loop = LoopFactory::create();
    }

    /**
     * @param \Symfony\Component\Routing\RouteCollection $routes
     * @return $this
     */
    public function useRoutes(RouteCollection $routes): WebSocketServerFactory
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost(string $host): WebSocketServerFactory
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param string $port
     * @return $this
     */
    public function setPort(string $port): WebSocketServerFactory
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param \React\EventLoop\LoopInterface $loop
     * @return $this
     */
    public function setLoop(LoopInterface $loop): WebSocketServerFactory
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $consoleOutput
     * @return $this
     */
    public function setConsoleOutput(OutputInterface $consoleOutput): WebSocketServerFactory
    {
        $this->consoleOutput = $consoleOutput;

        return $this;
    }

    /**
     * @return \Ratchet\Server\IoServer
     */
    public function createServer(): IoServer
    {
        $socket = new Server("{$this->host}:{$this->port}", $this->loop);

        if (Config::get('websockets.ssl.local_cert')) {
            $socket = new SecureServer($socket, $this->loop, Config::get('websockets.ssl'));
        }

        $urlMatcher = new UrlMatcher($this->routes, new RequestContext);

        $router = new Router($urlMatcher);

        $app = new OriginCheck($router, Config::get('websockets.allowed_origins', []));

        $httpServer = new HttpServer($app, Config::get('websockets.max_request_size_in_kb') * 1024);

        if (HttpLogger::isEnabled()) {
            $httpServer = HttpLogger::decorate($httpServer);
        }

        return new IoServer($httpServer, $socket, $this->loop);
    }
}
