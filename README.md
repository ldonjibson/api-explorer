# API Explorer for Laravel

A Swagger-UI-meets-Postman explorer that lives inside your Laravel app
(Laravel 10–13).

- **Lists every API endpoint** from three sources, merged and deduplicated:
  - 🟠 **Laravel routes** — auto-scanned, with controller docblock summaries
  - 🟢 **OpenAPI / Swagger** specs (JSON, and YAML with `symfony/yaml`)
  - 🔵 **Postman collections** (v2.0 / v2.1)
- **Postman-like Send button** — build query params, headers and a JSON body,
  then send. Requests go through a server-side proxy route, so CORS never
  blocks you.
- **Extras** — `{{variable}}` substitution, request history, JSON syntax
  highlighting, paste-or-upload imports from the UI, keyboard search (Ctrl+K).

## Installation

### 1. Install the package

```bash
composer require Ldonjibson/api-explorer
```

While it is not yet on Packagist, install it from the folder directly — in your
Laravel app's `composer.json` add:

```json
"repositories": [
    { "type": "path", "url": "../path/to/api-explorer" }
]
```

then run `composer require Ldonjibson/api-explorer`.

Optional, for YAML specs:

```bash
composer require symfony/yaml
```

### 2. (Optional) Publish the config

```bash
php artisan vendor:publish --tag=api-explorer-config
```

### 3. Open it

Visit **`http://your-app.test/api-explorer`** (enabled automatically in
`local`, `development` and `testing` environments).

## Configuration

```php
// config/api-explorer.php
'enabled' => env('API_EXPLORER_ENABLED'),  // null = non-production only
'path'    => 'api-explorer',
'middleware' => [],                        // extra middleware, e.g. ['auth']

'authorize' => null,                       // fn ($request) => $request->user()?->is_admin

'sources' => [
    'routes' => [
        'enabled'  => true,
        'prefixes' => ['api'],   // [] = list every route
        'exclude'  => ['telescope', 'horizon', ...],
    ],
    'openapi' => [
        'enabled' => true,
        'files'   => [storage_path('openapi.json')],  // spec files to merge
    ],
    'postman' => [
        'enabled' => true,
        'files'   => [resource_path('postman/collection.json')],
    ],
],

'proxy' => [
    'timeout'             => 30,
    'max_response_bytes'  => 1000000,
    // null = only the app host + localhost; ['*'] = any host;
    // or an explicit allow-list, wildcards supported: ['api.acme.test', '10.0.0.*']
    'allowed_hosts'       => null,
],
```

### Documenting routes

Add docblocks to your controllers and they show up as descriptions:

```php
/**
 * Create a new invoice for a customer.
 */
public function store(StoreInvoiceRequest $request): InvoiceResource
{
    //
}
```

## How the Send button works

The browser POSTs your built request to the package's proxy endpoint
(`/api-explorer/send`), the server performs the actual HTTP request and
returns status, timing, headers and body. That sidesteps CORS entirely —
including calls to other services.

Because of that, the proxy is guarded:

- Disabled in production by default (see `enabled`).
- By default it can only reach **your own app host** and **localhost**.
- Use `proxy.allowed_hosts` to widen or restrict the target list.

> **Tip:** PHP's built-in server (`php artisan serve`) handles one request at
> a time, so a proxied request back to the same server can deadlock. Serve the
> app with Herd, Valet, Octane or nginx + FPM when testing the Send button.

## Tips

- Imported collections and specs are stored in your browser (localStorage),
  so ad-hoc imports don't touch the server.
- Re-run any request from **History** with one click.
- Use **Variables** for `{{base_url}}`, `{{token}}` etc.

## License

MIT
