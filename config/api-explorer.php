<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable switch
    |--------------------------------------------------------------------------
    |
    | null  = auto: the explorer is enabled only in non-production environments
    |         (local, development, testing).
    | true  = always enabled (also in production — make sure you protect it
    |         with the "authorize" callback below).
    | false = disabled everywhere.
    |
    */

    'enabled' => env('API_EXPLORER_ENABLED'),

    /*
    |--------------------------------------------------------------------------
    | URL path
    |--------------------------------------------------------------------------
    |
    | The explorer UI will be served at /{path}, e.g. http://app.test/api-explorer
    |
    */

    'path' => env('API_EXPLORER_PATH', 'api-explorer'),

    /*
    |--------------------------------------------------------------------------
    | Extra middleware
    |--------------------------------------------------------------------------
    |
    | Additional middleware applied to every explorer route, e.g.:
    | ['auth', 'can:viewApiExplorer'] or a custom gate.
    |
    */

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Authorize callback
    |--------------------------------------------------------------------------
    |
    | Optional closure used to unlock the explorer when "enabled" is false.
    | Return true to allow access. Example:
    |
    | 'authorize' => fn ($request) => $request->user()?->is_admin,
    |
    */

    'authorize' => null,

    /*
    |--------------------------------------------------------------------------
    | Endpoint sources
    |--------------------------------------------------------------------------
    */

    'sources' => [

        // Scan your Laravel routes automatically.
        'routes' => [
            'enabled' => true,

            // Only URIs starting with these prefixes are listed.
            // Set to [] to list every route in the application.
            'prefixes' => ['api'],

            // URIs containing any of these fragments are hidden.
            'exclude' => [
                'telescope', 'horizon', '_ignition', 'sanctum',
                'up', 'storage',
            ],
        ],

        // OpenAPI / Swagger spec files loaded on every request.
        // JSON is always supported; YAML needs symfony/yaml installed.
        'openapi' => [
            'enabled' => true,
            'files' => [
                // storage_path('openapi.json'),
            ],
        ],

        // Postman collection files (v2.0 / v2.1) loaded on every request.
        // Collections can also be pasted straight from the UI at runtime.
        'postman' => [
            'enabled' => true,
            'files' => [
                // resource_path('postman/collection.json'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request proxy (the "Send" button)
    |--------------------------------------------------------------------------
    |
    | The browser sends test requests through a server-side proxy endpoint so
    | CORS never gets in the way. To limit SSRF-style abuse:
    |
    | allowed_hosts: null  -> only your own app host (app.url / request host)
    |                         plus localhost are reachable.
    | allowed_hosts: ['*'] -> any host is reachable.
    | allowed_hosts: ['api.acme.test', '10.0.0.*'] -> explicit allow-list,
    |                         supports fnmatch wildcards.
    |
    */

    'proxy' => [
        'timeout' => 30,
        'max_response_bytes' => 1000000,
        'allowed_hosts' => null,
    ],
];
