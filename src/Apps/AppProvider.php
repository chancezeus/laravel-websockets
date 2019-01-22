<?php

namespace BeyondCode\LaravelWebSockets\Apps;

interface AppProvider
{
    /**
     * @return \BeyondCode\LaravelWebSockets\Apps\App[]
     */
    public function all(): array;

    /**
     * @param string $appId
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public function findById(string $appId): ?App;

    /**
     * @param string $appKey
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public function findByKey(string $appKey): ?App;
}
