<?php

namespace Plugins\TimeTracker\Console;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;

class SeedActivityLogs extends Command
{
    protected $signature = 'timetracker:seed-activity
                            {--users=* : One or more user IDs to seed (comma-separated values allowed).}
                            {--days=14 : Number of past days (including today) to seed.}
                            {--include-weekends : Include Saturdays and Sundays.}
                            {--dry-run : Preview the seed output without inserting any rows.}';

    protected $description = 'Seed sample activity logs for the Time Tracker plugin.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $this->error('The --days option must be a positive integer.');

            return self::FAILURE;
        }

        $includeWeekends = (bool) $this->option('include-weekends');
        $dryRun = (bool) $this->option('dry-run');

        $userIds = $this->parseUserIds();
        $usersQuery = User::query()->orderBy('id');
        if (! empty($userIds)) {
            $usersQuery->whereIn('id', $userIds);
        }

        $users = $usersQuery->get();
        if ($users->isEmpty()) {
            $this->warn('No matching users found. Nothing to seed.');

            return self::SUCCESS;
        }

        $timezone = $this->resolveTimezone();
        $nowUtc = Carbon::now('UTC')->format('Y-m-d H:i:s');

        $totalInserted = 0;
        $perUserCounts = [];

        foreach ($users as $user) {
            $entries = $this->buildEntriesForUser($user->id, $days, $includeWeekends, $timezone, $nowUtc);
            $perUserCounts[$user->id] = count($entries);

            if ($dryRun) {
                $this->line(sprintf(
                    'User %d: would insert %d logs (timezone: %s)',
                    $user->id,
                    count($entries),
                    $timezone
                ));

                if (! empty($entries)) {
                    $preview = collect($entries)->take(3)->map(function ($entry) {
                        return sprintf(
                            '  - [%s] %s (%s)',
                            $entry['timestamp'],
                            $entry['action'],
                            $entry['metadata'] ?? '{}'
                        );
                    });
                    $preview->each(fn ($line) => $this->line($line));
                }

                $totalInserted += count($entries);
                continue;
            }

            foreach (array_chunk($entries, 500) as $chunk) {
                TimeTrackerActivityLog::insert($chunk);
            }

            $this->info(sprintf(
                'Seeded %d logs for user %d.',
                count($entries),
                $user->id
            ));
            $totalInserted += count($entries);
        }

        if ($dryRun) {
            $this->info(sprintf('Dry run complete. %d log rows would be inserted.', $totalInserted));
        } else {
            $this->info(sprintf('Seeding complete. Inserted %d activity log rows.', $totalInserted));
        }

        $this->table(
            ['User ID', 'Log Count'],
            collect($perUserCounts)->map(fn ($count, $id) => [$id, $count])->all()
        );

        return self::SUCCESS;
    }

    /**
     * @return int[]
     */
    private function parseUserIds(): array
    {
        $ids = collect($this->option('users'))
            ->flatMap(function ($value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                return array_filter(array_map('trim', explode(',', (string) $value)), 'strlen');
            })
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn ($value) => $value > 0)
            ->unique()
            ->values();

        return $ids->all();
    }

    private function resolveTimezone(): string
    {
        $timezone = config('app.timezone', 'UTC') ?: 'UTC';

        if (function_exists('get_settings')) {
            try {
                $general = get_settings('general_settings');
                if (is_array($general) && ! empty($general['timezone'])) {
                    $timezone = $general['timezone'];
                }
            } catch (\Throwable $exception) {
                $this->warn(sprintf(
                    'Failed to resolve timezone from settings: %s',
                    $exception->getMessage()
                ));
            }
        }

        return $timezone;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildEntriesForUser(
        int $userId,
        int $days,
        bool $includeWeekends,
        string $timezone,
        string $nowUtc
    ): array {
        $entries = [];
        $seedMarker = [
            'seeded_by' => 'timetracker:seed-activity',
        ];

        $start = Carbon::today($timezone);
        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->subDays($offset);

            if (! $includeWeekends && $day->isWeekend()) {
                continue;
            }

            $dailyEntries = $this->buildDailyEntries($userId, $day, $timezone, $seedMarker, $nowUtc);
            $entries = array_merge($entries, $dailyEntries);
        }

        return $entries;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyEntries(
        int $userId,
        Carbon $day,
        string $timezone,
        array $seedMarker,
        string $nowUtc
    ): array {
        $entries = [];

        $clockIn = $day->copy()->setTimezone($timezone)->setTime(9, 0)->addMinutes(random_int(0, 20));
        $clockOut = $day->copy()->setTimezone($timezone)->setTime(17, 30)->subMinutes(random_int(0, 20));

        if ($clockOut <= $clockIn) {
            $clockOut = $clockIn->copy()->addHours(8);
        }

        $entries[] = $this->makeEntry($userId, 'clock-in', $clockIn, $seedMarker, $nowUtc);

        if (random_int(1, 100) <= 75) {
            $breakStart = $day->copy()->setTimezone($timezone)->setTime(13, 0)->addMinutes(random_int(-10, 15));
            $breakEnd = $breakStart->copy()->addMinutes(random_int(25, 45));

            if ($breakEnd < $clockOut) {
                $entries[] = $this->makeEntry(
                    $userId,
                    'break-start',
                    $breakStart,
                    $seedMarker + ['note' => 'Lunch break (seeded)'],
                    $nowUtc
                );

                $entries[] = $this->makeEntry(
                    $userId,
                    'break-stop',
                    $breakEnd,
                    $seedMarker + ['note' => 'Lunch break ended'],
                    $nowUtc
                );

                $clockIn->addMinutes(0);
            }
        }

        if (random_int(1, 100) <= 50) {
            $idleStart = $day->copy()->setTimezone($timezone)->setTime(15, 30)->addMinutes(random_int(-15, 20));
            $idleEnd = $idleStart->copy()->addMinutes(random_int(5, 20));

            if ($idleEnd < $clockOut) {
                $entries[] = $this->makeEntry(
                    $userId,
                    'idle-start',
                    $idleStart,
                    $seedMarker + ['note' => 'Idle detected (seeded)'],
                    $nowUtc
                );
                $entries[] = $this->makeEntry(
                    $userId,
                    'idle-stop',
                    $idleEnd,
                    $seedMarker + ['note' => 'Idle resolved'],
                    $nowUtc
                );
            }
        }

        $entries[] = $this->makeEntry(
            $userId,
            'clock-out',
            $clockOut,
            $seedMarker + ['note' => 'End of seeded workday'],
            $nowUtc
        );

        usort($entries, static fn ($a, $b) => strcmp($a['timestamp'], $b['timestamp']));

        return $entries;
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    private function makeEntry(
        int $userId,
        string $action,
        Carbon $localTimestamp,
        array $metadata,
        string $nowUtc
    ): array {
        $timestampUtc = $localTimestamp->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');

        return [
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => $timestampUtc,
            'metadata' => ! empty($metadata) ? json_encode($metadata) : null,
            'created_at' => $nowUtc,
            'updated_at' => $nowUtc,
        ];
    }
}

