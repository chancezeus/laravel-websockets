<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use BeyondCode\LaravelWebSockets\Exceptions\InvalidApp;
use Illuminate\Support\Facades\App as AppContainer;
use Pusher\Pusher;

class App implements \JsonSerializable
{
    /** @var string */
    private $id;

    /** @var string */
    private $key;

    /** @var string */
    private $secret;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $host;

    /** @var bool */
    private $clientMessagesEnabled = false;

    /** @var bool */
    private $statisticsEnabled = true;

    /**
     * @param $appId
     * @return static
     */
    public static function findById($appId): App
    {
        return AppContainer::make(AppProvider::class)->findById($appId);
    }

    /**
     * @param string $appKey
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public static function findByKey(string $appKey): ?self
    {
        return AppContainer::make(AppProvider::class)->findByKey($appKey);
    }

    /**
     * @param string $appId
     * @param string $appKey
     * @param string $appSecret
     * @throws \BeyondCode\LaravelWebSockets\Exceptions\InvalidApp
     */
    public function __construct(string $appId, string $appKey, string $appSecret)
    {
        if ($appKey === '') {
            throw InvalidApp::valueIsRequired('appKey', $appId);
        }

        if ($appSecret === '') {
            throw InvalidApp::valueIsRequired('appSecret', $appId);
        }

        $this->id = $appId;

        $this->key = $appKey;

        $this->secret = $appSecret;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return bool
     */
    public function isClientMessagesEnabled(): bool
    {
        return $this->clientMessagesEnabled;
    }

    /**
     * @return bool
     */
    public function isStatisticsEnabled(): bool
    {
        return $this->statisticsEnabled;
    }

    /**
     * @param string $appName
     * @return $this
     */
    public function setName(string $appName): App
    {
        $this->name = $appName;

        return $this;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost(string $host): App
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function enableClientMessages(bool $enabled = true): App
    {
        $this->clientMessagesEnabled = $enabled;

        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function enableStatistics(bool $enabled = true): App
    {
        $this->statisticsEnabled = $enabled;

        return $this;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $data
     * @param string $body
     * @return string
     */
    public function generateSignature(string $method, string $path, array $data, string $body = ''): string
    {
        $path = trim($path, '/') ?: '/';

        /*
         * The `auth_signature` & `body_md5` parameters are not included when calculating the `auth_signature` value.
         *
         * The `appId`, `appKey` & `channelName` parameters are actually route parameters and are never supplied by the client.
         */
        $params = array_except($data, ['auth_signature', 'body_md5', 'appId', 'appKey', 'channelName']);

        if ($body !== '') {
            $params['body_md5'] = md5($body);
        }

        ksort($params);

        $signature = "{$method}\n/{$path}\n" . Pusher::array_implode('=', '&', $params);

        return hash_hmac('sha256', $signature, $this->secret);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'host' => $this->host,
            'clientMessagesEnabled' => $this->clientMessagesEnabled,
            'statisticsEnabled' => $this->statisticsEnabled,
        ];
    }
}
