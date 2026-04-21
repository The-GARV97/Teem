<?php

namespace Tests\Unit;

use App\Helpers\WorkingDaysHelper;
use Carbon\Carbon;
use Tests\TestCase;

class WorkingDaysHelperTest extends TestCase
{
    public function test_single_weekday_returns_1(): void
    {
        // Monday to Monday
        $monday = Carbon::parse('2024-01-08'); // a Monday
        $this->assertSame(1, WorkingDaysHelper::count($monday, $monday));
    }

    public function test_full_week_returns_5(): void
    {
        // Monday to Friday
        $start = Carbon::parse('2024-01-08'); // Monday
        $end   = Carbon::parse('2024-01-12'); // Friday
        $this->assertSame(5, WorkingDaysHelper::count($start, $end));
    }

    public function test_weekend_only_returns_0(): void
    {
        // Saturday to Sunday
        $start = Carbon::parse('2024-01-06'); // Saturday
        $end   = Carbon::parse('2024-01-07'); // Sunday
        $this->assertSame(0, WorkingDaysHelper::count($start, $end));
    }

    public function test_start_after_end_returns_0(): void
    {
        $start = Carbon::parse('2024-01-10');
        $end   = Carbon::parse('2024-01-08');
        $this->assertSame(0, WorkingDaysHelper::count($start, $end));
    }

    public function test_cross_month_range(): void
    {
        // Jan 31 (Wednesday) to Feb 1 (Thursday) = 2 working days
        $start = Carbon::parse('2024-01-31');
        $end   = Carbon::parse('2024-02-01');
        $this->assertSame(2, WorkingDaysHelper::count($start, $end));
    }

    public function test_two_weeks_returns_10(): void
    {
        // Monday to Friday of the following week = 10 working days
        $start = Carbon::parse('2024-01-08'); // Monday
        $end   = Carbon::parse('2024-01-19'); // Friday (2 weeks later)
        $this->assertSame(10, WorkingDaysHelper::count($start, $end));
    }
}
