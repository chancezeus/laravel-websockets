<?php

namespace BeyondCode\LaravelWebSockets\Server;

use BeyondCode\LaravelWebSockets\Exceptions\InvalidWebSocketController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelsController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchUsersController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\TriggerEventController;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketsLogger;
use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler;
use Illuminate\Support\Facades\App;
use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    /** @var \Symfony\Component\Routing\RouteCollection */
    protected $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection;
    }

    /**
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function echo()
    {
        $this->get('/app/{appKey}', WebSocketHandler::class);

        $this->post('/apps/{appId}/events', TriggerEventController::class);
        $this->get('/apps/{appId}/channels', FetchChannelsController::class);
        $this->get('/apps/{appId}/channels/{channelName}', FetchChannelController::class);
        $this->get('/apps/{appId}/channels/{channelName}/users', FetchUsersController::class);
    }

    /**
     * @param string $uri
     * @param string $action
     */
    public function get(string $uri, string $action)
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * @param string $uri
     * @param string $action
     */
    public function post(string $uri, string $action)
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * @param string $uri
     * @param string $action
     */
    public function put(string $uri, string $action)
    {
        $this->addRoute('PUT', $uri, $action);
    }

    /**
     * @param string $uri
     * @param string $action
     */
    public function patch(string $uri, string $action)
    {
        $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * @param string $uri
     * @param string $action
     */
    public function delete(string $uri, string $action)
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * @param string $uri
     * @param string $action
     * @throws \BeyondCode\LaravelWebSockets\Exceptions\InvalidWebSocketController
     */
    public function webSocket(string $uri, string $action)
    {
        if (! is_subclass_of($action, MessageComponentInterface::class)) {
            throw InvalidWebSocketController::withController($action);
        }

        $this->get($uri, $action);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $action
     */
    public function addRoute(string $method, string $uri, string $action): void
    {
        $this->routes->add($uri, $this->getRoute($method, $uri, $action));
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $action
     * @return \Symfony\Component\Routing\Route
     */
    protected function getRoute(string $method, string $uri, string $action): Route
    {
        /**
         * If the given action is a class that handles WebSockets, then it's not a regular
         * controller but a WebSocketHandler that needs to converted to a WsServer.
         *
         * If the given action is a regular controller we'll just instantiate it.
         */
        $action = is_subclass_of($action, MessageComponentInterface::class)
            ? $this->createWebSocketsServer($action)
            : App::make($action);

        return new Route($uri, ['_controller' => $action], [], [], null, [], [$method]);
    }

    /**
     * @param string $action
     * @return \Ratchet\WebSocket\WsServer
     */
    protected function createWebSocketsServer(string $action): WsServer
    {
        $app = App::make($action);

        if (WebsocketsLogger::isEnabled()) {
            $app = WebsocketsLogger::decorate($app);
        }

        return new WsServer($app);
    }
}
