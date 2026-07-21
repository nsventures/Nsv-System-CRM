<?php

namespace App\Services;

use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdayAnniversaryService
{
    /**
     * Get upcoming birthdays for web view
     */
    public function getUpcomingBirthdays(Workspace $workspace, array $filters): array
    {
        $currentDate = today();
        $currentYear = $currentDate->format('Y');
        $upcomingDays = (int)($filters['upcoming_days'] ?? 30);
        $upcomingDate = $currentDate->copy()->addDays($upcomingDays);
        $currentDateString = $currentDate->format('Y-m-d');
        $upcomingDateString = $upcomingDate->format('Y-m-d');
        $order = $filters['order'] ?? 'ASC';

        $users = $workspace->users();
        $clients = $workspace->clients();

        // Apply birthday date filter
        $birthdayWhereRaw = "
            DATE_ADD(DATE_FORMAT(dob, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(dob)
            + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(dob, '%m-%d'), 1, 0) YEAR)
            BETWEEN ? AND ?
            AND DATEDIFF(DATE_ADD(DATE_FORMAT(dob, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(dob)
            + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(dob, '%m-%d'), 1, 0) YEAR), CURRENT_DATE()) <= ?
            AND (
                (YEAR(CURRENT_DATE()) - YEAR(dob) >= 0)
                OR
                (YEAR(CURRENT_DATE()) - YEAR(dob) = 1 AND DATE_FORMAT(CURRENT_DATE(), '%m-%d') <= DATE_FORMAT(dob, '%m-%d'))
            )
        ";

        $users->whereRaw($birthdayWhereRaw, [$currentDateString, $upcomingDateString, $upcomingDays])
            ->orderByRaw("DATEDIFF(DATE_ADD(DATE_FORMAT(dob, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(dob)
            + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(dob, '%m-%d'), 1, 0) YEAR), CURRENT_DATE()) " . $order);

        $clients->whereRaw($birthdayWhereRaw, [$currentDateString, $upcomingDateString, $upcomingDays])
            ->orderByRaw("DATEDIFF(DATE_ADD(DATE_FORMAT(dob, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(dob)
            + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(dob, '%m-%d'), 1, 0) YEAR), CURRENT_DATE()) " . $order);

        $this->applySearchFilter($users, $clients, $filters['search'] ?? null, 'dob');
        $this->applyIdFilters($users, $clients, $filters['user_ids'] ?? null, $filters['client_ids'] ?? null);

        $merged = $this->mergeAndSortByDate($users, $clients, $currentDate, 'dob');
        $total = $this->calculateTotal($users, $clients, $filters['user_ids'] ?? null, $filters['client_ids'] ?? null);

        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 10);
        $paginated = $merged->forPage($page, $limit);

        $formattedResults = $paginated->map(function ($item) use ($currentDate, $currentYear) {
            return $this->formatBirthdayForWeb($item, $currentDate, $currentYear);
        });

        return [
            'rows' => $formattedResults->values(),
            'total' => $total,
        ];
    }

    /**
     * Get upcoming birthdays for API
     */
    public function getUpcomingBirthdaysApi(Workspace $workspace, array $filters): array
    {
        $currentDate = today();
        $currentYear = $currentDate->year;
        $upcomingDays = (int)($filters['upcoming_days'] ?? 30);
        $upcomingDate = $currentDate->copy()->addDays($upcomingDays);
        $currentDateString = $currentDate->format('Y-m-d');
        $upcomingDateString = $upcomingDate->format('Y-m-d');
        $order = $filters['order'] ?? 'ASC';

        $users = $workspace->users();
        $clients = $workspace->clients();

        $birthdayWhereRaw = "
             DATE_ADD(DATE_FORMAT(dob, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(dob)
             + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(dob, '%m-%d'), 1, 0) YEAR)
             BETWEEN ? AND ?
             AND DATEDIFF(DATE_ADD(DATE_FORMAT(dob, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(dob)
             + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(dob, '%m-%d'), 1, 0) YEAR), CURRENT_DATE()) <= ?
         ";

        $bindings = [$currentDateString, $upcomingDateString, $upcomingDays];
        $users->whereRaw($birthdayWhereRaw, $bindings);
        $clients->whereRaw($birthdayWhereRaw, $bindings);

        $this->applySearchFilter($users, $clients, $filters['search'] ?? null, 'dob');
        $this->applyIdFilters($users, $clients, $filters['user_ids'] ?? null, $filters['client_ids'] ?? null);

        $merged = $this->mergeAndSortByDate($users, $clients, $currentDate, 'dob', $order);
        $total = $merged->count();

        if ($merged->isEmpty()) {
            return [
                'error' => false,
                'message' => 'Upcoming birthdays not found.',
                'data' => [],
            ];
        }

        $limit = (int)($filters['limit'] ?? 15);
        $offset = (int)($filters['offset'] ?? 0);
        $paginated = $merged->slice($offset, $limit)->values();

        $formatted = $paginated->map(function ($item) use ($currentDate, $currentYear) {
            return $this->formatBirthdayForApi($item, $currentDate, $currentYear);
        });

        return [
            'error' => false,
            'message' => 'Upcoming birthdays retrieved successfully',
            'total' => $total,
            'data' => $formatted,
        ];
    }

    /**
     * Get upcoming work anniversaries for web view
     */
    public function getUpcomingAnniversaries(Workspace $workspace, array $filters): array
    {
        $currentDate = today();
        $currentYear = $currentDate->year;
        $upcomingDays = (int)($filters['upcoming_days'] ?? 30);
        $upcomingDate = $currentDate->copy()->addDays($upcomingDays);
        $currentDateString = $currentDate->format('Y-m-d');
        $upcomingDateString = $upcomingDate->format('Y-m-d');

        $users = $workspace->users()->select('users.*');
        $clients = $workspace->clients()->select('clients.*');

        $workAnniversarySql = "
            DATE_ADD(DATE_FORMAT(doj, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(doj)
            + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(doj, '%m-%d'), 1, 0) YEAR)
            BETWEEN ? AND ?
            AND DATEDIFF(
                DATE_ADD(DATE_FORMAT(doj, '%Y-%m-%d'),
                INTERVAL YEAR(CURRENT_DATE()) - YEAR(doj)
                + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(doj, '%m-%d'), 1, 0) YEAR),
                CURRENT_DATE()
            ) <= ?
            AND (
                (YEAR(CURRENT_DATE()) - YEAR(doj) >= 0)
                OR
                (YEAR(CURRENT_DATE()) - YEAR(doj) = 1 AND DATE_FORMAT(CURRENT_DATE(), '%m-%d') <= DATE_FORMAT(doj, '%m-%d'))
            )
        ";

        $users->whereRaw($workAnniversarySql, [$currentDateString, $upcomingDateString, $upcomingDays]);
        $clients->whereRaw($workAnniversarySql, [$currentDateString, $upcomingDateString, $upcomingDays]);

        $this->applySearchFilter($users, $clients, $filters['search'] ?? null, 'doj');

        if (!empty($filters['user_ids'])) {
            $users->whereIn('users.id', $filters['user_ids']);
        }
        if (!empty($filters['client_ids'])) {
            $clients->whereIn('clients.id', $filters['client_ids']);
        }

        $usersCollection = $users->distinct('users.id')->get()->map(function ($user) {
            $user->type = 'user';
            return $user;
        });

        $clientsCollection = $clients->distinct('clients.id')->get()->map(function ($client) {
            $client->type = 'client';
            return $client;
        });

        $mergedCollection = $usersCollection->merge($clientsCollection)->unique(function ($item) {
            return $item['type'] . '-' . $item['id'];
        });

        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 10);

        $paginated = $mergedCollection
            ->sortBy(function ($item) use ($currentDate) {
                $dojValue = $item['doj'] ?? $item->doj ?? null;
                if ($dojValue instanceof Carbon) {
                    $anniversaryDate = $dojValue->copy()->startOfDay();
                } else {
                    $anniversaryDate = Carbon::parse($dojValue)->startOfDay();
                }
                $anniversaryDate->year = $currentDate->year;
                if ($anniversaryDate->lt($currentDate)) {
                    $anniversaryDate->year = $currentDate->year + 1;
                }
                return $currentDate->diffInDays($anniversaryDate);
            })
            ->forPage($page, $limit);

        $formattedResults = $paginated->map(function ($item) use ($currentDate, $currentYear) {
            return $this->formatAnniversaryForWeb($item, $currentDate, $currentYear);
        });

        return [
            'rows' => $formattedResults->values(),
            'total' => $mergedCollection->count(),
        ];
    }

    /**
     * Get upcoming work anniversaries for API
     */
    public function getUpcomingAnniversariesApi(Workspace $workspace, array $filters): array
    {
        $currentDate = today();
        $currentYear = $currentDate->year;
        $upcomingDays = (int)($filters['upcoming_days'] ?? 30);
        $upcomingDate = $currentDate->copy()->addDays($upcomingDays);
        $currentDateString = $currentDate->format('Y-m-d');
        $upcomingDateString = $upcomingDate->format('Y-m-d');
        $order = $filters['order'] ?? 'ASC';

        $users = $workspace->users();
        $clients = $workspace->clients();

        $whereRaw = "
             DATE_ADD(DATE_FORMAT(doj, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(doj)
             + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(doj, '%m-%d'), 1, 0) YEAR)
             BETWEEN ? AND ?
             AND DATEDIFF(DATE_ADD(DATE_FORMAT(doj, '%Y-%m-%d'), INTERVAL YEAR(CURRENT_DATE()) - YEAR(doj)
             + IF(DATE_FORMAT(CURRENT_DATE(), '%m-%d') > DATE_FORMAT(doj, '%m-%d'), 1, 0) YEAR), CURRENT_DATE()) <= ?
         ";

        $bindings = [$currentDateString, $upcomingDateString, $upcomingDays];
        $users->whereRaw($whereRaw, $bindings);
        $clients->whereRaw($whereRaw, $bindings);

        $this->applySearchFilter($users, $clients, $filters['search'] ?? null, 'doj');
        $this->applyIdFilters($users, $clients, $filters['user_ids'] ?? null, $filters['client_ids'] ?? null);

        $merged = $this->mergeAndSortByDate($users, $clients, $currentDate, 'doj', $order);
        $total = $merged->count();

        if ($merged->isEmpty()) {
            return [
                'error' => false,
                'message' => 'Upcoming work anniversaries not found.',
                'data' => [],
            ];
        }

        $limit = (int)($filters['limit'] ?? 15);
        $offset = (int)($filters['offset'] ?? 0);
        $paginated = $merged->slice($offset, $limit)->values();

        $formatted = $paginated->map(function ($item) use ($currentDate, $currentYear) {
            return $this->formatAnniversaryForApi($item, $currentDate, $currentYear);
        });

        return [
            'error' => false,
            'message' => 'Upcoming work anniversaries retrieved successfully',
            'total' => $total,
            'data' => $formatted,
        ];
    }

    /**
     * Apply search filter to users and clients queries
     */
    private function applySearchFilter($users, $clients, ?string $search, string $dateField): void
    {
        if (!$search) {
            return;
        }

        $users->where(function ($query) use ($search, $dateField) {
            $query->where('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%")
                ->orWhere('users.id', 'LIKE', "%{$search}%")
                ->orWhere($dateField, 'LIKE', "%{$search}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
        });

        $clients->where(function ($query) use ($search, $dateField) {
            $query->where('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%")
                ->orWhere('clients.id', 'LIKE', "%{$search}%")
                ->orWhere($dateField, 'LIKE', "%{$search}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * Apply ID filters to users and clients queries
     */
    private function applyIdFilters($users, $clients, $userIds, $clientIds): void
    {
        if (!empty($userIds) && !empty($clientIds)) {
            $users->whereIn('users.id', $userIds);
            $clients->whereIn('clients.id', $clientIds);
        } elseif (!empty($userIds)) {
            $users->whereIn('users.id', $userIds);
            $clients->whereIn('clients.id', []);
        } elseif (!empty($clientIds)) {
            $clients->whereIn('clients.id', $clientIds);
            $users->whereIn('users.id', []);
        }
    }

    /**
     * Merge and sort users and clients by date
     */
    private function mergeAndSortByDate($users, $clients, Carbon $currentDate, string $dateField, string $order = 'ASC'): Collection
    {
        $usersCollection = $users->get()->map(function ($user) {
            $user->type = 'user';
            return $user;
        });

        $clientsCollection = $clients->get()->map(function ($client) {
            $client->type = 'client';
            return $client;
        });

        $merged = $usersCollection->merge($clientsCollection);

        return $merged->sortBy(function ($item) use ($currentDate, $dateField) {
            $dateValue = $item->{$dateField} ?? $item[$dateField] ?? null;
            if ($dateValue instanceof Carbon) {
                $eventDate = $dateValue->copy()->startOfDay();
            } else {
                $eventDate = Carbon::parse($dateValue)->startOfDay();
            }
            $eventDate->year = $currentDate->year;
            if ($eventDate->lt($currentDate)) {
                $eventDate->year++;
            }
            return $currentDate->diffInDays($eventDate);
        }, SORT_REGULAR, $order === 'DESC');
    }

    /**
     * Calculate total count based on filters
     */
    private function calculateTotal($users, $clients, $userIds, $clientIds): int
    {
        if (empty($userIds) && empty($clientIds)) {
            return $users->count() + $clients->count();
        }
        return max($users->count(), $clients->count());
    }

    /**
     * Format birthday data for web view (with HTML badges)
     */
    private function formatBirthdayForWeb($item, Carbon $currentDate, int $currentYear): array
    {
        $dobValue = $item['dob'] ?? $item->dob ?? null;
        if ($dobValue instanceof Carbon) {
            $birthdayDate = $dobValue->copy()->startOfDay();
        } else {
            $birthdayDate = Carbon::parse($dobValue)->startOfDay();
        }

        $birthdayDateYear = $birthdayDate->year;
        $yearDifference = $currentYear - $birthdayDateYear;
        $ordinalSuffix = getOrdinalSuffix($yearDifference);
        $birthdayDate->year = $currentDate->year;
        if ($birthdayDate->lt($currentDate)) {
            $birthdayDate->year = $currentDate->year + 1;
        }
        $daysLeft = $currentDate->diffInDays($birthdayDate);

        $emoji = '';
        $label = '';
        if ($daysLeft === 0) {
            $emoji = ' 🥳';
            $label = '<span class="badge bg-label-success mt-2">' . $yearDifference . '<sup>' . $ordinalSuffix . '</sup> ' . get_label('birthday', 'Birthday') . ' ' . get_label('today', 'Today') . '</span>' . $emoji;
        } elseif ($daysLeft === 1) {
            $label = '<span class="badge bg-label-warning mt-2">' . $yearDifference . '<sup>' . $ordinalSuffix . '</sup> ' . get_label('birthday', 'Birthday') . ' ' . get_label('tomorrow', 'Tomorrow') . '</span>';
        } elseif ($daysLeft === 2) {
            $label = '<span class="badge bg-label-success mt-2">' . $yearDifference . '<sup>' . $ordinalSuffix . '</sup> ' . get_label('birthday', 'Birthday') . ' ' . get_label('day_after_tomorrow', 'Day After Tomorrow') . '</span>';
        }

        $type = $item['type'] ?? 'user';
        $formattedMember = $type === 'user' ? formatUserHtml((object)$item) : formatClientHtml((object)$item);
        $typeLabel = $type === 'user'
            ? '<span class="badge bg-label-info">' . get_label('user', 'User') . '</span>'
            : '<span class="badge bg-label-success">' . get_label('client', 'Client') . '</span>';

        return [
            'id' => $item['id'],
            'member' => $formattedMember,
            'age' => $currentDate->diffInYears($birthdayDate),
            'days_left' => $daysLeft,
            'dob' => $birthdayDate->format('D, M d, Y') . ' ' . $label,
            'type' => $typeLabel,
        ];
    }

    /**
     * Format birthday data for API (plain text)
     */
    private function formatBirthdayForApi($item, Carbon $currentDate, int $currentYear): array
    {
        $dobValue = $item->dob ?? $item['dob'] ?? null;
        if ($dobValue instanceof Carbon) {
            $birthdayDate = $dobValue->copy()->startOfDay();
        } else {
            $birthdayDate = Carbon::parse($dobValue)->startOfDay();
        }

        $yearDifference = $currentYear - $birthdayDate->year;
        $ordinalSuffix = getOrdinalSuffix($yearDifference);
        $birthdayDate->year = $currentDate->year;
        if ($birthdayDate->lt($currentDate)) {
            $birthdayDate->year++;
        }
        $daysLeft = $currentDate->diffInDays($birthdayDate);

        $emoji = '';
        $label = '';
        if ($daysLeft === 0) {
            $emoji = ' 🥳';
            $label = "{$yearDifference}{$ordinalSuffix} Birthday Today{$emoji}";
        } elseif ($daysLeft === 1) {
            $label = "{$yearDifference}{$ordinalSuffix} Birthday Tomorrow";
        } elseif ($daysLeft === 2) {
            $label = "{$yearDifference}{$ordinalSuffix} Birthday Day After Tomorrow";
        } else {
            $label = "{$yearDifference}{$ordinalSuffix} Birthday in {$daysLeft} days";
        }

        return [
            'id' => $item->id,
            'member' => $item->first_name . ' ' . $item->last_name,
            'photo' => $item->photo ? asset('storage/' . $item->photo) : asset('storage/photos/no-image.jpg'),
            'birthday_count' => $yearDifference,
            'days_left' => $daysLeft,
            'dob' => $birthdayDate->format('D, M d, Y'),
            'type' => $item->type,
            'label' => $label,
        ];
    }

    /**
     * Format anniversary data for web view (with HTML badges)
     */
    private function formatAnniversaryForWeb($item, Carbon $currentDate, int $currentYear): array
    {
        $dojValue = $item['doj'] ?? $item->doj ?? null;
        if ($dojValue instanceof Carbon) {
            $anniversaryDate = $dojValue->copy()->startOfDay();
        } else {
            $anniversaryDate = Carbon::parse($dojValue)->startOfDay();
        }

        $yearDifference = $currentYear - $anniversaryDate->year;
        $ordinalSuffix = getOrdinalSuffix($yearDifference);
        $anniversaryDate->year = $currentDate->year;
        if ($anniversaryDate->lt($currentDate)) {
            $anniversaryDate->year = $currentDate->year + 1;
        }
        $daysLeft = $currentDate->diffInDays($anniversaryDate);

        $label = '';
        if ($daysLeft === 0) {
            $label = '<span class="badge bg-label-success mt-2">' . $yearDifference . '<sup>' . $ordinalSuffix . '</sup> ' . get_label('work_anniversary', 'Work Anniversary') . ' ' . get_label('today', 'Today') . ' 🥳</span>';
        } elseif ($daysLeft === 1) {
            $label = '<span class="badge bg-label-warning mt-2">' . $yearDifference . '<sup>' . $ordinalSuffix . '</sup> ' . get_label('work_anniversary', 'Work Anniversary') . ' ' . get_label('tomorrow', 'Tomorrow') . '</span>';
        } elseif ($daysLeft === 2) {
            $label = '<span class="badge bg-label-success mt-2">' . $yearDifference . '<sup>' . $ordinalSuffix . '</sup> ' . get_label('work_anniversary', 'Work Anniversary') . ' ' . get_label('day_after_tomorrow', 'Day After Tomorrow') . '</span>';
        }

        return [
            'id' => $item['id'],
            'member' => $item['type'] === 'user' ? formatUserHtml((object)$item) : formatClientHtml((object)$item),
            'days_left' => $daysLeft,
            'wa_date' => $anniversaryDate->format('D, M d, Y') . ' ' . $label,
            'type' => $item['type'] === 'user'
                ? '<span class="badge bg-label-success">' . get_label('user', 'User') . '</span>'
                : '<span class="badge bg-label-info">' . get_label('client', 'Client') . '</span>',
        ];
    }

    /**
     * Format anniversary data for API (plain text)
     */
    private function formatAnniversaryForApi($item, Carbon $currentDate, int $currentYear): array
    {
        $dojValue = $item->doj ?? $item['doj'] ?? null;
        if ($dojValue instanceof Carbon) {
            $anniversaryDate = $dojValue->copy()->startOfDay();
        } else {
            $anniversaryDate = Carbon::parse($dojValue)->startOfDay();
        }

        $yearDifference = $currentYear - $anniversaryDate->year;
        $ordinalSuffix = getOrdinalSuffix($yearDifference);
        $anniversaryDate->year = $currentDate->year;
        if ($anniversaryDate->lt($currentDate)) {
            $anniversaryDate->year++;
        }
        $daysLeft = $currentDate->diffInDays($anniversaryDate);

        $emoji = '';
        $label = '';
        if ($daysLeft === 0) {
            $emoji = ' 🎉';
            $label = "{$yearDifference}{$ordinalSuffix} Work Anniversary Today{$emoji}";
        } elseif ($daysLeft === 1) {
            $label = "{$yearDifference}{$ordinalSuffix} Work Anniversary Tomorrow";
        } elseif ($daysLeft === 2) {
            $label = "{$yearDifference}{$ordinalSuffix} Work Anniversary Day After Tomorrow";
        } else {
            $label = "{$yearDifference}{$ordinalSuffix} Work Anniversary in {$daysLeft} days";
        }

        return [
            'id' => $item->id,
            'member' => $item->first_name . ' ' . $item->last_name,
            'photo' => $item->photo ? asset('storage/' . $item->photo) : asset('storage/photos/no-image.jpg'),
            'anniversary_count' => $yearDifference,
            'days_left' => $daysLeft,
            'doj' => $anniversaryDate->format('D, M d, Y'),
            'label' => $label,
            'type' => $item->type,
        ];
    }
}

