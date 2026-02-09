<?php

declare(strict_types=1);
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a secure API Key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $key = 'bds-' . Str::random(32);

        $this->info('API Key mới của bạn:');
        $this->line('');
        $this->comment($key);
        $this->line('');
        $this->info('Hãy copy key này vào file .env:');
        $this->comment("API_KEY_IOS=$key");
        $this->line('');
    }
}
