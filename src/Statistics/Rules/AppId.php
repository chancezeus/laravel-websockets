<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Rules;

use BeyondCode\LaravelWebSockets\Apps\AppProvider;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App;

class AppId implements Rule
{
    public function passes($attribute, $value)
    {
        /** @var AppProvider $appProvider */
        $appProvider = App::make(AppProvider::class);

        return $appProvider->findById($value) ? true : false;
    }

    public function message()
    {
        return 'There is no app registered with the given id. Make sure the websockets config file contains an app for this id or that your custom AppProvider returns an app for this id.';
    }
}
