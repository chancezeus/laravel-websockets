<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void echo ()
 * @method static \Symfony\Component\Routing\RouteCollection getRoutes()
 *
 * @see \BeyondCode\LaravelWebSockets\Server\Router
 */
class WebSocketsRouter extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'websockets.router';
    }
}
