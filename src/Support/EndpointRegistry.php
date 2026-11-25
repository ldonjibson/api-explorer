<?php

namespace Ldonjibson\ApiExplorer\Support;

use Illuminate\Support\Facades\File;
use Ldonjibson\ApiExplorer\Support\OpenApiImporter;
use Ldonjibson\ApiExplorer\Support\PostmanImporter;
use Ldonjibson\ApiExplorer\Support\RouteScanner;

/**
 * Merges every configured source into a single, deduplicated endpoint list.
 */
class EndpointRegistry
{
    public function __construct(
        protected RouteScanner $routes,
        protected OpenApiImporter $openapi,
        protected PostmanImporter $postman,
    ) {}

    public function all(): array
    {
        $config = config('api-explorer.sources', []);

        $endpoints = $this->routes->scan();

        if ($config['openapi']['enabled'] ?? true) {
            foreach ((array) ($config['openapi']['files'] ?? []) as $file) {
                $endpoints = array_merge($endpoints, $this->parseFile($this->openapi, $file));
            }
        }

        if ($config['postman']['enabled'] ?? true) {
            foreach ((array) ($config['postman']['files'] ?? []) as $file) {
                $endpoints = array_merge($endpoints, $this->parseFile($this->postman, $file));
            }
        }

        if (config('api-explorer.dedupe', true)) {
            $endpoints = $this->dedupe($endpoints);
        }

        usort($endpoints, function (array $a, array $b): int {
            return [$a['group'], $a['path'], $a['method']]
                <=> [$b['group'], $b['path'], $b['method']];
        });

        return $endpoints;
    }

    protected function parseFile(object $importer, string $file): array
    {
        try {
            if (! is_file($file) || ! File::exists($file)) {
                return [];
            }

            return $importer->parse(File::get($file));
        } catch (\Throwable) {
            return [];
        }
    }

    protected function dedupe(array $endpoints): array
    {
        $seen = [];
        $unique = [];

        foreach ($endpoints as $endpoint) {
            $key = strtoupper($endpoint['method']).'|'.rtrim($endpoint['path'], '/');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $endpoint;
        }

        return $unique;
    }
}
