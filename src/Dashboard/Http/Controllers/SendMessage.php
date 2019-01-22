<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Pusher\Pusher;

class SendMessage
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return string
     * @throws \Pusher\PusherException
     */
    public function __invoke(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'appId' => ['required', new AppId()],
            'key' => 'required',
            'secret' => 'required',
            'channel' => 'required',
            'event' => 'required',
            'data' => 'json',
        ])->validate();

        $this->getPusherBroadcaster($validated)->broadcast(
            [$validated['channel']],
            $validated['event'],
            json_decode($validated['data'], true)
        );

        return 'ok';
    }

    /**
     * @param array $validated
     * @return \Illuminate\Broadcasting\Broadcasters\PusherBroadcaster
     * @throws \Pusher\PusherException
     */
    protected function getPusherBroadcaster(array $validated): PusherBroadcaster
    {
        $pusher = new Pusher(
            $validated['key'],
            $validated['secret'],
            $validated['appId'],
            Config::get('broadcasting.connections.pusher.options', [])
        );

        return new PusherBroadcaster($pusher);
    }
}
