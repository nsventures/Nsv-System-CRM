# Time Tracker Plugin

Tracks time and screenshots from the Electron Desktop App.

## API Endpoints

- `POST /api/plugin/timetracker/track`
- `POST /api/plugin/timetracker/screenshot`

## CLI Seeder

Use the artisan command below to generate sample activity logs for existing users:

<pre><code>php artisan timetracker:seed-activity
</code></pre>

Options:
- `--days=`: Number of days to seed (default `14`, includes today).
- `--users=`: Limit to specific user IDs. Accepts repeats or comma-separated values.
- `--include-weekends`: Include Saturdays and Sundays in the seed range.
- `--dry-run`: Preview the rows that would be created without inserting them.

### Verifying Seeded Data

1. Execute the seeder.
2. Confirm new rows exist:

<pre><code>SELECT user_id, action, timestamp
FROM time_tracker_activity_logs
ORDER BY timestamp DESC
LIMIT 10;
</code></pre>

3. Open the manual time or activity log views to ensure seeded entries appear for the selected users.
