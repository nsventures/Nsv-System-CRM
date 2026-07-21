<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveQueryService
{
    public function getMembersOnLeave(Workspace $workspace, User $authUser, array $filters): array
    {
        $search       = $filters['search'] ?? null;
        $sort         = $filters['sort'] ?? 'from_date';
        $order        = $filters['order'] ?? 'ASC';
        $upcomingDays = (int)($filters['upcoming_days'] ?? 30);
        $userIds      = $filters['user_ids'] ?? [];
        $limit        = (int)($filters['limit'] ?? 10);
        $page         = (int)($filters['page'] ?? 1);

        $currentDate  = today();
        $upcomingDate = $currentDate->copy()->addDays($upcomingDays);
        $timezone     = config('app.timezone');

        $leaveUsers = DB::table('leave_requests')
            ->leftJoin('users', 'leave_requests.user_id', '=', 'users.id')
            ->leftJoin('leave_request_visibility', 'leave_requests.id', '=', 'leave_request_visibility.leave_request_id')
            ->select(
                'users.id as user_id',
                'first_name',
                'last_name',
                DB::raw('MIN(from_date) as from_date'),
                DB::raw('MAX(to_date) as to_date'),
                DB::raw('GROUP_CONCAT(from_time) as from_times'),
                DB::raw('GROUP_CONCAT(to_time) as to_times')
            )
            ->where('leave_requests.status', 'approved')
            ->where('workspace_id', $workspace->id)
            ->where(function ($q) use ($currentDate, $upcomingDate) {
                $q->where('from_date', '<=', $upcomingDate)
                    ->where('to_date', '>=', $currentDate);
            });

        if (!is_admin_or_leave_editor()) {
            $leaveUsers->where(function ($query) use ($authUser) {
                $query->where('leave_requests.user_id', '=', $authUser->id)
                    ->orWhere('leave_request_visibility.user_id', '=', $authUser->id)
                    ->orWhere('leave_requests.visible_to_all', '=', 1);
            });
        }

        if (!empty($search)) {
            $leaveUsers->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('users.id', 'LIKE', "%$search%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
            });
        }

        if (!empty($userIds)) {
            $leaveUsers->whereIn('leave_requests.user_id', (array)$userIds);
        }

        $leaveUsers = $leaveUsers->groupBy('leave_requests.user_id')
            ->orderBy($sort, $order);

        $results = collect($leaveUsers->get())->forPage($page, $limit);

        $formattedResults = $results->map(function ($user) use ($currentDate, $timezone) {
            $fromDate = Carbon::parse($user->from_date);
            $toDate   = Carbon::parse($user->to_date);
            $daysLeft = max(0, $currentDate->diffInDays($fromDate));
            $currentDateTime = Carbon::now($timezone);
            $hasPartial = str_contains($user->from_times, ':') && str_contains($user->to_times, ':');

            $label = '';
            if ($daysLeft === 0 && $hasPartial) {
                $label = ' <span class="badge bg-label-info">' . get_label('on_partial_leave', 'On Partial Leave') . '</span>';
            } elseif ($daysLeft === 0 && !$hasPartial) {
                $label = ' <span class="badge bg-label-success">' . get_label('on_leave', 'On Leave') . '</span>';
            } elseif ($daysLeft === 1) {
                $label = ' <span class="badge bg-label-success">' . get_label('on_leave_tomorrow', 'On Leave From Tomorrow') . '</span>';
            } elseif ($daysLeft === 2) {
                $label = ' <span class="badge bg-label-warning">' . get_label('on_leave_day_after_tomorow', 'On Leave From Day After Tomorrow') . '</span>';
            }

            $duration = $hasPartial
                ? get_label('partial', 'Partial')
                : $fromDate->diffInDays($toDate) + 1 . ' ' . get_label('days', 'days');

            return [
                'id'        => $user->user_id,
                'member'    => formatUserHtml(User::find($user->user_id)),
                'from_date' => $fromDate->format('D, M d, Y'),
                'to_date'   => $toDate->format('D, M d, Y'),
                'type'      => $hasPartial
                    ? '<span class="badge bg-label-info">' . get_label('partial', 'Partial') . '</span>'
                    : '<span class="badge bg-label-success">' . get_label('full', 'Full') . '</span>',
                'duration'  => $duration,
                'days_left' => $daysLeft,
                'label'     => $label,
            ];
        });

        // Keep total logic same as before (distinct users on leave)
        $totalDistinct = DB::table('leave_requests')
            ->where('status', 'approved')
            ->where('workspace_id', $workspace->id)
            ->where(function ($q) use ($currentDate, $upcomingDays) {
                $upcomingDate = $currentDate->copy()->addDays($upcomingDays);
                $q->where('from_date', '<=', $upcomingDate)
                    ->where('to_date', '>=', $currentDate);
            })
            ->distinct('user_id')
            ->count('user_id');

        return [
            'rows'  => $formattedResults->values(),
            'total' => $totalDistinct,
        ];
    }

    public function getMembersOnLeaveApi(Workspace $workspace, User $authUser, array $filters): array
    {
        $search       = $filters['search'] ?? null;
        $sort         = $filters['sort'] ?? 'from_date';
        $order        = $filters['order'] ?? 'ASC';
        $upcomingDays = (int)($filters['upcoming_days'] ?? 30);
        $userIds      = $filters['user_ids'] ?? [];
        $limit        = (int)($filters['limit'] ?? 15);
        $offset       = (int)($filters['offset'] ?? 0);

        $currentDate  = today();
        $upcomingDate = $currentDate->copy()->addDays($upcomingDays);

        $leaveUsers = DB::table('leave_requests')
            ->selectRaw('*, leave_requests.user_id as UserId')
            ->leftJoin('users', 'leave_requests.user_id', '=', 'users.id')
            ->leftJoin('leave_request_visibility', 'leave_requests.id', '=', 'leave_request_visibility.leave_request_id')
            ->where(function ($leaveUsers) use ($currentDate, $upcomingDate) {
                $leaveUsers->where('from_date', '<=', $upcomingDate)
                    ->where('to_date', '>=', $currentDate);
            })
            ->where('leave_requests.status', '=', 'approved')
            ->where('workspace_id', '=', $workspace->id);

        if (!is_admin_or_leave_editor()) {
            $leaveUsers->where(function ($query) use ($authUser) {
                $query->where('leave_requests.user_id', '=', $authUser->id)
                    ->orWhere('leave_request_visibility.user_id', '=', $authUser->id)
                    ->orWhere('leave_requests.visible_to_all', '=', 1);
            });
        }

        if (!empty($search)) {
            $leaveUsers->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('users.id', 'LIKE', "%$search%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
            });
        }

        if (!empty($userIds)) {
            $leaveUsers->whereIn('leave_requests.user_id', (array)$userIds);
        }

        $total = $leaveUsers->count();
        if ($total === 0) {
            return [
                'error'   => false,
                'message' => 'Members on leave not found',
                'total'   => 0,
                'data'    => [],
            ];
        }

        $data = $leaveUsers->orderBy($sort, $order)
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($user) use ($currentDate) {
                $fromDate = Carbon::createFromFormat('Y-m-d', $user->from_date);
                $fromDate->year = $currentDate->year;
                $daysLeft = $currentDate->diffInDays($fromDate);

                if ($fromDate->lt($currentDate)) {
                    $daysLeft = 0;
                }

                $fromDate = Carbon::parse($user->from_date);
                $toDate   = Carbon::parse($user->to_date);

                if ($user->from_time && $user->to_time) {
                    $duration = 0;
                    while ($fromDate->lessThanOrEqualTo($toDate)) {
                        $fromDateTime = Carbon::parse($fromDate->toDateString() . ' ' . $user->from_time);
                        $toDateTime   = Carbon::parse($fromDate->toDateString() . ' ' . $user->to_time);
                        $duration    += $fromDateTime->diffInMinutes($toDateTime) / 60;
                        $fromDate->addDay();
                    }
                } else {
                    $duration = $fromDate->diffInDays($toDate) + 1;
                }

                return [
                    'id'        => $user->UserId,
                    'member'    => $user->first_name . ' ' . $user->last_name,
                    'photo'     => $user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg'),
                    'from_date' => format_date($user->from_date, to_format: 'Y-m-d'),
                    'from_time' => $user->from_time ? Carbon::parse($user->from_time)->format('h:i A') : '',
                    'to_date'   => format_date($user->to_date, to_format: 'Y-m-d'),
                    'to_time'   => $user->to_time ? Carbon::parse($user->to_time)->format('h:i A') : '',
                    'type'      => $user->from_time && $user->to_time ? get_label('partial', 'Partial') : get_label('full', 'Full'),
                    'duration'  => $user->from_time && $user->to_time
                        ? $duration . ' hour' . ($duration > 1 ? 's' : '')
                        : $duration . ' day' . ($duration > 1 ? 's' : ''),
                    'days_left' => $daysLeft,
                ];
            });

        return [
            'error'   => false,
            'message' => 'Members on leave retrieved successfully',
            'total'   => $total,
            'data'    => $data,
        ];
    }
}

