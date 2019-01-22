<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Events\StatisticsUpdated;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class WebSocketStatisticsEntriesController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function store(Request $request)
    {
        $validatedAttributes = Validator::make($request->all(), [
            'app_id' => ['required', new AppId()],
            'peak_connection_count' => 'required|integer',
            'websocket_message_count' => 'required|integer',
            'api_message_count' => 'required|integer',
        ])->validate();

        $webSocketsStatisticsEntryModelClass = Config::get('websockets.statistics.model');

        $statisticModel = $webSocketsStatisticsEntryModelClass::create($validatedAttributes);

        StatisticsUpdated::dispatch($statisticModel);

        return 'ok';
    }
}
