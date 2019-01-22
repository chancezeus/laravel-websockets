<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use Illuminate\Support\Facades\Config;

class DashboardApiController
{
    /**
     * @param string $appId
     * @return array
     */
    public function getStatistics(string $appId): array
    {
        $webSocketsStatisticsEntryModelClass = Config::get('websockets.statistics.model');

        /** @var \Illuminate\Support\Collection|\BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry[] $statistics */
        $statistics = $webSocketsStatisticsEntryModelClass::where('app_id', $appId)->latest()->limit(120)->get();

        $statisticData = $statistics->map(function ($statistic) {
            return [
                'timestamp' => (string) $statistic->created_at,
                'peak_connection_count' => $statistic->peak_connection_count,
                'websocket_message_count' => $statistic->websocket_message_count,
                'api_message_count' => $statistic->api_message_count,
            ];
        })->reverse();

        return [
            'peak_connections' => [
                'x' => $statisticData->pluck('timestamp'),
                'y' => $statisticData->pluck('peak_connection_count'),
            ],
            'websocket_message_count' => [
                'x' => $statisticData->pluck('timestamp'),
                'y' => $statisticData->pluck('websocket_message_count'),
            ],
            'api_message_count' => [
                'x' => $statisticData->pluck('timestamp'),
                'y' => $statisticData->pluck('api_message_count'),
            ],
        ];
    }
}
