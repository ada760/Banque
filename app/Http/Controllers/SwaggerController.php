<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SwaggerController extends Controller
{
    /**
     * Display Swagger UI
     */
    public function index()
    {
        return view('swagger.index');
    }

    /**
     * Generate OpenAPI JSON
     */
    public function json()
    {
        // Try to load from cache first
        $cacheFile = storage_path('app/api-docs/api-docs.json');
        if (file_exists($cacheFile)) {
            $json = file_get_contents($cacheFile);
            return response($json)->header('Content-Type', 'application/json');
        }

        // Generate on the fly if cache doesn't exist
        $openapi = \OpenApi\Generator::scan([
            app_path('Http/Controllers'),
        ]);

        return response()->json($openapi);
    }
}