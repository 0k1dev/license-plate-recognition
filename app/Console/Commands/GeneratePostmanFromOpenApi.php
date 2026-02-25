<?php

namespace App\Console\Commands;

use App\Console\Support\OpenApiToPostmanConverter;
use Illuminate\Console\Command;

class GeneratePostmanFromOpenApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postman:generate 
                            {--source-url= : Public URL of the API to fetch docs (e.g. http://127.0.0.1:8000)} 
                            {--target-url= : Configured Base URL for Postman variables (e.g. http://192.168.1.27:8000)} 
                            {--output= : Filename to save collection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Postman collection from Scramble OpenAPI spec';

    /**
     * Execute the console command.
     */
    public function handle(OpenApiToPostmanConverter $converter)
    {
        $this->info('🔄 Fetching OpenAPI spec from Scramble...');

        $sourceUrl = $this->option('source-url') ?: 'http://127.0.0.1:8000';
        $targetUrl = $this->option('target-url') ?: $sourceUrl;
        $filename = $this->option('output');

        try {
            $converter->generate($sourceUrl, $targetUrl);
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        $this->info('✅ OpenAPI spec fetched successfully');
        $this->info('📦 Converting to Postman collection...');

        // Save to file
        $filePath = $converter->saveToFile($filename);

        $this->newLine();
        $this->info("✅ Postman collection generated successfully!");
        $this->info("📁 File: {$filePath}");
        $this->newLine();

        // Display summary stats
        $stats = $converter->getStats();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Folders', $stats['folders']],
                ['Total Endpoints', $stats['endpoints']],
                ['Base URL Variable', $stats['base_url']],
            ]
        );

        return 0;
    }
}
