<?php
// Standalone smoke test for the importers (no Laravel needed).

function config($key, $default = null) {
    return ['sources' => ['routes' => ['enabled' => false]]][$key] ?? $default;
}

spl_autoload_register(function ($class) {
    $prefix = 'Ldonjibson\\ApiExplorer\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

$openapi = json_encode([
    'openapi' => '3.0.2',
    'info' => ['title' => 'Petstore'],
    'servers' => [['url' => 'https://petstore.test/v1']],
    'paths' => [
        '/pets' => [
            'get' => [
                'tags' => ['pets'],
                'summary' => 'List all pets',
                'parameters' => [['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer'], 'example' => 10]],
            ],
            'post' => [
                'tags' => ['pets'],
                'operationId' => 'addPet',
                'requestBody' => ['content' => ['application/json' => [
                    'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string'], 'age' => ['type' => 'integer']]],
                ]]],
            ],
        ],
        '/pets/{petId}' => [
            'get' => ['tags' => ['pets'], 'summary' => 'Info for a specific pet',
                'parameters' => [['name' => 'petId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]]],
            'delete' => ['tags' => ['pets'], 'summary' => 'Delete a pet'],
        ],
    ],
]);

$postman = json_encode([
    'info' => ['name' => 'My API', 'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'],
    'item' => [
        ['name' => 'Auth', 'item' => [
            ['name' => 'Login', 'request' => [
                'method' => 'POST', 'header' => [['key' => 'Authorization', 'value' => 'Bearer {{token}}']],
                'url' => ['raw' => 'https://api.acme.test/v1/auth/login', 'host' => ['api', 'acme', 'test']],
                'body' => ['mode' => 'raw', 'raw' => '{"email":"a@b.c","password":"secret"}'],
            ]],
            ['name' => 'Refresh', 'request' => ['method' => 'POST',
                'url' => ['raw' => 'https://api.acme.test/v1/auth/refresh']]],
        ]],
        ['name' => 'Users', 'item' => [
            ['name' => 'Get user', 'request' => [
                'method' => 'GET',
                'url' => ['raw' => 'https://api.acme.test/v1/users/:userId', 'variable' => [['key' => 'userId', 'value' => '42']],
                    'query' => [['key' => 'withPosts', 'value' => 'true']]],
            ]],
            ['name' => 'Disabled thing', 'request' => ['method' => 'TRACE', 'url' => 'x']],
        ]],
    ],
]);

$open = new Ldonjibson\ApiExplorer\Support\OpenApiImporter();
$post = new Ldonjibson\ApiExplorer\Support\PostmanImporter();

$fromOpen = $open->parse($openapi);
$fromPost = $post->parse($postman);

echo "OpenAPI endpoints: " . count($fromOpen) . "\n";
foreach ($fromOpen as $e) {
    echo "  [{$e['method']}] {$e['path']}  group={$e['group']}  baseUrl={$e['baseUrl']}\n";
    if ($e['bodyExample']) echo "    body: " . preg_replace('/\s+/', ' ', $e['bodyExample']) . "\n";
}
echo "Postman endpoints: " . count($fromPost) . "\n";
foreach ($fromPost as $e) {
    echo "  [{$e['method']}] {$e['path']}  group={$e['group']}  baseUrl={$e['baseUrl']}  headers=" . count($e['headers']) . " params=" . count($e['params']) . "\n";
}

// YAML path (skip if symfony/yaml not present globally)
$yaml = <<<YAML
openapi: 3.0.0
info:
  title: Yaml API
paths:
  /things:
    get:
      summary: List things
YAML;

$fromYaml = $open->parse($yaml);
echo "YAML endpoints: " . count($fromYaml) . " (symfony/yaml present: " . var_export(class_exists('Symfony\\Component\\Yaml\\Yaml'), true) . ")\n";

echo ($fromOpen && $fromPost) ? "SMOKE TEST OK\n" : "SMOKE TEST FAILED\n";
