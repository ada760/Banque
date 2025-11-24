<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Health check route
Route::get('/health', function () {
    try {
        // Vérifier PostgreSQL
        DB::connection('pgsql')->getPdo();
        $db_status = '✅ PostgreSQL OK';

        // Vérifier Redis
        try {
            Cache::store('redis')->get('health_check');
            $redis_status = '✅ Redis OK';
        } catch (\Exception $e) {
            $redis_status = '❌ Redis: ' . $e->getMessage();
        }

        // Vérifier Queue
        try {
            $queue_size = Queue::size();
            $queue_status = '✅ Queue OK (' . $queue_size . ' jobs)';
        } catch (\Exception $e) {
            $queue_status = '❌ Queue: ' . $e->getMessage();
        }

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now(),
            'services' => [
                'database' => $db_status,
                'redis' => $redis_status,
                'queue' => $queue_status,
            ],
            'version' => config('app.version', '1.0.0'),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => now(),
        ], 500);
    }
});


Route::any('/adminer', function () {
    require_once __DIR__ . '/../vendor/vrana/adminer/adminer-5.4.1.php';
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
