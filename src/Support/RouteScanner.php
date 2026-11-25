<?php

namespace Ldonjibson\ApiExplorer\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Scans the application's routes and turns them into normalized endpoints.
 */
class RouteScanner
{
    public function scan(): array
    {
        $config = config('api-explorer.sources.routes', []);

        if (! ($config['enabled'] ?? true)) {
            return [];
        }

        $prefixes = array_values(array_filter(array_map(
            fn (string $p) => trim($p, '/'),
            $config['prefixes'] ?? []
        )));

        $exclude = $config['exclude'] ?? [];

        $endpoints = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $uri = '/'.ltrim($route->uri(), '/');

            if (! $this->included($uri, $prefixes) || $this->excluded($uri, $exclude)) {
                continue;
            }

            $methods = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));

            if ($methods === []) {
                continue;
            }

            [$group, $name, $summary] = $this->describe($route, $uri);
            $params = $this->pathParams($uri);

            foreach ($methods as $method) {
                $endpoints[] = [
                    'source' => 'routes',
                    'method' => $method,
                    'path' => $uri,
                    'group' => $group,
                    'name' => $name,
                    'description' => $summary,
                    'baseUrl' => null,
                    'params' => $params,
                    'headers' => [],
                    'bodyExample' => null,
                ];
            }
        }

        return $endpoints;
    }

    protected function included(string $uri, array $prefixes): bool
    {
        if ($prefixes === []) {
            return true;
        }

        foreach ($prefixes as $prefix) {
            if (Str::is($prefix.'*', ltrim($uri, '/')) || Str::is('*/'.$prefix.'*', ltrim($uri, '/'))) {
                return true;
            }
        }

        return false;
    }

    protected function excluded(string $uri, array $exclude): bool
    {
        foreach ($exclude as $fragment) {
            if ($fragment !== '' && str_contains($uri, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: string, 1: string, 2: ?string} group, name, summary
     */
    protected function describe(Route $route, string $uri): array
    {
        $action = $route->getAction('controller');
        $routeName = $route->getName();

        if (is_string($action) && $action !== '') {
            [$class, $method] = str_contains($action, '@')
                ? explode('@', $action, 2)
                : [$action, null];

            $basename = class_basename($class);
            $group = str_ends_with($basename, 'Controller')
                ? substr($basename, 0, -strlen('Controller'))
                : $basename;

            $summary = $this->summarize($this->docComment($class, $method));

            $name = $method !== null
                ? $basename.'@'.$method
                : $basename;

            return [$group, $name, $summary];
        }

        // Closure or unknown action: fall back to the second URI segment.
        $segments = array_values(array_filter(explode('/', trim($uri, '/')), fn ($s) => ! str_starts_with($s, '{')));

        $group = isset($segments[1])
            ? ucfirst(Str::camel($segments[1]))
            : ($segments[0] ?? 'Root');

        return [$group, $routeName ?? 'Closure', null];
    }

    protected function docComment(string $class, ?string $method): ?string
    {
        try {
            if ($method !== null && method_exists($class, $method)) {
                $doc = (new \ReflectionMethod($class, $method))->getDocComment();

                if ($doc !== false && $doc !== null) {
                    return $doc;
                }
            }

            $doc = (new \ReflectionClass($class))->getDocComment();

            return $doc === false ? null : $doc;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function summarize(?string $doc): ?string
    {
        if (! $doc || ! preg_match('/\/\*\*(.*?)\*\//s', $doc, $m)) {
            return null;
        }

        foreach (preg_split('/\r?\n/', $m[1]) as $line) {
            $line = trim(preg_replace('/^\s*\/?\*+\s?/', '', $line));

            if ($line === '' || str_starts_with($line, '@')) {
                continue;
            }

            return $line;
        }

        return null;
    }

    protected function pathParams(string $uri): array
    {
        $params = [];

        preg_match_all('/\{([A-Za-z0-9_]+)(\?)?\}/', $uri, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $params[] = [
                'name' => $match[1],
                'in' => 'path',
                'type' => 'string',
                'required' => ($match[2] ?? '') !== '?',
                'description' => null,
                'example' => null,
            ];
        }

        return $params;
    }
}
