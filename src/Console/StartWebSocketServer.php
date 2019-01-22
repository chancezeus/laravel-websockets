<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use BeyondCode\LaravelWebSockets\Server\Logger\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketsLogger;
use BeyondCode\LaravelWebSockets\Server\WebSocketServerFactory;
use BeyondCode\LaravelWebSockets\Statistics\DnsResolver;
use BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\StatisticsLogger as StatisticsLoggerInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Buzz\Browser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use React\Dns\Config\Config as DnsConfig;
use React\Dns\Resolver\Factory as DnsFactory;
use React\Dns\Resolver\Resolver as ReactDnsResolver;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Connector;

class StartWebSocketServer extends Command
{
    protected $signature = 'websockets:serve
                                { --host=0.0.0.0}
                                { --port=6001}
                                { --debug : Forces the loggers to be enabled and thereby overriding the app.debug config setting }';

    protected $description = 'Start the Laravel WebSocket Server';

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    public function __construct()
    {
        parent::__construct();

        $this->loop = LoopFactory::create();
    }

    public function handle()
    {
        $this
            ->configureStatisticsLogger()
            ->configureHttpLogger()
            ->configureMessageLogger()
            ->configureConnectionLogger()
            ->registerEchoRoutes()
            ->startWebSocketServer();
    }

    /**
     * @return $this
     */
    protected function configureStatisticsLogger(): StartWebSocketServer
    {
        $connector = new Connector($this->loop, [
            'dns' => $this->getDnsResolver(),
            'tls' => [
                'verify_peer' => Config::get('app.env') === 'production',
                'verify_peer_name' => Config::get('app.env') === 'production',
            ],
        ]);

        $browser = new Browser($this->loop, $connector);

        App::singleton(StatisticsLoggerInterface::class, function () use ($browser) {
            return new HttpStatisticsLogger(App::make(ChannelManager::class), $browser);
        });

        $this->loop->addPeriodicTimer(Config::get('websockets.statistics.interval_in_seconds'), function () {
            StatisticsLogger::save();
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function configureHttpLogger(): StartWebSocketServer
    {
        App::singleton(HttpLogger::class, function () {
            return (new HttpLogger($this->output))
                ->enable($this->option('debug') ?: Config::get('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function configureMessageLogger(): StartWebSocketServer
    {
        App::singleton(WebsocketsLogger::class, function () {
            return (new WebsocketsLogger($this->output))
                ->enable($this->option('debug') ?: Config::get('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function configureConnectionLogger(): StartWebSocketServer
    {
        App::bind(ConnectionLogger::class, function () {
            return (new ConnectionLogger($this->output))
                ->enable(Config::get('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function registerEchoRoutes(): StartWebSocketServer
    {
        WebSocketsRouter::echo();

        return $this;
    }

    protected function startWebSocketServer()
    {
        $this->info("Starting the WebSocket server on port {$this->option('port')}...");

        $routes = WebSocketsRouter::getRoutes();

        /* 🛰 Start the server 🛰  */
        (new WebSocketServerFactory())
            ->setLoop($this->loop)
            ->useRoutes($routes)
            ->setHost($this->option('host'))
            ->setPort($this->option('port'))
            ->setConsoleOutput($this->output)
            ->createServer()
            ->run();
    }

    /**
     * @return \React\Dns\Resolver\Resolver
     */
    protected function getDnsResolver(): ReactDnsResolver
    {
        if (! Config::get('websockets.statistics.perform_dns_lookup')) {
            return new DnsResolver;
        }

        $dnsConfig = DnsConfig::loadSystemConfigBlocking();

        return (new DnsFactory)->createCached(
            $dnsConfig->nameservers
                ? reset($dnsConfig->nameservers)
                : '1.1.1.1',
            $this->loop
        );
    }
}
