<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebSocketStatisticsEntriesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class WebSocketsStatisticsControllerTest extends TestCase
{
    /** @test */
    public function it_can_store_statistics()
    {
        // Skip the broadcast job, since this depends on a running websocket server
        // TODO find a better way to handle AND test this also
        $this->withoutJobs();

        $appId = Config::get('websockets.apps.0.id');
        $path = URL::action(WebSocketStatisticsEntriesController::class . '@store', [], false);
        $query = ['appId' => $appId];
        $body = json_encode($payload = $this->payload());

        $app = App::findById($appId);
        $query['auth_signature'] = $app->generateSignature(
            Request::METHOD_POST,
            $path,
            $query,
            $body
        );

        $response = $this->postJson(
            URL::action(WebSocketStatisticsEntriesController::class . '@store', $query, true),
            $payload
        );

        $this->assertEquals(200, $response->getStatusCode());

        $entries = WebSocketsStatisticsEntry::get();

        $this->assertCount(1, $entries);

        $this->assertArraySubset($this->payload(), $entries->first()->attributesToArray());
    }

    protected function payload(): array
    {
        return [
            'app_id' => config('websockets.apps.0.id'),
            'peak_connection_count' => 1,
            'websocket_message_count' => 2,
            'api_message_count' => 3,
        ];
    }
}
