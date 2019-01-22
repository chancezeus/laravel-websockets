<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use BeyondCode\LaravelWebSockets\Statistics\Logger\FakeStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\StatisticsLogger as StatisticsLoggerInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void apiMessage(string $appId)
 * @method static void connection(\Ratchet\ConnectionInterface $connection)
 * @method static void disconnection(\Ratchet\ConnectionInterface $connection)
 * @method static void save()
 * @method static void webSocketMessage(\Ratchet\ConnectionInterface $connection)
 */
class StatisticsLogger extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return StatisticsLoggerInterface::class;
    }

    public static function fake()
    {
        static::swap(new FakeStatisticsLogger());
    }
}
