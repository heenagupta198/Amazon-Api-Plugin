<?php

namespace Tests\Unit;

use App\Services\Analytics\CandleMath;
use PHPUnit\Framework\TestCase;

class CandleMathTest extends TestCase
{
    public function test_vwap_calculation(): void
    {
        $candles = [
            'high' => [110.0, 120.0],
            'low' => [90.0, 100.0],
            'close' => [100.0, 110.0],
            'volume' => [1000, 1000],
        ];

        $vwap = CandleMath::vwap($candles);
        $this->assertNotNull($vwap);
        $this->assertGreaterThan(100, $vwap);
    }
}