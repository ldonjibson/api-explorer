<?php

namespace Ldonjibson\ApiExplorer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class EnsureExplorerEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->allowed($request)) {
            return $next($request);
        }

        abort(404);
    }

    protected function allowed(Request $request): bool
    {
        $enabled = config('api-explorer.enabled');

        if ($enabled === null) {
            $enabled = in_array(App::environment(), ['local', 'development', 'testing'], true);
        }

        if ($enabled) {
            return true;
        }

        $authorize = config('api-explorer.authorize');

        return is_callable($authorize) && (bool) $authorize($request);
    }
}
