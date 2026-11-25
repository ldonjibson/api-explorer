<?php

namespace Ldonjibson\ApiExplorer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ldonjibson\ApiExplorer\Support\EndpointRegistry;
use Ldonjibson\ApiExplorer\Support\OpenApiImporter;
use Ldonjibson\ApiExplorer\Support\PostmanImporter;
use Throwable;

class ApiExplorerController extends Controller
{
    public function index()
    {
        return view('api-explorer::explorer');
    }

    public function endpoints(EndpointRegistry $registry): JsonResponse
    {
        return response()->json([
            'endpoints' => $registry->all(),
        ]);
    }

    /**
     * Parse an OpenAPI spec or Postman collection pasted/uploaded at runtime.
     */
    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:openapi,postman'],
            'content' => ['required', 'string', 'max:4000000'],
        ]);

        try {
            $endpoints = match ($data['type']) {
                'openapi' => app(OpenApiImporter::class)->parse($data['content']),
                'postman' => app(PostmanImporter::class)->parse($data['content']),
            };
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Could not parse the content: '.$e->getMessage(),
            ], 422);
        }

        if ($endpoints === []) {
            return response()->json([
                'message' => 'No endpoints found — is this a valid spec or collection?',
            ], 422);
        }

        return response()->json([
            'message' => 'Imported '.count($endpoints).' endpoint(s).',
            'endpoints' => $endpoints,
        ]);
    }
}
