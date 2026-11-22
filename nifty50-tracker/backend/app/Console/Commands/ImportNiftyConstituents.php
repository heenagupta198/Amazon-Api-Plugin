<?php

namespace App\Console\Commands;

use App\Services\InstrumentImportService;
use App\Services\WeightageService;
use Illuminate\Console\Command;

class ImportNiftyConstituents extends Command
{
    protected $signature = 'nifty:import-constituents';

    protected $description = 'Import Nifty 50 symbols from NSE and map Dhan security IDs';

    public function handle(InstrumentImportService $import, WeightageService $weights): int
    {
        $result = $import->importNiftyConstituents();
        $weights->fillMissingWeights();

        $this->info("Imported: {$result['imported']}, mapped: {$result['mapped']}, missing IDs: {$result['skipped']}");

        return self::SUCCESS;
    }
}