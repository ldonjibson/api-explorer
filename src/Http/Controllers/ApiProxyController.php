<?php

namespace Ldonjibson\ApiExplorer\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ApiProxyController extends Controller
{
    private const METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private const FORBIDDEN_HEADERS = [
        'host', 'content-length', 'connection', 'transfer-encoding',
        'keep-alive', 'upgrade', 'expect',
    ];

    /**
     * Server-side proxy: performs the HTTP request the user built in the UI
     * and returns status, timing, headers and body so the browser never has
     * to fight CORS.
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url'],
            'method' => ['required', 'string'],
            'headers' => ['nullable', 'array'],
            'query' => ['nullable', 'array'],
            'body' => ['nullable', 'string', 'max:4000000'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $method = strtoupper($data['method']);

        if (! in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
            return response()->json(['message' => "Unsupported method: {$method}"], 422);
        }

        $this->assertHostAllowed($url = $data['url'], $request);

        if (! empty($data['query'])) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($data['query']);
        }

        $headers = [];

        foreach ($data['headers'] ?? [] as $name => $value) {
            if ($value === null || $value === '' || $name === '') {
                continue;
            }

            if (in_array(strtolower($name), self::FORBIDDEN_HEADERS, true)) {
                continue;
            }

            $headers[$name] = $value;
        }

        $body = ($data['body'] ?? null) !== null && in_array($method, self::METHODS_WITH_BODY, true)
            ? $data['body']
            : null;

        $http = Http::timeout((int) ($data['timeout'] ?? config('api-explorer.proxy.timeout', 30)))
            ->withHeaders($headers);

        if ($body !== null) {
            $http = $http->withBody($body, $headers['Content-Type'] ?? 'application/json');
        }

        $start = hrtime(true);

        try {
            $response = $http->send($method, $url);
        } catch (ConnectionException $e) {
            return response()->json([
                'ok' => false,
                'error' => 'Connection failed: '.$e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ]);
        }

        $timeMs = round((hrtime(true) - $start) / 1e6, 1);

        $max = (int) config('api-explorer.proxy.max_response_bytes', 1000000);
        $responseBody = mb_substr($response->body(), 0, $max);

        $responseHeaders = [];

        foreach ($response->headers() as $name => $values) {
            $responseHeaders[$name] = implode(', ', (array) $values);
        }

        return response()->json([
            'ok' => true,
            'status' => $response->status(),
            'statusText' => Response::$statusTexts[$response->status()] ?? '',
            'timeMs' => $timeMs,
            'size' => strlen($responseBody),
            'contentType' => $response->header('Content-Type'),
            'headers' => $responseHeaders,
            'body' => $responseBody,
            'url' => $url,
        ]);
    }

    protected function assertHostAllowed(string $url, Request $request): void
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if ($host === '') {
            abort(403, 'Could not determine the request host.');
        }

        $allowed = config('api-explorer.proxy.allowed_hosts');

        if (is_array($allowed)) {
            if (in_array('*', $allowed, true)) {
                return;
            }

            foreach ($allowed as $pattern) {
                if (fnmatch(strtolower($pattern), $host)) {
                    return;
                }
            }

            abort(403, "Host \"{$host}\" is not on the api-explorer proxy allow-list.");
        }

        // Default: only the app itself and localhost are reachable.
        $appHost = strtolower(parse_url(config('app.url') ?: $request->getSchemeAndHttpHost(), PHP_URL_HOST) ?? '');
        $requestHost = strtolower($request->getHost());

        $local = ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '::1'];

        if (in_array($host, $local, true)) {
            return;
        }

        if ($host !== $appHost && $host !== $requestHost) {
            abort(403, "Host \"{$host}\" is not reachable through the api-explorer proxy. ".
                'Configure api-explorer.proxy.allowed_hosts to allow external hosts.');
        }
    }
}
