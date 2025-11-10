<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class GenerateSwaggerDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Swagger/OpenAPI documentation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating Swagger documentation...');

        $openapi = Generator::scan([
            app_path('Http/Controllers'),
        ]);

        $json = $openapi->toJson();

        // Save to storage/app/api-docs/api-docs.json
        $path = storage_path('app/api-docs');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents($path . '/api-docs.json', $json);

        $this->info('Swagger documentation generated successfully!');
        $this->info('File saved to: ' . $path . '/api-docs.json');
        $this->info('Access Swagger UI at: /api/documentation');

        return Command::SUCCESS;
    }
}
