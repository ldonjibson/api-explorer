<?php

namespace Ldonjibson\ApiExplorer\Support;

/**
 * Parses Postman collection JSON (v2.0 / v2.1) into normalized endpoints.
 */
class PostmanImporter
{
    public function parse(string $content): array
    {
        $collection = json_decode($content, true);

        if (! is_array($collection) || empty($collection['item'])) {
            return [];
        }

        $endpoints = [];

        $this->walk($collection['item'], [], $endpoints);

        return $endpoints;
    }

    protected function walk(array $items, array $groups, array &$endpoints): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! empty($item['item']) && is_array($item['item'])) {
                $this->walk(
                    $item['item'],
                    array_merge($groups, [is_string($item['name'] ?? null) ? $item['name'] : 'Folder']),
                    $endpoints
                );

                continue;
            }

            if (empty($item['request']) || ! is_array($request = $item['request'])) {
                continue;
            }

            $method = strtoupper(is_string($request['method'] ?? null) ? $request['method'] : 'GET');

            if (! in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
                continue;
            }

            [$path, $baseUrl] = $this->splitUrl($request['url'] ?? '');

            $description = $request['description'] ?? null;

            if (is_array($description)) {
                $description = $description['content'] ?? null;
            }

            $endpoints[] = [
                'source' => 'postman',
                'method' => $method,
                'path' => $path,
                'group' => $groups === [] ? 'Collection' : implode(' / ', $groups),
                'name' => is_string($item['name'] ?? null) ? $item['name'] : $method.' '.$path,
                'description' => is_string($description) && $description !== '' ? $description : null,
                'baseUrl' => $baseUrl,
                'params' => $this->params($request, $path),
                'headers' => $this->headers($request),
                'bodyExample' => $this->body($request),
            ];
        }
    }

    /**
     * @return array{0: string, 1: ?string} path, base URL (null when relative)
     */
    protected function splitUrl(mixed $url): array
    {
        $raw = is_string($url) ? $url : (is_array($url) && is_string($url['raw'] ?? null) ? $url['raw'] : '');

        if (preg_match('#^([a-z][a-z0-9+.\-]*://[^/]+)(/.*)?$#i', $raw, $m)) {
            return [$this->postmanVarsToBraces($m[2] ?? '/'), rtrim($m[1], '/')];
        }

        return [$this->postmanVarsToBraces('/'.ltrim($raw, '/')), null];
    }

    /**
     * Postman writes path variables as :userId; the explorer UI works with
     * {userId} placeholders, so convert them.
     */
    protected function postmanVarsToBraces(string $path): string
    {
        return preg_replace('/(?<![a-zA-Z0-9_]):([A-Za-z0-9_]+)/', '{$1}', $path) ?? $path;
    }

    protected function params(array $request, string $path): array
    {
        $params = [];
        $url = $request['url'];

        // Path variables (Postman stores them explicitly when typed).
        if (is_array($url) && ! empty($url['variable']) && is_array($url['variable'])) {
            foreach ($url['variable'] as $variable) {
                if (is_array($variable) && ! empty($variable['key'])) {
                    $params[] = [
                        'name' => $variable['key'],
                        'in' => 'path',
                        'type' => 'string',
                        'required' => true,
                        'description' => is_string($variable['description'] ?? null) ? $variable['description'] : null,
                        'example' => is_string($variable['value'] ?? null) ? $variable['value'] : null,
                    ];
                }
            }
        } else {
            foreach ($this->braces($path) as $name) {
                $params[] = [
                    'name' => $name,
                    'in' => 'path',
                    'type' => 'string',
                    'required' => true,
                    'description' => null,
                    'example' => null,
                ];
            }
        }

        // Query parameters.
        if (is_array($url) && ! empty($url['query']) && is_array($url['query'])) {
            foreach ($url['query'] as $query) {
                if (is_array($query) && ($query['disabled'] ?? false) !== true && ($query['key'] ?? '') !== '') {
                    $params[] = [
                        'name' => $query['key'],
                        'in' => 'query',
                        'type' => 'string',
                        'required' => false,
                        'description' => is_string($query['description'] ?? null) ? $query['description'] : null,
                        'example' => is_string($query['value'] ?? null) ? $query['value'] : null,
                    ];
                }
            }
        }

        // Form bodies become body parameters.
        $body = $request['body'] ?? null;

        if (is_array($body) && in_array($body['mode'] ?? '', ['urlencoded', 'formdata'], true)) {
            foreach ($body[$body['mode']] ?? [] as $field) {
                if (is_array($field) && ($field['disabled'] ?? false) !== true && ($field['key'] ?? '') !== '') {
                    $params[] = [
                        'name' => $field['key'],
                        'in' => 'body',
                        'type' => 'string',
                        'required' => false,
                        'description' => is_string($field['description'] ?? null) ? $field['description'] : null,
                        'example' => is_string($field['value'] ?? null) ? $field['value'] : null,
                    ];
                }
            }
        }

        return $params;
    }

    protected function braces(string $path): array
    {
        preg_match_all('/\{([A-Za-z0-9_]+)\}/', $path, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    protected function headers(array $request): array
    {
        $headers = [];

        if (! empty($request['header']) && is_array($request['header'])) {
            foreach ($request['header'] as $header) {
                if (is_array($header) && ($header['disabled'] ?? false) !== true && ($header['key'] ?? '') !== '') {
                    $headers[] = [
                        'name' => $header['key'],
                        'value' => is_string($header['value'] ?? null) ? $header['value'] : '',
                        'description' => is_string($header['description'] ?? null) ? $header['description'] : null,
                    ];
                }
            }
        }

        return $headers;
    }

    protected function body(array $request): ?string
    {
        $body = $request['body'] ?? null;

        if (! is_array($body)) {
            return null;
        }

        if (($body['mode'] ?? '') === 'raw' && is_string($body['raw'] ?? null)) {
            return $body['raw'];
        }

        return null;
    }
}
