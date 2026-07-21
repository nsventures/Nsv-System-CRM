<?php

namespace App\Services;

use App\Models\UserLeaveBalance;
use Carbon\Carbon;

class LeaveService
{
    public function calculateLeaveDays($fromDate, $toDate, $fromTime = null, $toTime = null)
    {
        $fromDate = Carbon::parse($fromDate);
        $toDate = Carbon::parse($toDate);

        if ($fromTime && $toTime) {
            $duration = 0;
            while ($fromDate->lessThanOrEqualTo($toDate)) {
                $fromDateTime = Carbon::parse($fromDate->toDateString() . ' ' . $fromTime);
                $toDateTime = Carbon::parse($fromDate->toDateString() . ' ' . $toTime);
                $duration += $fromDateTime->diffInMinutes($toDateTime) / 60;
                $fromDate->addDay();
            }
            return $duration < 8 ? 0.5 : round($duration / 8, 2);
        } else {
            return $fromDate->diffInDays($toDate) + 1;
        }
    }

    public function getUserLeaveBalance($userId, $workspaceId, $year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }

        $balance = UserLeaveBalance::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            $totalAnnualLeaves = get_settings('general_settings')['total_paid_leaves_per_year'] ?? 0;
            return [
                'total_annual_leaves' => $totalAnnualLeaves,
                'used_paid_leaves' => 0,
                'remaining_paid_leaves' => $totalAnnualLeaves,
            ];
        }

        return [
            'id' => $balance->id,
            'total_annual_leaves' => $balance->total_annual_leaves,
            'used_paid_leaves' => $balance->used_paid_leaves,
            'remaining_paid_leaves' => $balance->remaining_paid_leaves,
        ];
    }

    public function getCurrentCompanyYear()
    {
        $settings = get_settings('general_settings');
        $startMonth = $settings['company_year_start_month'] ?? 1;
        $startDay = $settings['company_year_start_day'] ?? 1;

        $today = Carbon::now();
        $currentYear = $today->year;
        $companyYearStart = Carbon::create($currentYear, $startMonth, $startDay);

        if ($today->lt($companyYearStart)) {
            return $currentYear - 1;
        }

        return $currentYear;
    }

    public function getCompanyYearDates($companyYear = null)
    {
        if ($companyYear === null) {
            $companyYear = $this->getCurrentCompanyYear();
        }

        $settings = get_settings('general_settings');
        $startMonth = $settings['company_year_start_month'] ?? 1;
        $startDay = $settings['company_year_start_day'] ?? 1;

        $start = Carbon::create($companyYear, $startMonth, $startDay)->startOfDay();
        $end = $start->copy()->addYear()->subDay()->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'year' => $companyYear,
        ];
    }

    public function formatCompanyYear($companyYear = null, $detailed = false)
    {
        $dates = $this->getCompanyYearDates($companyYear);

        if ($detailed) {
            return $dates['start']->format('M Y') . ' - ' . $dates['end']->format('M Y');
        }

        return $dates['start']->year . '-' . $dates['end']->year;
    }
}
