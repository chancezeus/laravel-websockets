<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Middleware;

use BeyondCode\LaravelWebSockets\Apps\App;
use Illuminate\Http\Request;

class Authorize
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $app = App::findById($request->appId);
        if (! $app) {
            abort(403);
        }

        $signature = $app->generateSignature(
            $request->getMethod(),
            $request->path(),
            $request->query(),
            $request->getContent()
        );

        if ($signature !== $request->get('auth_signature')) {
            abort(403, 'Signature invalid');
        }

        return $next($request);
    }
}
