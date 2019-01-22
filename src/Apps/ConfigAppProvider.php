<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class ConfigAppProvider implements AppProvider
{
    /** @var \Illuminate\Support\Collection */
    protected $apps;

    public function __construct()
    {
        $this->apps = Collection::make(Config::get('websockets.apps', []));
    }

    /**
     * @return \BeyondCode\LaravelWebSockets\Apps\App[]
     */
    public function all(): array
    {
        return $this->apps
            ->map(function (array $appAttributes) {
                return $this->instantiate($appAttributes);
            })
            ->toArray();
    }

    /**
     * @param $appId
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     * @throws \BeyondCode\LaravelWebSockets\Exceptions\InvalidApp
     */
    public function findById($appId): ?App
    {
        $appAttributes = $this
            ->apps
            ->firstWhere('id', $appId);

        return $this->instantiate($appAttributes);
    }

    /**
     * @param string $appKey
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     * @throws \BeyondCode\LaravelWebSockets\Exceptions\InvalidApp
     */
    public function findByKey(string $appKey): ?App
    {
        $appAttributes = $this
            ->apps
            ->firstWhere('key', $appKey);

        return $this->instantiate($appAttributes);
    }

    /**
     * @param array|null $appAttributes
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     * @throws \BeyondCode\LaravelWebSockets\Exceptions\InvalidApp
     */
    protected function instantiate(?array $appAttributes): ?App
    {
        if (! $appAttributes) {
            return null;
        }

        $app = new App(
            $appAttributes['id'],
            $appAttributes['key'],
            $appAttributes['secret']
        );

        if (isset($appAttributes['name'])) {
            $app->setName($appAttributes['name']);
        }

        if (isset($appAttributes['host'])) {
            $app->setHost($appAttributes['host']);
        }

        $app
            ->enableClientMessages($appAttributes['enable_client_messages'] ?? false)
            ->enableStatistics($appAttributes['enable_statistics'] ?? true);

        return $app;
    }
}
