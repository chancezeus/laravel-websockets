<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebSocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\Statistics\Statistic;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Buzz\Browser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\stream_for;

class HttpStatisticsLogger implements StatisticsLogger
{
    /** @var \BeyondCode\LaravelWebSockets\Statistics\Statistic[] */
    protected $statistics = [];

    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    /** @var \Clue\React\Buzz\Browser */
    protected $browser;

    /**
     * @param \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager $channelManager
     * @param \Clue\React\Buzz\Browser $browser
     */
    public function __construct(ChannelManager $channelManager, Browser $browser)
    {
        $this->channelManager = $channelManager;

        $this->browser = $browser;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function webSocketMessage(ConnectionInterface $connection): void
    {
        $this
            ->findOrMakeStatisticForAppId($connection->app->getId())
            ->webSocketMessage();
    }

    /**
     * @param string $appId
     */
    public function apiMessage(string $appId): void
    {
        $this
            ->findOrMakeStatisticForAppId($appId)
            ->apiMessage();
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function connection(ConnectionInterface $connection): void
    {
        $this
            ->findOrMakeStatisticForAppId($connection->app->getId())
            ->connection();
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function disconnection(ConnectionInterface $connection): void
    {
        $this
            ->findOrMakeStatisticForAppId($connection->app->getId())
            ->disconnection();
    }

    /**
     * @param string $appId
     * @return \BeyondCode\LaravelWebSockets\Statistics\Statistic
     */
    protected function findOrMakeStatisticForAppId(string $appId): Statistic
    {
        if (! isset($this->statistics[$appId])) {
            $this->statistics[$appId] = new Statistic($appId);
        }

        return $this->statistics[$appId];
    }

    public function save(): void
    {
        foreach ($this->statistics as $appId => $statistic) {
            if (! $statistic->isEnabled()) {
                continue;
            }

            $path = URL::action('\\' . WebSocketStatisticsEntriesController::class . '@store', [], false);
            $query = ['appId' => $appId];
            $body = json_encode($statistic->toArray());

            $app = App::findById($appId);
            $query['auth_signature'] = $app->generateSignature(
                Request::METHOD_POST,
                $path,
                $query,
                $body
            );

            $this
                ->browser
                ->post(
                    URL::action('\\' . WebSocketStatisticsEntriesController::class . '@store', $query),
                    ['Content-Type' => 'application/json'],
                    stream_for($body)
                );

            $currentConnectionCount = $this->channelManager->getConnectionCount($appId);
            $statistic->reset($currentConnectionCount);
        }
    }
}
