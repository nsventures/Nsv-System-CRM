<?php

namespace App\Services;

use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarEventService
{
    /**
     * Build birthday events for users and clients in the given date range.
     *
     * Mirrors the logic previously inside HomeController::upcoming_birthdays_calendar().
     *
     * @return array<int,array<string,mixed>>
     */
    public function getBirthdayEvents(Workspace $workspace, Carbon $startDate, Carbon $endDate): array
    {
        $users = $workspace->users()->get();
        $clients = $workspace->clients()->get();
        $currentDate = today();

        $userBirthdays = $this->calculateBirthdays($users, 'user', '#007bff', $startDate, $endDate, $currentDate);
        $clientBirthdays = $this->calculateBirthdays($clients, 'client', '#17a2b8', $startDate, $endDate, $currentDate);

        return array_merge($userBirthdays, $clientBirthdays);
    }

    /**
     * Build work anniversary events for users and clients in the given date range.
     *
     * Mirrors the logic previously inside HomeController::upcoming_work_anniversaries_calendar().
     *
     * @return array<int,array<string,mixed>>
     */
    public function getWorkAnniversaryEvents(Workspace $workspace, Carbon $startDate, Carbon $endDate): array
    {
        $users = $workspace->users()->get();
        $clients = $workspace->clients()->get();
        $currentDate = today();

        $userEvents = $this->calculateWorkAnniversaries($users, 'user', '#007bff', $startDate, $endDate, $currentDate);
        $clientEvents = $this->calculateWorkAnniversaries($clients, 'client', '#17a2b8', $startDate, $endDate, $currentDate);

        return array_merge($userEvents, $clientEvents);
    }

    /**
     * Build leave events for the calendar within the given date range.
     *
     * Mirrors the logic previously inside HomeController::members_on_leave_calendar().
     *
     * @return array<int,array<string,mixed>>
     */
    public function getLeaveEvents(Workspace $workspace, Carbon $startDate, Carbon $endDate, bool $isAdminOrLeaveEditor, int $currentUserId): array
    {
        $leaveRequests = DB::table('leave_requests')
            ->selectRaw('*, leave_requests.user_id as UserId')
            ->leftJoin('users', 'leave_requests.user_id', '=', 'users.id')
            ->leftJoin('leave_request_visibility', 'leave_requests.id', '=', 'leave_request_visibility.leave_request_id')
            ->where('leave_requests.status', '=', 'approved')
            ->where('workspace_id', '=', $workspace->id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('from_date', [$startDate, $endDate])
                    ->orWhereBetween('to_date', [$startDate, $endDate])
                    ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                        $subQuery->where('from_date', '<=', $startDate)
                            ->where('to_date', '>=', $endDate);
                    });
            });

        if (!$isAdminOrLeaveEditor) {
            $leaveRequests->where(function ($query) use ($currentUserId) {
                $query->where('leave_requests.user_id', '=', $currentUserId)
                    ->orWhere('leave_request_visibility.user_id', '=', $currentUserId);
            });
        }

        $timeFormat = get_php_date_time_format(true);
        $timeFormat = str_replace(':s', '', $timeFormat);

        return $leaveRequests->get()->map(function ($leave) {
            $title = $leave->first_name . ' ' . $leave->last_name;

            if ($leave->from_time && $leave->to_time) {
                $formattedStartDateTime = format_date($leave->from_date . ' ' . $leave->from_time, true, null, null, false);
                $formattedEndDateTime = format_date($leave->to_date . ' ' . $leave->to_time, true, null, null, false);
                $title .= ' : ' . $formattedStartDateTime . ' ' . get_label('to', 'to') . ' ' . $formattedEndDateTime;
                $backgroundColor = '#02C5EE';
            } else {
                $title .= ' : ' . format_date($leave->from_date);
                if ($leave->to_date != $leave->from_date) {
                    $title .= ' ' . get_label('to', 'to') . ' ' . format_date($leave->to_date);
                }
                $backgroundColor = '#007bff';
            }

            return [
                'userId' => $leave->UserId,
                'title' => $title,
                'start' => $leave->from_date,
                'end' => $leave->to_date,
                'startTime' => $leave->from_time,
                'endTime' => $leave->to_time,
                'backgroundColor' => $backgroundColor,
                'borderColor' => $backgroundColor,
                'textColor' => '#ffffff',
            ];
        })->all();
    }

    /**
     * Internal helper to calculate birthday events for a set of entities.
     *
     * @param  \Illuminate\Support\Collection<int,mixed>  $entities
     * @return array<int,array<string,mixed>>
     */
    protected function calculateBirthdays($entities, string $type, string $color, Carbon $startDate, Carbon $endDate, Carbon $currentDate): array
    {
        $entityEvents = [];

        foreach ($entities as $entity) {
            if (empty($entity->dob)) {
                continue;
            }

            $dobValue = $entity->dob ?? $entity['dob'] ?? null;

            try {
                if ($dobValue instanceof Carbon) {
                    $birthday = $dobValue->copy()->startOfDay();
                } else {
                    $birthday = Carbon::parse(trim($dobValue))->startOfDay();
                }
            } catch (\Exception $e) {
                continue;
            }

            $birthdayDateYear = $birthday->year;

            $birthdayThisYear = $birthday->copy()
                ->year($startDate->year)
                ->month($birthday->month)
                ->day($birthday->day);

            if ($birthdayThisYear->lt($startDate)) {
                $birthdayThisYear->addYear();
            }

            while ($birthdayThisYear->lte($endDate)) {
                $age = $birthdayThisYear->year - $birthdayDateYear;

                if ($age >= 1) {
                    $ordinalSuffix = getOrdinalSuffix($age);
                    $title = $entity->first_name . ' ' . $entity->last_name . get_label('s', '\'s ') . ' ' . $age . ' ' . $ordinalSuffix . ' ' . get_label('birthday', 'Birthday');
                } elseif ($age === 0) {
                    $title = $entity->first_name . ' ' . $entity->last_name . get_label('s', '\'s ') . get_label('birth', 'Birth');
                } else {
                    $birthdayThisYear->addYear();
                    continue;
                }

                $entityEvents[] = [
                    'userId' => $entity->id,
                    'type' => $type,
                    'title' => $title,
                    'start' => $birthdayThisYear->format('Y-m-d'),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#ffffff',
                ];

                $birthdayThisYear->addYear();
            }
        }

        return $entityEvents;
    }

    /**
     * Internal helper to calculate work anniversary events.
     *
     * @param  \Illuminate\Support\Collection<int,mixed>  $entities
     * @return array<int,array<string,mixed>>
     */
    protected function calculateWorkAnniversaries($entities, string $type, string $color, Carbon $startDate, Carbon $endDate, Carbon $currentDate): array
    {
        $entityEvents = [];

        foreach ($entities as $entity) {
            if (empty($entity->doj)) {
                continue;
            }

            $dojValue = $entity->doj ?? $entity['doj'] ?? null;

            if ($dojValue instanceof Carbon) {
                $doj = $dojValue->copy()->startOfDay();
            } else {
                $doj = Carbon::parse($dojValue)->startOfDay();
            }

            $dojYear = $doj->year;
            $waDateThisYear = $doj->copy()->year($currentDate->year);

            if ($waDateThisYear->year <= $doj->year) {
                $waDateThisYear->year = $doj->year + 1;
            }

            while ($waDateThisYear->lte($endDate)) {
                if ($waDateThisYear->gte($startDate)) {
                    $yearsOfService = $waDateThisYear->year - $dojYear;
                    $ordinalSuffix = getOrdinalSuffix($yearsOfService);

                    $entityEvents[] = [
                        'userId' => $entity->id,
                        'type' => $type,
                        'title' => $entity->first_name . ' ' . $entity->last_name . get_label('s', '\'s ') . ' ' . $yearsOfService . ' ' . $ordinalSuffix . ' ' . get_label('work_anniversary', 'Work Anniversary'),
                        'start' => $waDateThisYear->format('Y-m-d'),
                        'backgroundColor' => $color,
                        'borderColor' => $color,
                        'textColor' => '#ffffff',
                    ];
                }

                $waDateThisYear->addYear();
            }
        }

        return $entityEvents;
    }
}


