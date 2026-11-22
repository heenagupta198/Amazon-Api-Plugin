<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InstrumentImportService
{
    /**
     * @return array{imported: int, mapped: int, skipped: int}
     */
    public function importNiftyConstituents(): array
    {
        $constituents = $this->fetchNiftyConstituents();
        $master = $this->fetchDhanMasterIndex();

        $imported = 0;
        $mapped = 0;
        $skipped = 0;

        foreach ($constituents as $row) {
            $symbol = strtoupper($row['symbol']);
            $securityId = $master[$symbol] ?? null;

            $stock = Stock::query()->updateOrCreate(
                ['symbol' => $symbol],
                [
                    'name' => $row['name'],
                    'isin' => $row['isin'] ?? null,
                    'industry' => $row['industry'] ?? null,
                    'security_id' => $securityId,
                    'is_active' => true,
                ]
            );

            $imported++;
            if ($securityId) {
                $mapped++;
            } else {
                $skipped++;
            }

            if (! $stock->wasRecentlyCreated) {
                // still counts as import update
            }
        }

        return ['imported' => $imported, 'mapped' => $mapped, 'skipped' => $skipped];
    }

    /**
     * @return list<array{name: string, symbol: string, isin: ?string, industry: ?string}>
     */
    private function fetchNiftyConstituents(): array
    {
        $csv = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Nifty50Tracker/1.0)'])
            ->get((string) config('dhan.nse_constituents_csv'))
            ->body();

        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];
        array_shift($lines);

        $out = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line);
            if (count($cols) < 4) {
                continue;
            }
            $out[] = [
                'name' => $cols[0],
                'industry' => $cols[1],
                'symbol' => $cols[2],
                'isin' => $cols[4] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private function fetchDhanMasterIndex(): array
    {
        $csv = Http::timeout(120)->get((string) config('dhan.nse_master_csv'))->body();
        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];
        $header = str_getcsv(array_shift($lines) ?? '');

        $symbolIdx = array_search('SEM_TRADING_SYMBOL', $header, true);
        $idIdx = array_search('SEM_SMST_SECURITY_ID', $header, true);
        $exchIdx = array_search('SEM_EXM_EXCH_ID', $header, true);
        $instrIdx = array_search('SEM_INSTRUMENT_NAME', $header, true);

        $map = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line);
            if ($symbolIdx === false || $idIdx === false || ! isset($cols[$symbolIdx], $cols[$idIdx])) {
                continue;
            }
            if ($exchIdx !== false && ($cols[$exchIdx] ?? '') !== 'NSE') {
                continue;
            }
            if ($instrIdx !== false && ($cols[$instrIdx] ?? '') !== 'EQUITY') {
                continue;
            }

            $symbol = strtoupper(Str::trim($cols[$symbolIdx]));
            $map[$symbol] = (int) $cols[$idIdx];
        }

        return $map;
    }
}