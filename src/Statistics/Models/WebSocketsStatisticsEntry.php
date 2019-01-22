<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $app_id
 * @property int $api_message_count
 * @property int $peak_connection_count
 * @property int $websocket_message_count
 * @property string $created_at
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class WebSocketsStatisticsEntry extends Model
{
    protected $guarded = [];

    protected $table = 'websockets_statistics_entries';
}
