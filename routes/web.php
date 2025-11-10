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

        // Vérifier MongoDB
        try {
            DB::connection('mongodb')->getDatabase();
            $mongo_status = '✅ MongoDB OK';
        } catch (\Exception $e) {
            $mongo_status = '❌ MongoDB: ' . $e->getMessage();
        }

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
                'mongodb' => $mongo_status,
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

// Health check spécifique MongoDB
Route::get('/health/mongo', function () {
    try {
        $mongo = DB::connection('mongodb')->getDatabase();
        $collections = iterator_to_array($mongo->listCollections());
        $collectionNames = array_map(function($c) { return $c->getName(); }, $collections);

        // Test d'insertion
        $testCollection = $mongo->selectCollection('health_check');
        $result = $testCollection->insertOne([
            'check_type' => 'health_check',
            'timestamp' => now(),
            'status' => 'ok'
        ]);

        return response()->json([
            'status' => 'healthy',
            'mongodb' => [
                'connection' => 'ok',
                'database' => env('MONGODB_DATABASE', 'om_pay_db'),
                'collections' => $collectionNames,
                'test_insertion' => 'ok',
                'inserted_id' => (string)$result->getInsertedId()
            ],
            'timestamp' => now(),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'mongodb' => [
                'connection' => 'failed',
                'error' => $e->getMessage(),
                'uri' => env('MONGODB_URI') ? 'configured' : 'not_configured'
            ],
            'timestamp' => now(),
        ], 500);
    }
});

Route::any('/adminer', function () {
    require_once __DIR__ . '/../vendor/vrana/adminer/adminer-5.4.1.php';
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
