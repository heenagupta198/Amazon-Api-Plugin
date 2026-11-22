<?php

namespace Tests\Unit;

use App\Services\ContributionService;
use PHPUnit\Framework\TestCase;

class ContributionServiceTest extends TestCase
{
    public function test_hdfc_bank_sample_from_spec(): void
    {
        $service = new ContributionService;
        $points = $service->calculatePointContribution(26000, 11, 1);

        $this->assertEqualsWithDelta(28.6, $points, 0.01);
    }

    public function test_sbi_negative_sample_from_spec(): void
    {
        $service = new ContributionService;
        $points = $service->calculatePointContribution(26000, 3.5, -1);

        $this->assertEqualsWithDelta(-9.1, $points, 0.01);
    }
}