<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Events;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Pusher\Pusher;

class StatisticsUpdated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, InteractsWithSockets, Queueable, SerializesModels;

    /** @var \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry */
    protected $webSocketsStatisticsEntry;

    /**
     * @param \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry $webSocketsStatisticsEntry
     */
    public function __construct(WebSocketsStatisticsEntry $webSocketsStatisticsEntry)
    {
        $this->webSocketsStatisticsEntry = $webSocketsStatisticsEntry;
    }

    /**
     * @throws \Pusher\PusherException
     */
    public function handle()
    {
        $app = App::findById($this->webSocketsStatisticsEntry->app_id);

        $options = Config::get('broadcasting.connections.pusher.options');

        // We do not want to connect to pusher itself but to our local (custom) instance
        unset($options['cluster']);

        // Fetch host from options or default to localhost
        $options['host'] = $options['host'] ?? 'localhost';

        // Fetch port from options or default to 6000
        $options['port'] = $options['port'] ?? 6000;

        // Fetch encryption settings from config or default to false
        $options['encrypted'] = $options['encrypted'] ?? false;

        /** @var \Illuminate\Contracts\Broadcasting\Broadcaster $broadcaster */
        $broadcaster = new PusherBroadcaster(new Pusher(
            $app->getKey(),
            $app->getSecret(),
            $app->getId(),
            $options
        ));

        $broadcaster->broadcast(
            Arr::wrap($this->broadcastOn()),
            $this->broadcastAs(),
            array_merge(
                $this->broadcastWith(),
                ['socket' => data_get($this, 'socket')]
            )
        );
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'time' => (string) $this->webSocketsStatisticsEntry->created_at,
            'app_id' => $this->webSocketsStatisticsEntry->app_id,
            'peak_connection_count' => $this->webSocketsStatisticsEntry->peak_connection_count,
            'websocket_message_count' => $this->webSocketsStatisticsEntry->websocket_message_count,
            'api_message_count' => $this->webSocketsStatisticsEntry->api_message_count,
        ];
    }

    /**
     * @return \Illuminate\Broadcasting\PrivateChannel
     */
    public function broadcastOn()
    {
        $channelName = str_after(DashboardLogger::LOG_CHANNEL_PREFIX . 'statistics', 'private-');

        return new PrivateChannel($channelName);
    }

    /**
     * @return string
     */
    public function broadcastAs()
    {
        return 'statistics-updated';
    }
}
