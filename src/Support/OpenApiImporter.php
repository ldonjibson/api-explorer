<?php

namespace Ldonjibson\ApiExplorer\Support;

/**
 * Parses OpenAPI 2 (Swagger) / 3.x specs (JSON, or YAML when symfony/yaml
 * is installed) into normalized endpoints.
 */
class OpenApiImporter
{
    public function parse(string $content): array
    {
        $spec = $this->decode($content);

        if (! is_array($spec) || empty($spec['paths'])) {
            return [];
        }

        $baseUrl = null;

        if (! empty($spec['servers'][0]['url']) && is_string($spec['servers'][0]['url'])) {
            $baseUrl = $spec['servers'][0]['url'];
        } elseif (! empty($spec['host']) && is_string($spec['host'])) {
            $scheme = $spec['schemes'][0] ?? 'https';
            $basePath = $spec['basePath'] ?? '';
            $baseUrl = rtrim($scheme.'://'.$spec['host'], '/').'/'.ltrim((string) $basePath, '/');
        }

        $endpoints = [];

        foreach ($spec['paths'] as $path => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            foreach (['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] as $method) {
                if (empty($pathItem[$method]) || ! is_array($op = $pathItem[$method])) {
                    continue;
                }

                $parameters = array_values(array_merge(
                    is_array($pathItem['parameters'] ?? null) ? $pathItem['parameters'] : [],
                    is_array($op['parameters'] ?? null) ? $op['parameters'] : []
                ));

                $endpoints[] = [
                    'source' => 'openapi',
                    'method' => strtoupper($method),
                    'path' => '/'.ltrim($path, '/'),
                    'group' => $this->group($spec, $op, $path),
                    'name' => $op['operationId'] ?? $op['summary'] ?? $method.' '.$path,
                    'description' => $this->description($op),
                    'baseUrl' => $baseUrl,
                    'params' => $this->params($parameters),
                    'headers' => [],
                    'bodyExample' => $this->bodyExample($op['requestBody'] ?? null),
                ];
            }
        }

        return $endpoints;
    }

    protected function decode(string $content): ?array
    {
        $data = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            try {
                $data = \Symfony\Component\Yaml\Yaml::parse($content);

                return is_array($data) ? $data : null;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function group(array $spec, array $op, string $path): string
    {
        $tag = $op['tags'][0] ?? null;

        if (is_string($tag) && $tag !== '') {
            return $tag;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        return isset($segments[0]) && ! str_starts_with($segments[0], '{')
            ? ucfirst($segments[0])
            : ($spec['info']['title'] ?? 'API');
    }

    protected function description(array $op): ?string
    {
        $description = $op['description'] ?? $op['summary'] ?? null;

        return is_string($description) && $description !== '' ? $description : null;
    }

    protected function params(array $parameters): array
    {
        $params = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter) || empty($parameter['name'])) {
                continue;
            }

            $in = in_array($parameter['in'] ?? 'query', ['path', 'query', 'header'], true)
                ? $parameter['in']
                : 'query';

            $params[] = [
                'name' => $parameter['name'],
                'in' => $in,
                'type' => $parameter['schema']['type']
                    ?? $parameter['type']
                    ?? 'string',
                'required' => (bool) ($parameter['required'] ?? false),
                'description' => is_string($parameter['description'] ?? null) ? $parameter['description'] : null,
                'example' => $parameter['example']
                    ?? $parameter['schema']['example']
                    ?? $parameter['schema']['default']
                    ?? null,
            ];
        }

        return $params;
    }

    protected function bodyExample(mixed $requestBody): ?string
    {
        if (! is_array($requestBody)) {
            return null;
        }

        $json = $requestBody['content']['application/json'] ?? null;

        if (! is_array($json)) {
            return null;
        }

        if (isset($json['example'])) {
            return json_encode($json['example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if (isset($json['examples']) && is_array($json['examples'])) {
            $first = reset($json['examples']);

            if (is_array($first) && isset($first['value'])) {
                return json_encode($first['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }

        if (! isset($json['schema']) || ! is_array($json['schema'])) {
            return null;
        }

        $sample = $this->sampleSchema($json['schema']);

        return $sample === null ? null : json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function sampleSchema(array $schema): mixed
    {
        if (array_key_exists('example', $schema)) {
            return $schema['example'];
        }

        if (array_key_exists('default', $schema)) {
            return $schema['default'];
        }

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            $object = [];

            foreach ($schema['properties'] as $name => $property) {
                $object[$name] = $this->sampleSchema(is_array($property) ? $property : []);
            }

            return $object;
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            return [$this->sampleSchema($schema['items'])];
        }

        return match ($schema['type'] ?? 'object') {
            'integer', 'number' => 0,
            'boolean' => true,
            'array' => [],
            'object' => new \stdClass,
            default => '',
        };
    }
}
