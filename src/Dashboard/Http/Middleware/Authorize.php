<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class Authorize
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if (! Gate::check('viewWebSocketsDashboard', [$request->user()])) {
            abort(403);
        }

        return $next($request);
    }
}
