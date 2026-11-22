<?php

namespace App\Console\Commands;

use App\Services\DhanApiService;
use Illuminate\Console\Command;

class DhanConnectionTest extends Command
{
    protected $signature = 'dhan:test';

    protected $description = 'Test DhanHQ API credentials via profile endpoint';

    public function handle(DhanApiService $dhan): int
    {
        $result = $dhan->connectionTest();
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return ($result['connected'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}