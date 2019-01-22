<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Controller implements HttpServerInterface
{
    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    /**
     * @param \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager $channelManager
     */
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Psr\Http\Message\RequestInterface|null $request
     */
    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $serverRequest = (new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        ))->withQueryParams(QueryParameters::create($request)->all());

        $laravelRequest = Request::createFromBase((new HttpFoundationFactory)->createRequest($serverRequest));

        $this
            ->ensureValidAppId($laravelRequest->appId)
            ->ensureValidSignature($laravelRequest);

        $response = $this($laravelRequest);

        $connection->send(JsonResponse::create($response));
        $connection->close();
    }

    /**
     * @param \Ratchet\ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     */
    public function onClose(ConnectionInterface $connection)
    {
    }

    /**
     * @param \Ratchet\ConnectionInterface $connection
     * @param \Exception $exception
     */
    public function onError(ConnectionInterface $connection, \Exception $exception)
    {
        if (! $exception instanceof HttpException) {
            return;
        }

        $response = new Response($exception->getStatusCode(), [
            'Content-Type' => 'application/json',
        ], json_encode([
            'error' => $exception->getMessage(),
        ]));

        $connection->send(\GuzzleHttp\Psr7\str($response));

        $connection->close();
    }

    /**
     * @param string $appId
     * @return $this
     */
    public function ensureValidAppId(string $appId): Controller
    {
        if (! App::findById($appId)) {
            throw new HttpException(401, "Unknown app id `{$appId}` provided.");
        }

        return $this;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return $this
     */
    protected function ensureValidSignature(Request $request)
    {
        $app = App::findById($request->appId);
        if (! $app) {
            throw new HttpException(401, 'Invalid auth signature provided.');
        }

        $signature = $app->generateSignature(
            $request->getMethod(),
            $request->path(),
            $request->query(),
            $request->getContent()
        );

        if ($signature !== $request->get('auth_signature')) {
            throw new HttpException(401, 'Invalid auth signature provided.');
        }

        return $this;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    abstract public function __invoke(Request $request);
}
