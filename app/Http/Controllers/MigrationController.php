<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MigrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('customRole:admin');
    }

    /**
     * Display the migration dashboard
     */
    public function index()
    {
        try {
            $status = $this->getMigrationStatus();
            $pendingMigrations = $status['pending'] ?? [];
            $ranMigrations = $status['ran'] ?? [];
            $totalMigrations = $status['total'] ?? 0;
            $pendingCount = count($pendingMigrations);
            $ranCount = count($ranMigrations);

            return view('migrations.index', compact(
                'pendingMigrations',
                'ranMigrations',
                'totalMigrations',
                'pendingCount',
                'ranCount',
                'status'
            ));
        } catch (Exception $e) {
            Log::error('Migration dashboard failed', ['error' => $e->getMessage()]);
            return redirect('/home')->with('error', get_label('something_went_wrong', 'Something went wrong.'));
        }
    }

    /**
     * Get migration status (AJAX)
     */
    public function getStatus()
    {
        try {
            $status = $this->getMigrationStatus();
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (Exception $e) {
            Log::error('Get migration status failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run all pending migrations
     */
    public function runAll(Request $request)
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            Log::info('Migrations run via /migrate/run-all route', ['output' => $output]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => get_label('migrations_run_successfully', 'Migrations run successfully.'),
                    'output' => $output
                ]);
            }

            return redirect('/migrate')->with('message', get_label('migrations_run_successfully', 'Migrations run successfully.'));
        } catch (Exception $e) {
            Log::error('Run all migrations failed', ['error' => $e->getMessage()]);

            // Check if it's a "table already exists" error
            if (str_contains($e->getMessage(), 'Base table or view already exists') ||
                str_contains($e->getMessage(), 'already exists')) {

                // Try to detect and fix the issue
                $tableExistsIssues = $this->detectTableExistsIssues();
                if (!empty($tableExistsIssues)) {
                    $autoFix = $request->input('auto_fix', false);

                    if ($autoFix) {
                        $fixed = $this->fixTableExistsIssues($tableExistsIssues);
                        if (!empty($fixed)) {
                            // Retry migration after fix
                            try {
                                Artisan::call('migrate', ['--force' => true]);
                                $output = Artisan::output();

                                if ($request->expectsJson()) {
                                    return response()->json([
                                        'success' => true,
                                        'message' => get_label('migration_fixed_and_ran', 'Migration issues fixed and migrations run successfully.'),
                                        'output' => $output,
                                        'fixed' => $fixed
                                    ]);
                                }

                                return redirect('/migrate')->with('message', get_label('migration_fixed_and_ran', 'Migration issues fixed and migrations run successfully.'));
                            } catch (Exception $retryException) {
                                // If retry fails, return the fix info
                                if ($request->expectsJson()) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => get_label('migration_fixed_but_retry_failed', 'Migration issues fixed but retry failed: ') . $retryException->getMessage(),
                                        'fixed' => $fixed,
                                        'retry_error' => $retryException->getMessage()
                                    ], 500);
                                }

                                return redirect('/migrate')->with('error', get_label('migration_fixed_but_retry_failed', 'Migration issues fixed but retry failed: ') . $retryException->getMessage());
                            }
                        }
                    }

                    // Return info about the issue and suggest auto-fix
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => get_label('table_already_exists_error', 'Table already exists error detected.'),
                            'error' => $e->getMessage(),
                            'issues' => $tableExistsIssues,
                            'suggest_fix' => true
                        ], 500);
                    }

                    return redirect('/migrate')->with('error', get_label('table_already_exists_error', 'Table already exists error detected. Use "Fix Issues" with auto-fix enabled.'));
                }
            }

            // Check if it's a "table doesn't exist" error
            if (str_contains($e->getMessage(), "doesn't exist") ||
                str_contains($e->getMessage(), 'not found') ||
                str_contains($e->getMessage(), 'Base table or view not found')) {

                $dependencyInfo = $this->detectMissingTableDependency('', $e->getMessage());

                if ($dependencyInfo) {
                    $errorMessage = get_label('table_does_not_exist_error', 'Table does not exist. ') .
                        get_label('required_dependency', 'Required dependency: ') . $dependencyInfo['dependency_migration'] .
                        '. ' . get_label('run_dependency_first', 'Please run the dependency migration first.');

                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage,
                            'dependency_info' => $dependencyInfo,
                            'suggest_dependency' => true
                        ], 500);
                    }

                    return redirect('/migrate')->with('error', $errorMessage)
                        ->with('dependency_info', $dependencyInfo);
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => get_label('migration_failed', 'Migration failed: ') . $e->getMessage()
                ], 500);
            }

            return redirect('/migrate')->with('error', get_label('migration_failed', 'Migration failed: ') . $e->getMessage());
        }
    }

    /**
     * Run a specific migration file
     */
    public function runSingle($filename, Request $request)
    {
        try {
            // Sanitize filename to prevent directory traversal
            $filename = basename($filename);

            // Validate it's a PHP file
            if (!str_ends_with($filename, '.php')) {
                throw new Exception(get_label('invalid_migration_format', 'Invalid migration file format'));
            }

            // Check if file exists
            $migrationPath = database_path('migrations/' . $filename);
            if (!file_exists($migrationPath)) {
                throw new Exception(get_label('migration_file_not_found', 'Migration file not found: ') . $filename);
            }

            // Run the specific migration
            Artisan::call('migrate', [
                '--path' => 'database/migrations/' . $filename,
                '--force' => true
            ]);

            $output = Artisan::output();

            Log::info('Migration run via /migrate/file route', [
                'filename' => $filename,
                'output' => $output
            ]);

            return redirect('/migrate')->with('message', get_label('migration_run_successfully', 'Migration run successfully: ') . $filename);
        } catch (Exception $e) {
            Log::error('Migration failed', [
                'filename' => $filename ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            // Check if it's a "table already exists" error
            if (str_contains($e->getMessage(), 'Base table or view already exists') ||
                str_contains($e->getMessage(), 'already exists')) {

                $migrationName = str_replace('.php', '', $filename);
                $tableExistsIssues = $this->detectTableExistsIssues();
                $relevantIssue = null;

                foreach ($tableExistsIssues as $issue) {
                    if ($issue['migration_name'] === $migrationName) {
                        $relevantIssue = $issue;
                        break;
                    }
                }

                if ($relevantIssue) {
                    $autoFix = $request->input('auto_fix', false);

                    if ($autoFix) {
                        $fixed = $this->fixTableExistsIssues([$relevantIssue]);
                        if (!empty($fixed)) {
                            return redirect('/migrate')->with('message', get_label('migration_fixed_and_marked_as_run', 'Migration issue fixed and marked as run: ') . $filename);
                        }
                    }

                    return redirect('/migrate')->with('error', get_label('table_already_exists_for_migration', 'Table already exists for this migration. Use "Fix Issues" with auto-fix enabled to mark it as run.'));
                }
            }

            // Check if it's a "table doesn't exist" error
            if (str_contains($e->getMessage(), "doesn't exist") ||
                str_contains($e->getMessage(), 'not found') ||
                str_contains($e->getMessage(), 'Base table or view not found')) {

                $migrationName = str_replace('.php', '', $filename);
                $dependencyInfo = $this->detectMissingTableDependency($migrationName, $e->getMessage());

                if ($dependencyInfo) {
                    $errorMessage = get_label('table_does_not_exist_error', 'Table does not exist. ') .
                        get_label('required_dependency', 'Required dependency: ') . $dependencyInfo['dependency_migration'] .
                        '. ' . get_label('run_dependency_first', 'Please run the dependency migration first.');

                    return redirect('/migrate')->with('error', $errorMessage)
                        ->with('dependency_info', $dependencyInfo);
                }
            }

            return redirect('/migrate')->with('error', get_label('migration_failed', 'Migration failed: ') . $e->getMessage());
        }
    }

    /**
     * Check migration sequence and dependencies
     */
    public function checkSequence()
    {
        try {
            $report = [
                'valid' => true,
                'warnings' => [],
                'errors' => [],
                'out_of_order' => [],
                'dependency_errors' => [],
                'recommendations' => []
            ];

            // Get all migration files
            $migrationFiles = File::files(database_path('migrations'));
            $migrations = [];

            foreach ($migrationFiles as $file) {
                $filename = $file->getFilename();
                if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)$/', $filename, $matches)) {
                    $timestamp = $matches[1];
                    $name = $matches[2];
                    $migrations[] = [
                        'filename' => $filename,
                        'timestamp' => $timestamp,
                        'name' => str_replace('.php', '', $name),
                        'path' => $file->getPathname(),
                        'datetime' => $this->parseTimestamp($timestamp)
                    ];
                }
            }

            // Sort by timestamp
            usort($migrations, function ($a, $b) {
                return strcmp($a['timestamp'], $b['timestamp']);
            });

            // Get ran migrations from database
            $ranMigrations = DB::table('migrations')
                ->orderBy('migration')
                ->pluck('migration')
                ->toArray();

            // Check for out-of-order migrations
            foreach ($migrations as $migration) {
                $migrationName = str_replace('.php', '', $migration['filename']);

                // Check if this migration is already ran
                if (in_array($migrationName, $ranMigrations)) {
                    continue;
                }

                // Check if a later migration is already ran
                foreach ($ranMigrations as $ranMigration) {
                    if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)$/', $ranMigration, $ranMatches)) {
                        $ranTimestamp = $ranMatches[1];
                        if (strcmp($ranTimestamp, $migration['timestamp']) > 0) {
                            $report['valid'] = false;
                            $report['out_of_order'][] = [
                                'migration' => $migration['filename'],
                                'timestamp' => $migration['timestamp'],
                                'ran_before' => $ranMigration,
                                'ran_timestamp' => $ranTimestamp
                            ];
                            $report['warnings'][] = get_label('migration_out_of_order', 'Migration out of order: ') . $migration['filename'];
                        }
                    }
                }
            }

            // Check dependencies by analyzing migration content
            $tableCreations = [];
            $tableModifications = [];
            $foreignKeys = [];

            foreach ($migrations as $migration) {
                $content = File::get($migration['path']);
                $migrationName = str_replace('.php', '', $migration['filename']);

                // Skip if already ran
                if (in_array($migrationName, $ranMigrations)) {
                    // Extract table names from ran migrations for dependency checking
                    if (preg_match_all("/Schema::create\(['\"]([^'\"]+)['\"]/", $content, $matches) && isset($matches[1]) && is_array($matches[1])) {
                        foreach ($matches[1] as $table) {
                            $tableCreations[$table] = $migration['timestamp'];
                        }
                    }
                    continue;
                }

                // Check for table modifications before creation
                if (preg_match_all("/Schema::table\(['\"]([^'\"]+)['\"]/", $content, $matches) && isset($matches[1]) && is_array($matches[1])) {
                    foreach ($matches[1] as $table) {
                        if (!isset($tableCreations[$table]) && !Schema::hasTable($table)) {
                            // Check if any later migration creates this table
                            $found = false;
                            foreach ($migrations as $laterMigration) {
                                if (strcmp($laterMigration['timestamp'], $migration['timestamp']) > 0) {
                                    $laterContent = File::get($laterMigration['path']);
                                    if (preg_match("/Schema::create\(['\"]" . preg_quote($table, '/') . "['\"]/", $laterContent)) {
                                        $found = true;
                                        $report['valid'] = false;
                                        $report['dependency_errors'][] = [
                                            'migration' => $migration['filename'],
                                            'table' => $table,
                                            'created_in' => $laterMigration['filename']
                                        ];
                                        $report['errors'][] = get_label('migration_dependency_error', 'Migration dependency error: ') .
                                            $migration['filename'] . ' modifies table "' . $table . '" which is created in a later migration.';
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                // Check for foreign keys
                if (preg_match_all("/->foreign\(['\"]([^'\"]+)['\"]\)/", $content, $matches) && isset($matches[1]) && is_array($matches[1])) {
                    foreach ($matches[1] as $foreignKey) {
                        $foreignKeys[] = [
                            'migration' => $migration['filename'],
                            'foreign_key' => $foreignKey
                        ];
                    }
                }
            }

            // Generate recommendations
            if (!empty($report['out_of_order'])) {
                $report['recommendations'][] = get_label('fix_out_of_order_migrations', 'Consider rolling back and re-running migrations in correct order.');
            }

            if (!empty($report['dependency_errors'])) {
                $report['recommendations'][] = get_label('fix_dependency_errors', 'Review migration dependencies and ensure tables are created before being modified.');
            }

            return response()->json([
                'success' => true,
                'report' => $report
            ]);
        } catch (Exception $e) {
            Log::error('Check migration sequence failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fix migration issues
     */
    public function fixIssues(Request $request)
    {
        try {
            $fixes = [];
            $errors = [];
            $warnings = [];
            $autoFix = $request->input('auto_fix', false);

            // Check for syntax errors using php -l
            $migrationFiles = File::files(database_path('migrations'));
            foreach ($migrationFiles as $file) {
                $filePath = $file->getPathname();
                $output = [];
                $returnVar = 0;

                // Use php -l to check syntax
                exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnVar);

                if ($returnVar !== 0) {
                    $errorOutput = implode("\n", $output);
                    $errors[] = get_label('syntax_error_in_migration', 'Syntax error in migration: ') . $file->getFilename() . ' - ' . $errorOutput;
                }
            }

            // Check for "table already exists" issues
            $tableExistsIssues = $this->detectTableExistsIssues();
            if (!empty($tableExistsIssues)) {
                $fixes['table_exists'] = $tableExistsIssues;

                if ($autoFix) {
                    $fixed = $this->fixTableExistsIssues($tableExistsIssues);
                    $fixes['table_exists_fixed'] = $fixed;
                } else {
                    $warnings[] = get_label('table_exists_issues_found', 'Found migrations where tables already exist. Enable auto-fix to mark them as run.');
                }
            }

            // Check for missing columns
            // This would require comparing migration files with actual database schema
            // For now, we'll just provide a basic structure

            // Get sequence check results
            $sequenceCheck = $this->checkSequence();
            $sequenceData = json_decode($sequenceCheck->getContent(), true);

            if (isset($sequenceData['report'])) {
                $fixes['sequence'] = $sequenceData['report'];
            }

            return response()->json([
                'success' => true,
                'fixes' => $fixes,
                'errors' => $errors,
                'warnings' => $warnings,
                'message' => get_label('migration_issues_checked', 'Migration issues checked.')
            ]);
        } catch (Exception $e) {
            Log::error('Fix migration issues failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detect migrations where tables already exist but migration is not recorded
     */
    private function detectTableExistsIssues()
    {
        $issues = [];
        $migrationFiles = File::files(database_path('migrations'));
        $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();

        foreach ($migrationFiles as $file) {
            $filename = $file->getFilename();
            $migrationName = str_replace('.php', '', $filename);

            // Skip if already recorded as run
            if (in_array($migrationName, $ranMigrations)) {
                continue;
            }

            $content = File::get($file->getPathname());

            // Check for Schema::create statements
            if (preg_match_all("/Schema::create\(['\"]([^'\"]+)['\"]/", $content, $matches)) {
                foreach ($matches[1] as $tableName) {
                    // Check if table exists in database
                    if (Schema::hasTable($tableName)) {
                        $issues[] = [
                            'migration' => $filename,
                            'migration_name' => $migrationName,
                            'table' => $tableName,
                            'issue' => 'table_already_exists',
                            'message' => get_label('table_already_exists_message', 'Table') . " '{$tableName}' " . get_label('already_exists_but_migration_not_recorded', 'already exists but migration is not recorded as run.')
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Detect missing table dependency for a migration
     */
    private function detectMissingTableDependency($migrationName, $errorMessage)
    {
        // Extract table name from error message
        $tableName = null;
        if (preg_match("/Table ['\"]([^'\"]+)['\"]/", $errorMessage, $matches)) {
            $tableName = $matches[1];
        } elseif (preg_match("/alter table [`'\"]([^`'\"]+)[`'\"]/i", $errorMessage, $matches)) {
            $tableName = $matches[1];
        }

        if (!$tableName) {
            return null;
        }

        // Find migration that creates this table
        $migrationFiles = File::files(database_path('migrations'));
        $dependencyMigration = null;
        $currentMigrationPath = null;

        foreach ($migrationFiles as $file) {
            $filename = $file->getFilename();
            $name = str_replace('.php', '', $filename);

            if ($name === $migrationName) {
                $currentMigrationPath = $file->getPathname();
                continue;
            }

            $content = File::get($file->getPathname());

            // Check if this migration creates the required table
            if (preg_match("/Schema::create\(['\"]([^'\"]+)['\"]/", $content, $matches)) {
                if ($matches[1] === $tableName) {
                    $dependencyMigration = [
                        'filename' => $filename,
                        'migration_name' => $name,
                        'table' => $tableName,
                        'path' => $file->getPathname()
                    ];

                    // Extract timestamp
                    if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)$/', $name, $tsMatches)) {
                        $dependencyMigration['timestamp'] = $tsMatches[1];
                    }
                    break;
                }
            }
        }

        if ($dependencyMigration && $currentMigrationPath) {
            // Check if dependency migration is already run
            $isDependencyRun = DB::table('migrations')
                ->where('migration', $dependencyMigration['migration_name'])
                ->exists();

            return [
                'migration' => $migrationName,
                'table' => $tableName,
                'dependency_migration' => $dependencyMigration['filename'],
                'dependency_name' => $dependencyMigration['migration_name'],
                'dependency_path' => $dependencyMigration['path'],
                'is_dependency_run' => $isDependencyRun,
                'message' => get_label('table_requires_dependency', 'This migration requires table') . " '{$tableName}' " .
                    get_label('which_is_created_in', 'which is created in') . " '{$dependencyMigration['filename']}'"
            ];
        }

        return null;
    }

    /**
     * Fix table exists issues by marking migrations as run
     */
    private function fixTableExistsIssues($issues)
    {
        $fixed = [];
        $batch = DB::table('migrations')->max('batch') ?? 0;
        $newBatch = $batch + 1;

        foreach ($issues as $issue) {
            try {
                // Check if migration is already recorded
                $exists = DB::table('migrations')
                    ->where('migration', $issue['migration_name'])
                    ->exists();

                if (!$exists) {
                    // Insert migration record
                    DB::table('migrations')->insert([
                        'migration' => $issue['migration_name'],
                        'batch' => $newBatch
                    ]);

                    $fixed[] = [
                        'migration' => $issue['migration'],
                        'table' => $issue['table'],
                        'action' => 'marked_as_run',
                        'message' => get_label('migration_marked_as_run', 'Migration marked as run: ') . $issue['migration']
                    ];

                    Log::info('Fixed table exists issue', [
                        'migration' => $issue['migration_name'],
                        'table' => $issue['table']
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Failed to fix table exists issue', [
                    'migration' => $issue['migration_name'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $fixed;
    }

    /**
     * Validate migrations
     */
    public function validateMigrations()
    {
        try {
            $validation = [
                'valid' => true,
                'errors' => [],
                'warnings' => []
            ];

            // Check migration files
            $migrationFiles = File::files(database_path('migrations'));
            foreach ($migrationFiles as $file) {
                $filename = $file->getFilename();

                // Check file naming convention
                if (!preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $filename)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = get_label('invalid_migration_filename', 'Invalid migration filename format: ') . $filename;
                }

                // Check file content structure
                $content = File::get($file->getPathname());
                if (!str_contains($content, 'extends Migration')) {
                    $validation['valid'] = false;
                    $validation['errors'][] = get_label('invalid_migration_structure', 'Invalid migration structure in: ') . $filename;
                }

                if (!str_contains($content, 'public function up()')) {
                    $validation['warnings'][] = get_label('missing_up_method', 'Missing up() method in: ') . $filename;
                }
            }

            // Check database connection
            try {
                DB::connection()->getPdo();
            } catch (Exception $e) {
                $validation['valid'] = false;
                $validation['errors'][] = get_label('database_connection_failed', 'Database connection failed: ') . $e->getMessage();
            }

            // Include sequence check
            $sequenceCheck = $this->checkSequence();
            $sequenceData = json_decode($sequenceCheck->getContent(), true);
            if (isset($sequenceData['report']) && !$sequenceData['report']['valid']) {
                $validation['valid'] = false;
                $validation['warnings'] = array_merge($validation['warnings'], $sequenceData['report']['warnings'] ?? []);
                $validation['errors'] = array_merge($validation['errors'], $sequenceData['report']['errors'] ?? []);
            }

            return response()->json([
                'success' => true,
                'validation' => $validation
            ]);
        } catch (Exception $e) {
            Log::error('Validate migrations failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rollback failed migrations
     */
    public function rollbackFailed()
    {
        try {
            // Get failed migrations (migrations that exist in files but not in migrations table with errors)
            // This is a simplified version - in practice, you'd track failed migrations differently

            Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
            $output = Artisan::output();

            Log::info('Rollback migrations', ['output' => $output]);

            return response()->json([
                'success' => true,
                'message' => get_label('migrations_rolled_back', 'Migrations rolled back successfully.'),
                'output' => $output
            ]);
        } catch (Exception $e) {
            Log::error('Rollback failed migrations failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get migration status data
     */
    private function getMigrationStatus()
    {
        Artisan::call('migrate:status');
        $output = Artisan::output();

        $pendingMigrations = [];
        $ranMigrations = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Check for pending migrations
            if (str_contains($line, 'Pending') && !str_contains($line, 'Migration name')) {
                if (preg_match('/^\s*([a-z0-9_]+)\s+\.+.*Pending$/i', $line, $matches)) {
                    $migrationName = trim($matches[1]);
                    $filename = $migrationName . '.php';
                    $migrationPath = database_path('migrations/' . $filename);

                    $migrationData = [
                        'filename' => $filename,
                        'name' => $migrationName,
                        'path' => 'database/migrations/' . $filename,
                        'file_exists' => file_exists($migrationPath)
                    ];

                    // Extract timestamp if available
                    if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)$/', $migrationName, $tsMatches)) {
                        $migrationData['timestamp'] = $tsMatches[1];
                        $migrationData['datetime'] = $this->parseTimestamp($tsMatches[1]);
                    }

                    $pendingMigrations[] = $migrationData;
                }
            }

            // Check for ran migrations
            if (str_contains($line, 'Ran') && !str_contains($line, 'Migration name') && !str_contains($line, 'Pending')) {
                if (preg_match('/^\s*([a-z0-9_]+)\s+\.+.*Ran$/i', $line, $matches)) {
                    $migrationName = trim($matches[1]);
                    $filename = $migrationName . '.php';
                    $migrationPath = database_path('migrations/' . $filename);

                    $migrationData = [
                        'filename' => $filename,
                        'name' => $migrationName,
                        'path' => 'database/migrations/' . $filename,
                        'file_exists' => file_exists($migrationPath)
                    ];

                    // Extract timestamp if available
                    if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)$/', $migrationName, $tsMatches)) {
                        $migrationData['timestamp'] = $tsMatches[1];
                        $migrationData['datetime'] = $this->parseTimestamp($tsMatches[1]);
                    }

                    $ranMigrations[] = $migrationData;
                }
            }
        }

        // Fallback: Get all migration files and compare with database
        // This ensures we catch migrations that might not appear in artisan output
        $allMigrationFiles = [];
        $migrationFiles = File::files(database_path('migrations'));
        foreach ($migrationFiles as $file) {
            $filename = $file->getFilename();
            $migrationName = str_replace('.php', '', $filename);
            $allMigrationFiles[$migrationName] = [
                'filename' => $filename,
                'name' => $migrationName,
                'path' => 'database/migrations/' . $filename,
                'file_exists' => true
            ];

            // Extract timestamp if available
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)$/', $migrationName, $tsMatches)) {
                $allMigrationFiles[$migrationName]['timestamp'] = $tsMatches[1];
                $allMigrationFiles[$migrationName]['datetime'] = $this->parseTimestamp($tsMatches[1]);
            }
        }

        // Get ran migrations from database
        $ranMigrationsFromDb = DB::table('migrations')
            ->orderBy('migration')
            ->pluck('migration')
            ->toArray();

        // Create arrays of migration names we've already found
        $foundPendingNames = array_map(function($m) { return $m['name']; }, $pendingMigrations);
        $foundRanNames = array_map(function($m) { return $m['name']; }, $ranMigrations);

        // Check all migration files - if not found in pending or ran, add appropriately
        foreach ($allMigrationFiles as $migrationName => $migrationData) {
            if (!in_array($migrationName, $foundPendingNames) && !in_array($migrationName, $foundRanNames)) {
                // Migration not found in artisan output, check database
                if (in_array($migrationName, $ranMigrationsFromDb)) {
                    // It's in database, add to ran
                    $ranMigrations[] = $migrationData;
                    $foundRanNames[] = $migrationName;
                } else {
                    // Not in database, should be pending
                    $pendingMigrations[] = $migrationData;
                    $foundPendingNames[] = $migrationName;
                }
            }
        }

        // Sort by filename (chronological order)
        usort($pendingMigrations, function ($a, $b) {
            return strcmp($a['filename'] ?? '', $b['filename'] ?? '');
        });

        usort($ranMigrations, function ($a, $b) {
            return strcmp($a['filename'] ?? '', $b['filename'] ?? '');
        });

        return [
            'pending' => $pendingMigrations,
            'ran' => $ranMigrations,
            'total' => count($pendingMigrations) + count($ranMigrations),
            'raw_output' => $output
        ];
    }

    /**
     * Parse timestamp from migration filename
     */
    private function parseTimestamp($timestamp)
    {
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})$/', $timestamp, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] . ':' . $matches[6];
        }
        return $timestamp;
    }
}

