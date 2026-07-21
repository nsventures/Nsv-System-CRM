<?php

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Task;
use App\Models\User;
use App\Models\Client;
use App\Models\Status;
use App\Models\Update;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Setting;
use App\Models\FcmToken;
use App\Models\Template;
use App\Models\Candidate;
use App\Models\Workspace;
use App\Models\ActivityLog;
use App\Models\CustomField;
use App\Models\LeaveEditor;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\LeaveRequest;
use App\Models\Notification;
use Chatify\ChatifyMessenger;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UserClientPreference;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client as TwilioClient;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use App\Notifications\AssignmentNotification;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


if (!function_exists('get_timezone_array')) {
    // 1.Get Time Zone
    function get_timezone_array()
    {
        $list = DateTimeZone::listAbbreviations();
        $idents = DateTimeZone::listIdentifiers();
        $data = $offset = $added = array();
        foreach ($list as $abbr => $info) {
            foreach ($info as $zone) {
                if (
                    !empty($zone['timezone_id'])
                    and
                    !in_array($zone['timezone_id'], $added)
                    and
                    in_array($zone['timezone_id'], $idents)
                ) {
                    $z = new DateTimeZone($zone['timezone_id']);
                    $c = new DateTime("", $z);
                    $zone['time'] = $c->format('h:i A');
                    $offset[] = $zone['offset'] = $z->getOffset($c);
                    $data[] = $zone;
                    $added[] = $zone['timezone_id'];
                }
            }
        }
        array_multisort($offset, SORT_ASC, $data);
        $i = 0;
        $temp = array();
        foreach ($data as $key => $row) {
            $temp[0] = $row['time'];
            $temp[1] = formatOffset($row['offset']);
            $temp[2] = $row['timezone_id'];
            $options[$i++] = $temp;
        }
        return $options;
    }
}
if (!function_exists('formatOffset')) {
    function formatOffset($offset)
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);
        if ($hour == 0 and $minutes == 0) {
            $sign = ' ';
        }
        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');
    }
}
if (!function_exists('relativeTime')) {
    function relativeTime($time)
    {
        if (!ctype_digit($time))
            $time = strtotime($time);
        $d[0] = array(1, "second");
        $d[1] = array(60, "minute");
        $d[2] = array(3600, "hour");
        $d[3] = array(86400, "day");
        $d[4] = array(604800, "week");
        $d[5] = array(2592000, "month");
        $d[6] = array(31104000, "year");
        $w = array();
        $return = "";
        $now = time();
        $diff = ($now - $time);
        $secondsLeft = $diff;
        for ($i = 6; $i > -1; $i--) {
            $w[$i] = intval($secondsLeft / $d[$i][0]);
            $secondsLeft -= ($w[$i] * $d[$i][0]);
            if ($w[$i] != 0) {
                $return .= abs($w[$i]) . " " . $d[$i][1] . (($w[$i] > 1) ? 's' : '') . " ";
            }
        }
        $return .= ($diff > 0) ? "ago" : "left";
        return $return;
    }
}
if (!function_exists('get_settings')) {
    function get_settings($variable, $default = null)
    {
        static $requestCache = null;

        if ($requestCache === null) {
            //  dd("Loading from Laravel Cache / DB");

            // Use Laravel Cache to store settings persistently (e.g. for 24 hours)
            // This prevents DB queries on every single request across all users
            $requestCache = \Illuminate\Support\Facades\Cache::remember('app_settings_global', 86400, function () {
                //   dd("Fetching from Database");
                return \App\Models\Setting::pluck('value', 'variable')->toArray();
            });
        }

        $value = $requestCache[$variable] ?? $default;

        if ($value && is_string($value) && isJson($value)) {
            return json_decode($value, true);
        }

        return $value;
    }
}
if (!function_exists('isJson')) {
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
if (!function_exists('create_label')) {
    function create_label($variable, $title = '', $locale = '')
    {
        if ($title == '') {
            $title = $variable;
        }
        $value = htmlspecialchars(get_label($variable, $title, $locale), ENT_QUOTES, 'UTF-8');
        return "
        <div class='mb-3 col-md-6'>
                    <label class='form-label' for='$variable'>$title</label>
                    <div class='input-group input-group-merge'>
                        <input type='text' name='$variable' class='form-control' value='$value'>
                    </div>
                </div>
        ";
    }
}
if (!function_exists('get_label')) {
    function get_label($label, $default, $locale = '')
    {
        // Check if the database connection is available
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Exception $e) {
            $dbConnected = false;
        }
        // Only fetch general settings if the database is connected
        $general_settings = $dbConnected ? get_settings('general_settings') : [];
        if ($dbConnected && (!isset($general_settings['priLangAsAuth']) || $general_settings['priLangAsAuth'] == 1 && (Request::is('forgot-password') || Request::is('/') || Request::segment(1) == 'reset-password' || Request::is('signup')))) {
            // Get the default language set by the first admin
            $mainAdminId = getMainAdminId();
            $adminLang = DB::table('users')
                ->where('id', $mainAdminId)
                ->value('lang');
            // If a locale is not provided, use the admin's language as fallback
            if (empty($locale)) {
                $locale = $adminLang ?: app()->getLocale(); // Use admin's language or default app locale
            }
        } else {
            // Use default app locale if DB is not connected
            $locale = $locale ?: app()->getLocale();
        }
        // Check if the label exists in the requested locale
        if (Lang::has('labels.' . $label, $locale)) {
            return trans('labels.' . $label, [], $locale);
        } else {
            return $default;
        }
    }
}
if (!function_exists('empty_state')) {
    function empty_state($url)
    {
        $dataNotFound = get_label('data_not_found', 'Data Not Found');
        $oopsMessage = get_label('oops_data_doesnt_exist', "Oops! 😖 Data doesn't exists.");
        $createNow = get_label('create_now', 'Create now');

        return "
    <div class='card text-center'>
    <div class='card-body'>
        <div class='misc-wrapper'>
            <h2 class='mb-2 mx-2'>" . htmlspecialchars($dataNotFound, ENT_QUOTES, 'UTF-8') . " </h2>
            <p class='mb-4 mx-2'>" . htmlspecialchars($oopsMessage, ENT_QUOTES, 'UTF-8') . "</p>
            <a href='/$url' class='btn btn-primary'>" . htmlspecialchars($createNow, ENT_QUOTES, 'UTF-8') . "</a>
            <div class='mt-3'>
                <img src='../assets/img/illustrations/page-misc-error-light.png' alt='page-misc-error-light' width='500' class='img-fluid' data-app-dark-img='illustrations/page-misc-error-dark.png' data-app-light-img='illustrations/page-misc-error-light.png' />
            </div>
        </div>
    </div>
</div>";
    }
}
if (!function_exists('format_date')) {
    function format_date($date, $time = false, $from_format = null, $to_format = null, $apply_timezone = true)
    {
        if ($date) {
            $from_format = $from_format ?? 'Y-m-d';
            $to_format = $to_format ?? get_php_date_time_format();
            $time_format = get_php_date_time_format(true);
            if ($time) {
                if ($apply_timezone) {
                    if (!$date instanceof \Carbon\Carbon) {
                        // Try with seconds first, then without
                        try {
                            $dateObj = \Carbon\Carbon::createFromFormat($from_format . ' H:i:s', $date)
                                ->setTimezone(config('app.timezone'));
                        } catch (\Exception $e) {
                            $dateObj = \Carbon\Carbon::createFromFormat($from_format . ' H:i', $date)
                                ->setTimezone(config('app.timezone'));
                        }
                    } else {
                        $dateObj = $date->setTimezone(config('app.timezone'));
                    }
                } else {
                    if (!$date instanceof \Carbon\Carbon) {
                        // Try with seconds first, then without
                        try {
                            $dateObj = \Carbon\Carbon::createFromFormat($from_format . ' H:i:s', $date);
                        } catch (\Exception $e) {
                            $dateObj = \Carbon\Carbon::createFromFormat($from_format . ' H:i', $date);
                        }
                    } else {
                        $dateObj = $date;
                    }
                }
            } else {
                if (!$date instanceof \Carbon\Carbon) {
                    $dateObj = \Carbon\Carbon::createFromFormat($from_format, $date);
                } else {
                    $dateObj = $date;
                }
            }
            $timeFormat = $time ? ' ' . $time_format : '';
            $date = $dateObj->format($to_format . $timeFormat);
            return $date;
        } else {
            return '-';
        }
    }
}
if (!function_exists('getAuthenticatedUser')) {
    function getAuthenticatedUser($idOnly = false, $withPrefix = false)
    {
        $prefix = '';
        // Check the 'web' guard (users)
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $prefix = 'u_';
        }
        // Check the 'client' guard (clients)
        elseif (Auth::guard('client')->check()) {
            $user = Auth::guard('client')->user();
            $prefix = 'c_';
        }
        // Check the 'sanctum' guard (API tokens)
        elseif (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            // Optionally set a prefix for sanctum-authenticated users
            // $prefix = 's_';
        }
        // No user is authenticated
        else {
            return null;
        }
        if ($idOnly) {
            if ($withPrefix) {
                return $prefix . $user->id;
            } else {
                return $user->id;
            }
        }

        return $user;
    }
}
if (!function_exists('isUser')) {
    function isUser()
    {
        return Auth::guard('web')->check(); // Assuming 'role' is a field in the user model.
    }
}
if (!function_exists('isClient')) {
    function isClient()
    {
        return Auth::guard('client')->check(); // Assuming 'role' is a field in the user model.
    }
}
if (!function_exists('generateUniqueSlug')) {
    function generateUniqueSlug($title, $model, $id = null)
    {
        $slug = Str::slug($title);
        $count = 2;
        // If an ID is provided, add a where clause to exclude it
        if ($id !== null) {
            while ($model::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = Str::slug($title) . '-' . $count;
                $count++;
            }
        } else {
            while ($model::where('slug', $slug)->exists()) {
                $slug = Str::slug($title) . '-' . $count;
                $count++;
            }
        }
        return $slug;
    }
}
if (!function_exists('duplicateRecord')) {
    function duplicateRecord($model, $id, $relatedTables = [], $title = '')
    {
        $eagerLoadRelations = $relatedTables;
        $eagerLoadRelations = array_filter($eagerLoadRelations, function ($table) {
            return $table !== 'project_tasks'; // Exclude from eager loading
        });
        // Eager load the related tables excluding 'project_tasks'
        $originalRecord = $model::with($eagerLoadRelations)->find($id);
        if (!$originalRecord) {
            return false; // Record not found
        }
        // Start a new database transaction to ensure data consistency
        DB::beginTransaction();
        try {
            // Duplicate the original record
            $duplicateRecord = $originalRecord->replicate();
            // Set the title if provided
            if (!empty($title)) {
                $duplicateRecord->title = $title;
            }
            $duplicateRecord->save();
            foreach ($relatedTables as $relatedTable) {
                if ($relatedTable === 'projects') {
                    foreach ($originalRecord->$relatedTable as $project) {
                        // Duplicate the project
                        $duplicateProject = $project->replicate();
                        $duplicateProject->workspace_id = $duplicateRecord->id; // Set the new workspace ID
                        $duplicateProject->save();
                        // Attach project users
                        foreach ($project->users as $user) {
                            $duplicateProject->users()->attach($user->id);
                        }
                        // Attach project clients
                        foreach ($project->clients as $client) {
                            $duplicateProject->clients()->attach($client->id);
                        }
                        // Duplicate the project's tasks
                        if (in_array('project_tasks', $relatedTables)) {
                            foreach ($project->tasks as $task) {
                                $duplicateTask = $task->replicate();
                                $duplicateTask->workspace_id = $duplicateRecord->id;
                                $duplicateTask->project_id = $duplicateProject->id; // Set the new project ID
                                $duplicateTask->save();
                                // Duplicate task's users (if applicable)
                                foreach ($task->users as $user) {
                                    $duplicateTask->users()->attach($user->id);
                                }
                            }
                        }
                    }
                }
                if ($relatedTable === 'tasks') {
                    // Handle 'tasks' relationship separately
                    foreach ($originalRecord->$relatedTable as $task) {
                        // Duplicate the related task
                        $duplicateTask = $task->replicate();
                        $duplicateTask->project_id = $duplicateRecord->id;
                        $duplicateTask->save();
                        foreach ($task->users as $user) {
                            // Attach the duplicated user to the duplicated task
                            $duplicateTask->users()->attach($user->id);
                        }
                    }
                }
                if ($relatedTable === 'meetings') {
                    foreach ($originalRecord->$relatedTable as $meeting) {
                        $duplicateMeeting = $meeting->replicate();
                        $duplicateMeeting->workspace_id = $duplicateRecord->id; // Set the new workspace ID
                        $duplicateMeeting->save();
                        // Duplicate meeting's users
                        foreach ($meeting->users as $user) {
                            $duplicateMeeting->users()->attach($user->id);
                        }
                        // Duplicate meeting's clients
                        foreach ($meeting->clients as $client) {
                            $duplicateMeeting->clients()->attach($client->id);
                        }
                    }
                }
                if ($relatedTable === 'todos') {
                    // Duplicate todos
                    foreach ($originalRecord->$relatedTable as $todo) {
                        $duplicateTodo = $todo->replicate();
                        $duplicateTodo->workspace_id = $duplicateRecord->id; // Set the new workspace ID
                        $duplicateTodo->creator_type = $todo->creator_type; // Keep original creator type
                        $duplicateTodo->creator_id = $todo->creator_id;     // Keep original creator ID
                        $duplicateTodo->save();
                    }
                }
                if ($relatedTable === 'notes') {
                    foreach ($originalRecord->$relatedTable as $note) {
                        $duplicateNote = $note->replicate();
                        $duplicateNote->workspace_id = $duplicateRecord->id; // Set the new workspace ID
                        $duplicateNote->creator_id = $note->creator_id;      // Retain the creator_id
                        $duplicateNote->save();
                    }
                }
            }
            // Handle many-to-many relationships separately
            if (in_array('users', $relatedTables)) {
                $originalRecord->users()->each(function ($user) use ($duplicateRecord) {
                    $duplicateRecord->users()->attach($user->id);
                });
            }
            if (in_array('clients', $relatedTables)) {
                $originalRecord->clients()->each(function ($client) use ($duplicateRecord) {
                    $duplicateRecord->clients()->attach($client->id);
                });
            }
            if (in_array('tags', $relatedTables)) {
                $originalRecord->tags()->each(function ($tag) use ($duplicateRecord) {
                    $duplicateRecord->tags()->attach($tag->id);
                });
            }
            // Commit the transaction
            DB::commit();
            return $duplicateRecord;
        } catch (\Exception $e) {
            // Handle any exceptions and rollback the transaction on failure
            DB::rollback();
            return false;
        }
    }
}
if (!function_exists('is_admin_or_leave_editor')) {
    function is_admin_or_leave_editor($user = null)
    {
        if (!$user) {
            $user = getAuthenticatedUser();
        }
        // Check if the user is an admin or a leave editor based on their presence in the leave_editors table
        if ($user->hasRole('admin') || LeaveEditor::where('user_id', $user->id)->exists()) {
            return true;
        }
        return false;
    }
}
if (!function_exists('get_php_date_time_format')) {
    function get_php_date_time_format($timeFormat = false)
    {
        $general_settings = get_settings('general_settings');
        // Ensure $general_settings is an array to avoid errors when accessing array keys
        if (!is_array($general_settings)) {
            $general_settings = [];
        }
        if ($timeFormat) {
            return $general_settings['time_format'] ?? 'H:i:s';
        } else {
            $date_format = $general_settings['date_format'] ?? 'DD-MM-YYYY|d-m-Y';
            $date_format = explode('|', $date_format);
            return $date_format[1];
        }
    }
}
if (!function_exists('get_system_update_info')) {
    function get_system_update_info()
    {
        $updatePath = Config::get('constants.UPDATE_PATH');
        $updaterPath = $updatePath . 'updater.json';
        $subDirectory = (File::exists($updaterPath) && File::exists($updatePath . 'update/updater.json')) ? 'update/' : '';
        if (File::exists($updaterPath) || File::exists($updatePath . $subDirectory . 'updater.json')) {
            $updaterFilePath = File::exists($updaterPath) ? $updaterPath : $updatePath . $subDirectory . 'updater.json';
            $updaterContents = File::get($updaterFilePath);
            // Check if the file contains valid JSON data
            if (!json_decode($updaterContents)) {
                throw new \RuntimeException('Invalid JSON content in updater.json');
            }
            $linesArray = json_decode($updaterContents, true);
            if (!isset($linesArray['version'], $linesArray['previous'], $linesArray['manual_queries'], $linesArray['query_path'])) {
                throw new \RuntimeException('Invalid JSON structure in updater.json');
            }
        } else {
            throw new \RuntimeException('updater.json does not exist');
        }
        $dbCurrentVersion = Update::latest()->first();
        $data['db_current_version'] = $dbCurrentVersion ? $dbCurrentVersion->version : '1.0.0';
        if ($data['db_current_version'] == $linesArray['version']) {
            $data['updated_error'] = true;
            $data['message'] = get_label('version_already_updated_try_another_one', 'Oops!. This version is already updated into your system. Try another one.');
            return $data;
        }
        if ($data['db_current_version'] == $linesArray['previous']) {
            $data['file_current_version'] = $linesArray['version'];
        } else {
            $data['sequence_error'] = true;
            $data['message'] = get_label('update_must_be_performed_in_sequence', 'Oops!. Update must performed in sequence.');
            return $data;
        }
        $data['query'] = $linesArray['manual_queries'];
        $data['query_path'] = $linesArray['query_path'];
        return $data;
    }
}
if (!function_exists('escape_array')) {
    function escape_array($array)
    {
        if (empty($array)) {
            return $array;
        }
        $db = DB::connection()->getPdo();
        if (is_array($array)) {
            return array_map(function ($value) use ($db) {
                return $db->quote($value);
            }, $array);
        } else {
            // Handle single non-array value
            return $db->quote($array);
        }
    }
}
if (!function_exists('isEmailConfigured')) {
    function isEmailConfigured()
    {
        $email_settings = get_settings('email_settings');

        // Step 1: Ensure all required SMTP fields are present
        if (
            empty($email_settings['email']) ||
            empty($email_settings['password']) ||
            empty($email_settings['smtp_host']) ||
            empty($email_settings['smtp_port'])
        ) {
            return false;
        }

        // Step 2: Try SMTP connection
        try {
            $transport = new EsmtpTransport(
                $email_settings['smtp_host'],
                (int) $email_settings['smtp_port'],
                ($email_settings['encryption'] ?? null) === 'ssl'
            );

            $transport->setUsername($email_settings['email']);
            $transport->setPassword($email_settings['password']);

            // This actually opens the connection to verify SMTP
            $transport->start();

            return true;
        } catch (TransportExceptionInterface $e) {
            Log::error('SMTP connection failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected SMTP error: ' . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('get_current_version')) {
    function get_current_version()
    {
        $dbCurrentVersion = Update::latest()->first();
        return $dbCurrentVersion ? $dbCurrentVersion->version : '1.0.0';
    }
}
if (!function_exists('isAdminOrHasAllDataAccess')) {
    function isAdminOrHasAllDataAccess($type = null, $id = null)
    {
        // Get authenticated user
        $authenticatedUser = getAuthenticatedUser();
        if ($type == 'user' && $id !== null) {
            $user = User::find($id);
            if ($user) {
                return $user->hasRole('admin') || $user->can('access_all_data');
            }
        } elseif ($type == 'client' && $id !== null) {
            $client = Client::find($id);
            if ($client) {
                return $client->hasRole('admin') || $client->can('access_all_data');
            }
        } elseif ($type === null && $id === null) {
            if ($authenticatedUser) {
                return $authenticatedUser->hasRole('admin') || $authenticatedUser->can('access_all_data');
            }
        }
        return false;
    }
}
if (!function_exists('getControllerNames')) {
    function getControllerNames()
    {
        $controllersPath = app_path('Http/Controllers');
        $files = File::files($controllersPath);
        $excludedControllers = [
            'ActivityLogController',
            'Controller',
            'HomeController',
            'InstallerController',
            'LanguageController',
            'ProfileController',
            'RolesController',
            'SearchController',
            'SettingsController',
            'UpdaterController',
            'EstimatesInvoicesController',
            'PreferenceController',
            'ReportsController',
            'NotificationsController',
            'SwaggerController'
        ];
        $controllerNames = [];
        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            // Skip controllers in the excluded list
            if (in_array($fileName, $excludedControllers)) {
                continue;
            }
            if (str_ends_with($fileName, 'Controller')) {
                // Convert to singular form, snake_case, and remove 'Controller' suffix
                $controllerName = Str::snake(Str::singular(str_replace('Controller', '', $fileName)));
                $controllerNames[] = $controllerName;
            }
        }
        // Add manually defined types
        $manuallyDefinedTypes = [
            'contract_type',
            'media',
            'estimate',
            'invoice',
            'milestone'
            // Add more types as needed
        ];
        $controllerNames = array_merge($controllerNames, $manuallyDefinedTypes);
        return $controllerNames;
    }
}
if (!function_exists('formatSize')) {
    function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
if (!function_exists('getStatusColor')) {
    function getStatusColor($status)
    {
        switch ($status) {
            case 'sent':
                return 'primary';
            case 'accepted':
            case 'fully_paid':
                return 'success';
            case 'draft':
                return 'secondary';
            case 'declined':
            case 'due':
                return 'danger';
            case 'expired':
            case 'partially_paid':
                return 'warning';
            case 'not_specified':
                return 'secondary';
            default:
                return 'info';
        }
    }
}
if (!function_exists('getStatusCount')) {
    function getStatusCount($status, $type)
    {
        $estimates_invoices = isAdminOrHasAllDataAccess() ? Workspace::find(getWorkspaceId())->estimates_invoices($status, $type) : getAuthenticatedUser()->estimates_invoices($status, $type);
        return $estimates_invoices->count();
    }
}
if (!function_exists('format_currency')) {
    function format_currency($amount, $is_currency_symbol = 1, $include_separators = true)
    {
        if ($amount == '') {
            return '';
        }
        $general_settings = get_settings('general_settings');
        $currency_symbol = $general_settings['currency_symbol'] ?? '₹';
        $currency_format = $general_settings['currency_formate'] ?? 'comma_separated';
        $decimal_points = intval($general_settings['decimal_points_in_currency'] ?? '2');
        $currency_symbol_position = $general_settings['currency_symbol_position'] ?? 'before';
        // Determine the appropriate separators based on the currency format and $use_commas parameter
        if ($include_separators) {
            $thousands_separator = ($currency_format == 'comma_separated') ? ',' : '.';
        } else {
            $thousands_separator = '';
        }
        // Format the amount with the determined separators
        $formatted_amount = number_format($amount, $decimal_points, '.', $thousands_separator);
        if ($is_currency_symbol) {
            // Format currency symbol position
            if ($currency_symbol_position === 'before') {
                $currency_amount = $currency_symbol . ' ' . $formatted_amount;
            } else {
                $currency_amount = $formatted_amount . ' ' . $currency_symbol;
            }
            return $currency_amount;
        }
        return $formatted_amount;
    }
}
if (!function_exists('get_tax_data')) {
    function get_tax_data($tax_id, $total_amount, $currency_symbol = 0)
    {
        // Check if tax_id is not empty
        if ($tax_id != '') {
            // Retrieve tax data from the database using the tax_id
            $tax = Tax::find($tax_id);
            // Check if tax data is found
            if ($tax) {
                // Get tax rate and type
                $taxRate = $tax->amount;
                $taxType = $tax->type;
                // Calculate tax amount based on tax rate and type
                $taxAmount = 0;
                $disp_tax = '';
                if ($taxType == 'percentage') {
                    $taxAmount = ($total_amount * $tax->percentage) / 100;
                    $disp_tax = format_currency($taxAmount, $currency_symbol) . '(' . $tax->percentage . '%)';
                } elseif ($taxType == 'amount') {
                    $taxAmount = $taxRate;
                    $disp_tax = format_currency($taxAmount, $currency_symbol);
                }
                // Return the calculated tax data
                return [
                    'taxAmount' => $taxAmount,
                    'taxType' => $taxType,
                    'dispTax' => $disp_tax,
                ];
            }
        }
        // Return empty data if tax_id is empty or tax data is not found
        return [
            'taxAmount' => 0,
            'taxType' => '',
            'dispTax' => '',
        ];
    }
}
if (!function_exists('processNotificationsSynchronously')) {
    function processNotificationsSynchronously($data, $recipients)
    {
        return app(\App\Services\NotificationService::class)->processNotificationsSynchronously($data, $recipients);
    }
}
if (!function_exists('processNotifications')) {
    function processNotifications($data, $recipients)
    {
        if (empty($recipients)) {
            return;
        }

        // Capture current context
        $workspaceId = getWorkspaceId();
        $auth = getAuthenticatedUser();
        $authId = $auth ? $auth->id : null;
        $authGuard = getGuardName();

        // Dispatch the job
        \App\Jobs\ProcessNotificationsJob::dispatch($data, $recipients, $workspaceId, $authId, $authGuard);
    }
}
if (!function_exists('sendPushNotification')) {
    function sendPushNotification($recipientModel, $data)
    {
        return app(\App\Services\NotificationService::class)->sendPushNotification($recipientModel, $data);
    }
}
if (!function_exists('sendEmailNotification')) {
    function sendEmailNotification($recipientModel, $data)
    {
        return app(\App\Services\NotificationService::class)->sendEmailNotification($recipientModel, $data);
    }
}
if (!function_exists('sendSMSNotification')) {
    function sendSMSNotification($data, $recipient)
    {
        return app(\App\Services\NotificationService::class)->sendSMSNotification($data, $recipient);
    }
}
if (!function_exists('getNotificationTemplate')) {
    function getNotificationTemplate($type, $emailOrSMS = 'email')
    {
        return app(\App\Services\NotificationService::class)->getNotificationTemplate($type, $emailOrSMS);
    }
}
if (!function_exists('send_sms')) {
    function send_sms($recipient, $itemData = NULL, $message = NULL)
    {
        return app(\App\Services\NotificationService::class)->send_sms($recipient, $itemData, $message);
    }
}
if (!function_exists('storeFcmToken')) {
    function storeFcmToken($recipientModel, $token)
    {
        // Check if the token is provided
        if (empty($token)) {
            return false; // No token to store
        }
        // Determine if the recipient is a User or Client
        $userId = null;
        $clientId = null;
        $guardName = getGuardName();
        if ($guardName == 'web') {
            $userId = $recipientModel->id; // Set user ID
        } elseif ($guardName == 'client') {
            $clientId = $recipientModel->id; // Set client ID
        }
        // Check if the token already exists for this user or client
        $query = FcmToken::where('fcm_token', $token);
        if ($userId) {
            $existingToken = $query->where('user_id', $userId)->first();
        } elseif ($clientId) {
            $existingToken = $query->where('client_id', $clientId)->first();
        }
        // If the token does not exist, save it
        if (!$existingToken) {
            FcmToken::create([
                'user_id' => $userId,
                'client_id' => $clientId,
                'fcm_token' => $token,
            ]);
        }
        return true; // Token stored successfully or already exists
    }
}
if (!function_exists('sendWhatsAppNotification')) {
    function sendWhatsAppNotification($recipient, $itemData = NULL, $message = NULL)
    {
        return app(\App\Services\NotificationService::class)->sendWhatsAppNotification($recipient, $itemData, $message);
    }
}
if (!function_exists('sendSlackNotification')) {
    function sendSlackNotification($recipient, $itemData = NULL, $message = NULL)
    {
        return app(\App\Services\NotificationService::class)->sendSlackNotification($recipient, $itemData, $message);
    }
}
if (!function_exists('getSlackUserIdByEmail')) {
    function getSlackUserIdByEmail($client, $email)
    {
        return app(\App\Services\NotificationService::class)->getSlackUserIdByEmail($client, $email);
    }
}
if (!function_exists('curl_sms')) {
    function curl_sms($url, $method = 'GET', $data = [], $headers = [])
    {
        return app(\App\Services\NotificationService::class)->curl_sms($url, $method, $data, $headers);
    }
}
if (!function_exists('parse_sms')) {
    function parse_sms($template, $phone, $msg, $country_code)
    {
        return app(\App\Services\NotificationService::class)->parse_sms($template, $phone, $msg, $country_code);
    }
}
if (!function_exists('get_message')) {
    function get_message($data, $recipient, $type = 'sms')
    {
        return app(\App\Services\NotificationService::class)->getMessage($data, $recipient, $type);
    }
}
if (!function_exists('format_budget')) {
    function format_budget($amount)
    {
        // Check if the input is numeric or can be converted to a numeric value.
        if (!is_numeric($amount)) {
            // If the input is not numeric, return null or handle the error as needed.
            return null;
        }
        // Remove non-numeric characters from the input string.
        $amount = preg_replace('/[^0-9.]/', '', $amount);
        // Convert the input to a float.
        $amount = (float) $amount;
        // Define suffixes for thousands, millions, etc.
        $suffixes = ['', 'K', 'M', 'B', 'T'];
        // Determine the appropriate suffix and divide the amount accordingly.
        $suffixIndex = 0;
        while ($amount >= 1000 && $suffixIndex < count($suffixes) - 1) {
            $amount /= 1000;
            $suffixIndex++;
        }
        // Format the amount with the determined suffix.
        return number_format($amount, 2) . $suffixes[$suffixIndex];
    }
}
if (!function_exists('canSetStatus')) {
    function canSetStatus($status)
    {
        $user = getAuthenticatedUser();
        $isAdminOrHasAllDataAccess = isAdminOrHasAllDataAccess();
        // Ensure the user and their first role exist
        $userRoleId = $user && $user->roles->isNotEmpty() ? $user->roles->first()->id : null;
        // Check if the user has permission for this status
        $hasPermission = $userRoleId && $status->roles->contains($userRoleId) || $isAdminOrHasAllDataAccess;
        return $hasPermission;
    }
}
if (!function_exists('checkPermission')) {
    function checkPermission($permission)
    {
        static $user = null;
        if ($user === null) {
            $user = getAuthenticatedUser();
        }
        return $user->can($permission);
    }
}
if (!function_exists('getUserPreferences')) {
    function getUserPreferences($table, $column = 'visible_columns', $userId = null)
    {
        if ($userId === null) {
            $userId = getAuthenticatedUser(true, true);
        }
        $result = UserClientPreference::where('user_id', $userId)
            ->where('table_name', $table)
            ->first();
        switch ($column) {
            case 'default_view':
                if ($table == 'projects') {
                    $views = [
                        'kanban' => 'projects/kanban',
                        'list' => 'projects/list',
                        'gantt-chart' => 'projects/gantt-chart',
                        'calendar' => 'projects/calendar-view',
                    ];
                    return $result && $result->default_view
                        ? ($views[$result->default_view] ?? 'projects')
                        : 'projects';
                } elseif ($table == 'tasks') {
                    return $result && $result->default_view ? (
                        $result->default_view == 'draggable' ? 'tasks/draggable' : (
                            $result->default_view == 'calendar' ? 'tasks/calendar' : 'tasks'
                        )
                    ) : 'tasks';
                } elseif ($table == 'meetings') {
                    return $result->default_view ?? 'list';
                } elseif ($table == 'leave_requests') {
                    return $result->default_view ?? 'list';
                } elseif ($table == 'activity_logs') {
                    return $result->default_view ?? 'list';
                } elseif ($table == 'leads') {
                    return $result->default_view ?? 'list';
                }
                break;
            case 'visible_columns':
                return $result && $result->visible_columns ? $result->visible_columns : [];
                break;
            case 'enabled_notifications':
                if ($result) {
                    if ($result->enabled_notifications === null) {
                        return null;
                    }
                    return json_decode($result->enabled_notifications, true);
                }
                return [];
                break;
                break;
            default:
                return null;
                break;
        }
    }
}
if (!function_exists('getOrdinalSuffix')) {
    function getOrdinalSuffix($number)
    {
        if (!in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return 'st';
                case 2:
                    return 'nd';
                case 3:
                    return 'rd';
            }
        }
        return 'th';
    }
}
if (!function_exists('getTitle')) {
    function getTitle($data, $recipient = NULL, $type = 'system')
    {
        return app(\App\Services\NotificationService::class)->getTitle($data, $recipient, $type);
    }
}
if (!function_exists('hasPrimaryWorkspace')) {
    function hasPrimaryWorkspace()
    {
        $primaryWorkspace = \App\Models\Workspace::where('is_primary', 1)->first();
        return $primaryWorkspace ? $primaryWorkspace->id : 0;
    }
}
if (!function_exists('getWorkspaceId')) {
    function getWorkspaceId()
    {
        $workspaceId = 0;
        $authenticatedUser = getAuthenticatedUser();
        if ($authenticatedUser) {
            if (session()->has('workspace_id')) {
                // dd(getAuthenticatedUser());
                $workspaceId = session('workspace_id'); // Retrieve workspace_id from session
            } else {
                $workspaceId = request()->header('workspace_id');
            }
        }
        return $workspaceId;
    }
}
if (!function_exists('getGuardName')) {
    function getGuardName()
    {
        static $guardName = null;
        // If the guard name is already determined, return it
        if ($guardName !== null) {
            return $guardName;
        }
        // Check the 'web' guard (users)
        if (Auth::guard('web')->check()) {
            $guardName = 'web';
        }
        // Check the 'client' guard (clients)
        elseif (Auth::guard('client')->check()) {
            $guardName = 'client';
        }
        // Check the 'sanctum' guard (API tokens)
        elseif (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            // Determine if the sanctum user is a user or a client
            if ($user instanceof \App\Models\User) {
                $guardName = 'web';
            } elseif ($user instanceof \App\Models\Client) {
                $guardName = 'client';
            }
        }
        return $guardName;
    }
}
if (!function_exists('formatProject')) {
    function formatProject($project)
    {
        return app(\App\Services\FormatterService::class)->formatProject($project);
    }
}
if (!function_exists('formatTask')) {
    function formatTask($task)
    {
        return app(\App\Services\FormatterService::class)->formatTask($task);
    }
}
if (!function_exists('formatWorkspace')) {
    function formatWorkspace($workspace)
    {
        return app(\App\Services\FormatterService::class)->formatWorkspace($workspace);
    }
}
// formating email templates for api
if (!function_exists('formatEmailTemplate')) {
    function formatEmailTemplate($template)
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'subject' => $template->subject,
            'body' => $template->body,
            'workspace_id' => $template->workspace_id,
            'placeholders' => $template->placeholders,
            'created_at' => format_date($template->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($template->updated_at, to_format: 'Y-m-d'),
        ];
    }
}
/// formating sent email for api
if (!function_exists('formatEmailSend')) {
    function formatEmailSend($email)
    {
        return [
            'id' => $email->id,
            'user_id' => $email->user_id,
            'email_template_id' => $email->email_template_id,
            'workspace_id' => $email->workspace_id,
            'to_email' => $email->to_email,
            'subject' => $email->subject,
            'body' => $email->body,
            'placeholders' => $email->placeholders ?? null,
            'status' => $email->status,
            'scheduled_at' => $email->scheduled_at ? format_date($email->scheduled_at, to_format: 'Y-m-d H:i:s') : null,
            'attachments' => $email->getMedia('email-media')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ];
            })->toArray(),
            'created_at' => format_date($email->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($email->updated_at, to_format: 'Y-m-d'),
        ];
    }
}
// formating candidates for api
if (!function_exists('formatCandidate')) {
    function formatCandidate($candidate)
    {
        return [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'position' => $candidate->position,
            'source' => $candidate->source,
            'status' => [
                'id' => $candidate->status_id,
                'name' => $candidate->status ? $candidate->status->name : null,
            ],
            'attachments' => $candidate->getMedia('candidate-media')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => round($media->size / 1024, 2) . ' KB',
                    'mime_type' => $media->mime_type,
                    'uploaded_date' => format_date($media->created_at)
                ];
            })->toArray(),
            'interviews' => $candidate->interviews->map(function ($interview) {
                return [
                    'id' => $interview->id,
                    'candidate_name' => $interview->candidate->name,
                    'interviewer' => $interview->interviewer->first_name . ' ' . $interview->interviewer->last_name,
                    'round' => $interview->round,
                    'scheduled_at' => $interview->scheduled_at,
                    'status' => $interview->status,
                    'location' => $interview->location,
                    'mode' => $interview->mode,
                    'created_at' => format_date($interview->created_at, to_format: 'Y-m-d'),
                    'updated_at' => format_date($interview->updated_at, to_format: 'Y-m-d'),
                ];
            }),
            'created_at' => format_date($candidate->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($candidate->updated_at, to_format: 'Y-m-d'),
        ];
    }
}
if (!function_exists('formatCandidateStuses')) {
    function formatCandidateStatus($status)
    {
        return [
            'id' => $status->id,
            'name' => $status->name,
            'order' => $status->order,
            'color' => $status->color,
            'created_at' => format_date($status->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($status->updated_at, to_format: 'Y-m-d'),
            'can_edit' => checkPermission('edit_candidate_status'),
            'can_delete' => checkPermission('delete_candidate_status'),
        ];
    }
}
if (!function_exists('formatInterview')) {
    function formatInterview($interview)
    {
        return [
            'id' => $interview->id,
            'candidate_id' => $interview->candidate->id,
            'candidate_name' => $interview->candidate->name,
            'interviewer_id' => $interview->interviewer->id,
            'interviewer_name' => $interview->interviewer->first_name  . " " . $interview->interviewer->last_name,
            'round' => $interview->round,
            'scheduled_at' => format_date($interview->scheduled_at, true, to_format: 'Y-m-d'),
            'mode' => $interview->mode,
            'location' => $interview->location,
            'status' => $interview->status,
        ];
    }
}
if (!function_exists('formatMeeting')) {
    function formatMeeting($meeting)
    {
        return app(\App\Services\FormatterService::class)->formatMeeting($meeting);
    }
}
if (!function_exists('formatNotification')) {
    function formatNotification($notification)
    {
        return app(\App\Services\FormatterService::class)->formatNotification($notification);
    }
}
if (!function_exists('formatLeaveRequest')) {
    function formatLeaveRequest($leaveRequest)
    {
        $leaveRequest = LeaveRequest::select(
            'leave_requests.*',
            'users.photo AS user_photo',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) AS user_name'),
            DB::raw('CONCAT(action_users.first_name, " ", action_users.last_name) AS action_by_name'),
            'leave_requests.action_by as action_by_id'
        )
            ->leftJoin('users', 'leave_requests.user_id', '=', 'users.id')
            ->leftJoin('users AS action_users', 'leave_requests.action_by', '=', 'action_users.id')
            ->where('leave_requests.workspace_id', getWorkspaceId())
            ->find($leaveRequest->id);
        // Calculate the duration in hours if both from_time and to_time are provided
        $fromDate = Carbon::parse($leaveRequest->from_date);
        $toDate = Carbon::parse($leaveRequest->to_date);
        $fromDateDayOfWeek = $fromDate->format('D');
        $toDateDayOfWeek = $toDate->format('D');
        if ($leaveRequest->from_time && $leaveRequest->to_time) {
            $duration = 0;
            // Loop through each day
            while ($fromDate->lessThanOrEqualTo($toDate)) {
                // Create Carbon instances for the start and end times of the leave request for the current day
                $fromDateTime = Carbon::parse($fromDate->toDateString() . ' ' . $leaveRequest->from_time);
                $toDateTime = Carbon::parse($fromDate->toDateString() . ' ' . $leaveRequest->to_time);
                // Calculate the duration for the current day and add it to the total duration
                $duration += $fromDateTime->diffInMinutes($toDateTime) / 60; // Duration in hours
                // Move to the next day
                $fromDate->addDay();
            }
        } else {
            // Calculate the inclusive duration in days
            $duration = $fromDate->diffInDays($toDate) + 1;
        }
        if ($leaveRequest->visible_to_all == 1) {
            $visibleTo = [];
        } else {
            $visibleTo = $leaveRequest->visibleToUsers->isEmpty()
                ? null
                : $leaveRequest->visibleToUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                    'photo' => $user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')
                    ];
                });
        }
        $visibleToIds = $leaveRequest->visibleToUsers->pluck('id')->toArray();
        return [
            'id' => $leaveRequest->id,
            'user_id' => $leaveRequest->user_id,
            'user_name' => $leaveRequest->user_name,
            'user_photo' => $leaveRequest->user_photo ? asset('storage/' . $leaveRequest->user_photo) : asset('storage/photos/no-image.jpg'),
            'action_by' => $leaveRequest->action_by_name,
            'action_by_id' => $leaveRequest->action_by_id,
            'from_date' => $leaveRequest->from_date,
            'from_time' => Carbon::parse($leaveRequest->from_time)->format('h:i A'),
            'to_date' => $leaveRequest->to_date,
            'to_time' => Carbon::parse($leaveRequest->to_time)->format('h:i A'),
            'type' => $leaveRequest->from_time && $leaveRequest->to_time ? 'Partial' : 'Full',
            'leaveVisibleToAll' => $leaveRequest->visible_to_all ? 'on' : 'off',
            'partialLeave' => $leaveRequest->from_time && $leaveRequest->to_time ? 'on' : 'off',
            'duration' => ($leaveRequest->from_time && $leaveRequest->to_time)
                ? (string) $duration
                : (string) number_format($duration, 2),
            'reason' => $leaveRequest->reason,
            'comment' => $leaveRequest->comment,
            'status' => $leaveRequest->status,
            'visible_to' => $visibleTo ?? [],
            'visible_to_ids' => $visibleToIds ?? [],
            'created_at' => format_date($leaveRequest->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($leaveRequest->updated_at, to_format: 'Y-m-d'),
        ];
    }
}
if (!function_exists('formatUser')) {
    function formatUser($user, $isSignup = false)
    {
        return app(\App\Services\FormatterService::class)->formatUser($user, $isSignup);
    }
}
if (!function_exists('formatClient')) {
    function formatClient($client, $isSignup = false)
    {
        return app(\App\Services\FormatterService::class)->formatClient($client, $isSignup);
    }
}
if (!function_exists('formatNote')) {
    function formatNote($note)
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'color' => $note->color,
            'type' => $note->note_type,
            'drawing_data' => $note->drawing_data,
            'description' => $note->description,
            'workspace_id' => $note->workspace_id,
            'creator_id' => $note->creator_id,
            'created_at' => format_date($note->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($note->updated_at, to_format: 'Y-m-d'),
        ];
    }
}
if (!function_exists('formatTodo')) {
    function formatTodo($todo)
    {
        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'priority' => $todo->priority,
            'is_completed' => $todo->is_completed,
            'created_at' => format_date($todo->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($todo->updated_at, to_format: 'Y-m-d'),
        ];
    }
}
if (!function_exists('formatRole')) {
    function formatRole($role)
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name'),
            'created_at' => format_date($role->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($role->updated_at, to_format: 'Y-m-d'),
        ];
    }
}
if (!function_exists('validate_date_format_and_order')) {
    /**
     * Validate if a date matches the format specified and ensure the start date is before or equal to the end date.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $format
     * @param string $startDateLabel
     * @param string $endDateLabel
     * @param string $startDateKey
     * @param string $endDateKey
     * @return array
     */
    function validate_date_format_and_order(
        $startDate,
        $endDate,
        $format = null,
        $startDateLabel = 'start date',
        $endDateLabel = 'end date',
        $startDateKey = 'start_date',
        $endDateKey = 'end_date'
    ) {
        $matchFormat = $format ?? get_php_date_time_format();
        $errors = [];
        // Validate start date format
        if ($startDate && !validate_date_format($startDate, $matchFormat)) {
            $errors[$startDateKey][] = 'The ' . $startDateLabel . ' does not follow the format set in settings.';
        }
        // Validate end date format
        if ($endDate && !validate_date_format($endDate, $matchFormat)) {
            $errors[$endDateKey][] = 'The ' . $endDateLabel . ' does not follow the format set in settings.';
        }
        // Validate date order
        if ($startDate && $endDate) {
            $parsedStartDate = \DateTime::createFromFormat($matchFormat, $startDate);
            $parsedEndDate = \DateTime::createFromFormat($matchFormat, $endDate);
            if ($parsedStartDate && $parsedEndDate && $parsedStartDate > $parsedEndDate) {
                $errors[$startDateKey][] = 'The ' . $startDateLabel . ' must be before or equal to the ' . $endDateLabel . '.';
            }
        }
        return $errors;
    }
}
if (!function_exists('validate_date_format')) {
    /**
     * Validate if a date matches the format specified in settings.
     *
     * @param string $date
     * @param string|null $format
     * @return bool
     */
    function validate_date_format($date, $format = null)
    {
        $format = $format ?? get_php_date_time_format();
        $parsedDate = \DateTime::createFromFormat($format, $date);
        return $parsedDate && $parsedDate->format($format) === $date;
    }
}
if (!function_exists('validate_currency_format')) {
    function validate_currency_format($value, $label)
    {
        $regex = '/^(?:\d{1,3}(?:,\d{3})*|\d+)(\.\d+)?$/';
        if (!preg_match($regex, $value)) {
            return "The $label format is invalid.";
        }
        return null;
    }
}
if (!function_exists('formatApiResponse')) {
    function formatApiResponse($error, $message, array $optionalParams = [], $statusCode = 200)
    {
        $response = [
            'error' => $error,
            'message' => $message,
        ];
        // Merge optional parameters into the response if they are provided
        $response = array_merge($response, $optionalParams);
        return response()->json($response, $statusCode);
    }
}
if (!function_exists('isSanctumAuth')) {
    function isSanctumAuth()
    {
        return Auth::guard('web')->check() || Auth::guard('client')->check() ? false : true;
    }
}
if (!function_exists('formatApiValidationError')) {
    function formatApiValidationError($isApi, $errors, $defaultMessage = 'Validation errors occurred')
    {
        if ($isApi) {
            $messages = collect($errors)->flatten()->implode("\n");
            return response()->json([
                'error' => true,
                'message' => $messages,
            ], 422);
        } else {
            return response()->json([
                'error' => true,
                'message' => $defaultMessage,
                'errors' => $errors,
            ], 422);
        }
    }
}
if (!function_exists('getMimeTypeMap')) {
    function getMimeTypeMap()
    {
        return [
            // Image MIME Types
            '.jpg' => 'image/jpeg',
            '.jpeg' => 'image/jpeg',
            '.png' => 'image/png',
            '.gif' => 'image/gif',
            '.bmp' => 'image/bmp',
            '.svg' => 'image/svg+xml',
            '.webp' => 'image/webp',
            '.tiff' => 'image/tiff',
            '.ico' => 'image/vnd.microsoft.icon',
            '.psd' => 'image/vnd.adobe.photoshop',
            '.heic' => 'image/heic',
            // Document MIME Types
            '.pdf' => 'application/pdf',
            '.doc' => 'application/msword',
            '.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            '.xls' => 'application/vnd.ms-excel',
            '.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            '.ppt' => 'application/vnd.ms-powerpoint',
            '.pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            '.txt' => 'text/plain',
            '.rtf' => 'application/rtf',
            '.odt' => 'application/vnd.oasis.opendocument.text',
            '.ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            '.odp' => 'application/vnd.oasis.opendocument.presentation',
            '.csv' => 'text/csv',
            '.md' => 'text/markdown',
            // Archive MIME Types
            '.zip' => 'application/zip',
            '.rar' => 'application/x-rar-compressed',
            '.7z' => 'application/x-7z-compressed',
            '.tar' => 'application/x-tar',
            '.gz' => 'application/gzip',
            '.bz2' => 'application/x-bzip2',
            '.xz' => 'application/x-xz',
            '.iso' => 'application/x-iso9660-image',
            // Audio MIME Types
            '.mp3' => 'audio/mpeg',
            '.wav' => 'audio/wav',
            '.ogg' => 'audio/ogg',
            '.flac' => 'audio/flac',
            '.aac' => 'audio/aac',
            '.m4a' => 'audio/x-m4a',
            '.wma' => 'audio/x-ms-wma',
            '.aiff' => 'audio/aiff',
            '.opus' => 'audio/opus',
            '.amr' => 'audio/amr',
            // Video MIME Types
            '.mp4' => 'video/mp4',
            '.avi' => 'video/x-msvideo',
            '.mov' => 'video/quicktime',
            '.wmv' => 'video/x-ms-wmv',
            '.flv' => 'video/x-flv',
            '.mkv' => 'video/x-matroska',
            '.webm' => 'video/webm',
            '.3gp' => 'video/3gpp',
            '.m4v' => 'video/x-m4v',
            '.mpg' => 'video/mpeg',
            '.mpeg' => 'video/mpeg',
            // Executable MIME Types
            '.exe' => 'application/vnd.microsoft.portable-executable',
            '.bat' => 'application/x-msdownload',
            '.sh' => 'application/x-sh',
            '.bin' => 'application/octet-stream',
            '.msi' => 'application/x-msi',
            '.cmd' => 'application/x-msdownload',
            '.jar' => 'application/java-archive',
            '.apk' => 'application/vnd.android.package-archive',
            // Code MIME Types
            '.html' => 'text/html',
            '.htm' => 'text/html',
            '.css' => 'text/css',
            '.js' => 'application/javascript',
            '.php' => 'application/x-httpd-php',
            '.java' => 'text/x-java-source',
            '.py' => 'text/x-python',
            '.rb' => 'application/x-ruby',
            '.pl' => 'application/x-perl',
            '.cpp' => 'text/x-c++',
            '.c' => 'text/x-c',
            '.h' => 'text/x-c',
            '.cs' => 'text/x-csharp',
            '.xml' => 'application/xml',
            '.json' => 'application/json',
            '.yml' => 'text/yaml',
            '.sql' => 'application/sql',
            // Font MIME Types
            '.ttf' => 'font/ttf',
            '.otf' => 'font/otf',
            '.woff' => 'font/woff',
            '.woff2' => 'font/woff2',
            '.eot' => 'application/vnd.ms-fontobject',
            // Miscellaneous MIME Types
            '.ics' => 'text/calendar',
            '.vcf' => 'text/x-vcard',
            '.swf' => 'application/x-shockwave-flash',
            '.epub' => 'application/epub+zip',
            '.mobi' => 'application/x-mobipocket-ebook',
            '.azw' => 'application/vnd.amazon.ebook',
            '.bak' => 'application/octet-stream'
        ];
    }
}
if (!function_exists('getMainAdminId')) {
    function getMainAdminId()
    {
        $mainAdminId = DB::table('model_has_roles')
            ->where('role_id', 1)
            ->orderBy('model_id')
            ->value('model_id');
        return $mainAdminId;
    }
}
if (!function_exists('getMenus')) {
    function getMenus()
    {
        return app(\App\Services\MenuService::class)->getMenus();
    }
}
if (!function_exists('getAllPermissions')) {
    /**
     * Get an array of all defined permissions.
     *
     * @return array
     */
    function getAllPermissions()
    {
        $permissionsConfig = config('taskhub.permissions'); // Fetch permissions from config
        $allPermissions = [];
        foreach ($permissionsConfig as $category => $permissions) {
            $allPermissions = array_merge($allPermissions, $permissions);
        }
        return $allPermissions;
    }
}
/**
 * Replace plain @mentions in the content with HTML links to the user's profile.
 *
 * @param string $content
 * @return string
 */
if (!function_exists('replaceUserMentionsWithLinks')) {
    function replaceUserMentionsWithLinks($content)
    {
        // Find all @mentions in the content
        preg_match_all('/@([A-Za-z0-9]+\s[A-Za-z0-9]+)/', $content, $matches);
        // Initialize modified content
        $modifiedContent = $content;
        $mentionedUserIds = [];
        $mentionedClientIds = [];
        $workspaceId = getWorkspaceId();
        // Check if any matches were found
        if (!empty($matches[1])) {
            foreach ($matches[1] as $fullName) {
                // Try to find the user by their full name (first_name + last_name)
                $user = User::where(DB::raw("CONCAT(first_name, ' ', last_name)"), '=', $fullName)
                    ->whereHas('workspaces', function ($query) use ($workspaceId) {
                        $query->where('workspaces.id', $workspaceId);
                    })
                    ->first();
                if ($user) {
                    // Add user ID to the list of mentioned user IDs
                    $mentionedUserIds[] = $user->id;
                    // Check permission for managing users
                    // if (checkPermission('manage_users')) {
                    // Create a profile link for the mentioned user
                    $mentionLink = '<a href="' . route('users.profile', ['id' => $user->id]) . '">@' . $fullName . '</a>';
                    // } else {
                    //     // Non-clickable text
                    //     $mentionLink = '@' . $fullName;
                    // }
                    // Replace the plain @mention with the linked or non-clickable version
                    $modifiedContent = str_replace('@' . $fullName, $mentionLink, $modifiedContent);
                } else {
                    // If user not found, check if it's a client
                    $client = Client::where(DB::raw("CONCAT(clients.first_name, ' ', clients.last_name)"), '=', $fullName)
                        ->whereHas('workspaces', function ($query) use ($workspaceId) {
                            $query->where('workspaces.id', $workspaceId);
                        })
                        ->first();
                    if ($client) {
                        // Add client ID to the list of mentioned client IDs
                        $mentionedClientIds[] = $client->id;
                        // Check permission for managing clients
                        // if (checkPermission('manage_clients')) {
                        // Create a profile link for the mentioned client
                        $mentionLink = '<a href="' . route('clients.profile', ['id' => $client->id]) . '">@' . $fullName . '</a>';
                        // } else {
                        //     // Non-clickable text
                        //     $mentionLink = '@' . $fullName;
                        // }
                        // Replace the plain @mention with the linked or non-clickable version
                        $modifiedContent = str_replace('@' . $fullName, $mentionLink, $modifiedContent);
                    }
                }
            }
        }
        // Return the modified content along with both mentioned user and client IDs
        return [$modifiedContent, $mentionedUserIds, $mentionedClientIds];
    }
}
if (!function_exists('sendMentionNotification')) {
    function sendMentionNotification($comment, $mentionedUserIds, $workspaceId, $currentUserId, $mentionedClientIds = [])
    {
        // Ensure mentioned user IDs are unique
        $mentionedUserIds = array_unique($mentionedUserIds);
        // Ensure mentioned client IDs are unique
        $mentionedClientIds = array_unique($mentionedClientIds);
        // Initialize module variables
        $moduleType = '';
        $url = '';
        // dd($comment->commentable_type);
        switch ($comment->commentable_type) {
            case 'App\Models\Task':
                $moduleType = 'task';
                $url = route('tasks.info', ['id' => $comment->commentable_id]) . '#navs-top-discussions';
                break;
            case 'App\Models\Project':
                $moduleType = 'project';
                $url = route('projects.info', ['id' => $comment->commentable_id]) . '#navs-top-discussions';
                break;
            default:
                $moduleType = '';
                break;
        }
        $module = [];
        if ($moduleType) {
            switch ($moduleType) {
                case 'task':
                    $module = Task::find($comment->commentable_id);
                    break;
                case 'project':
                    $module = Project::find($comment->commentable_id);
                    break;
                default:
                    break;
            }
        }
        // Get the authenticated user who is sending the notification
        $authUser = getAuthenticatedUser();
        // Create the notification
        $notification = Notification::create([
            'workspace_id' => $workspaceId,
            'from_id' => 'u_' . $currentUserId,
            'type' => $moduleType . '_comment_mention',
            'type_id' => $module->id,
            'action' => 'mentioned',
            'title' => 'You were mentioned in a comment',
            'message' => $authUser->first_name . ' ' . $authUser->last_name . ' mentioned you in ' . ucfirst($moduleType) . ' <a href="' . $url . '">' . $module->title . '</a>.',
        ]);
        // Attach mentioned users to the notification
        foreach ($mentionedUserIds as $userId) {
            $notification->users()->attach($userId);
        }
        // Attach mentioned clients to the notification
        foreach ($mentionedClientIds as $clientId) {
            $client = Client::find($clientId);
            if ($client) {
                $notification->clients()->attach($clientId);
            }
        }
    }
}
if (!function_exists('get_file_settings')) {
    function get_file_settings()
    {
        $general_settings = get_settings('general_settings');
        // Remove spaces from allowed file types
        $allowed_file_types = isset($general_settings['allowed_file_types'])
            ? str_replace(' ', '', $general_settings['allowed_file_types'])
            : '.png,.jpg,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt';
        return [
            'allowed_file_types' => $allowed_file_types,
            'max_files_allowed' => isset($general_settings['max_files_allowed'])
                ? $general_settings['max_files_allowed']
                : 10,
        ];
    }
}
if (!function_exists('formatUserHtml')) {
    function formatUserHtml($user)
    {
        return app(\App\Services\FormatterService::class)->formatUserHtml($user);
    }
}
if (!function_exists('formatClientHtml')) {
    function formatClientHtml($client)
    {
        return app(\App\Services\FormatterService::class)->formatClientHtml($client);
    }
}
if (!function_exists('getFavoriteStatus')) {
    function getFavoriteStatus($id, $model = \App\Models\Project::class)
    {
        // Ensure the model is valid and exists
        if (!class_exists($model) || !$model::find($id)) {
            return false; // Return false if the model class doesn't exist or the specific entity doesn't exist
        }
        // Get the authenticated user (either a User or a Client)
        $authUser = getAuthenticatedUser();
        // Get the favorite based on the provided model (e.g., Project, Task, etc.)
        $isFavorited = $authUser->favorites()
            ->where('favoritable_type', $model)
            ->where('favoritable_id', $id)
            ->exists();
        return (int) $isFavorited;
    }
}
if (!function_exists('getPinnedStatus')) {
    function getPinnedStatus($id, $model = \App\Models\Project::class)
    {
        // Ensure the model is valid and exists
        if (!class_exists($model) || !$model::find($id)) {
            return false; // Return false if the model class doesn't exist or the specific entity doesn't exist
        }
        // Get the authenticated user (either a User or a Client)
        $authUser = getAuthenticatedUser();
        // Get the pinned status based on the provided model (e.g., Project, Task, etc.)
        $isPinned = $authUser->pinned()
            ->where('pinnable_type', $model)
            ->where('pinnable_id', $id)
            ->exists();
        return (int) $isPinned;
    }
}
if (!function_exists('logActivity')) {
    function logActivity($type, $typeId, $title, $operation = 'created', $parentId = null, $parentType = null)
    {
        // Retrieve necessary values once
        $authenticatedUser = getAuthenticatedUser();
        $workspaceId = getWorkspaceId();
        $guardName = getGuardName();
        // Construct the actor details
        $actorName = $authenticatedUser->first_name . ' ' . $authenticatedUser->last_name;
        $actorId = $authenticatedUser->id;
        $actorType = $guardName == 'web' ? 'user' : 'client';
        // Construct the activity message
        $message = trim($actorName) . ' ' . trim($operation) . ' ' . trim($type) . ' ' . trim($title);
        // Prepare the log data
        $logData = [
            'workspace_id' => $workspaceId,
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'type_id' => $typeId,
            'type' => $type,
            'activity' => $operation,
            'message' => $message,
        ];
        // Add parent information if available
        if ($parentId) {
            $logData['parent_type_id'] = $parentId;
        }
        if ($parentType) {
            $logData['parent_type'] = $parentType;
        }
        // Create the activity log entry
        ActivityLog::create($logData);
    }
}
// Function for sending reminders for tasks or birthday or work anniversary
if (!function_exists('sendReminderNotification')) {
    /**
     * Sends reminder notifications to the given recipients based on the given data.
     *
     * @param array $data The reminder data, must contain the type of reminder.
     * @param array $recipients The recipients of the notification, must contain the user or client IDs.
     * @return void
     */
    function sendReminderNotification($data, $recipients)
    {
        Log::info('Sending reminder notification to: ' . json_encode($recipients, JSON_PRETTY_PRINT) . 'With data: ' . json_encode($data, JSON_PRETTY_PRINT));
        if (empty($recipients)) {
            return;
        }
        // Define notification types
        $notificationTypes = ['task_reminder', 'project_reminder', 'leave_request_reminder', 'recurring_task', 'todo_reminder'];
        Log::debug('Checking notification type', ['type' => $data['type'], 'valid_types' => $notificationTypes]);
        // Get notification template based on the type
        $template = getNotificationTemplate($data['type'], 'system');
        if (!$template || $template->status !== 0) {
            $notification = createNotification($data);
        }
        // Process each recipient
        foreach (array_unique($recipients) as $recipient) {
            Log::info('Processing recipient', ['recipient_id' => $recipient]);
            $recipientModel = getRecipientModel($recipient);
            if ($recipientModel) {
                Log::debug('Found recipient model', [
                    'recipient_type' => get_class($recipientModel),
                    'recipient_id' => $recipientModel->id
                ]);
                handleRecipientNotification($recipientModel, $notification, $template, $data, $notificationTypes);
            }
        }
    }
    /**
     * Creates a new notification from the given data.
     *
     * @param array $data An associative array containing the notification details,
     *                    including the 'type', 'type_id', and 'action'.
     * @return \App\Models\Notification The newly created notification instance.
     */
    function createNotification($data)
    {
        return Notification::create(
            [
                'workspace_id' => $data['workspace_id'],
                'from_id' => $data['from_id'],
                'type' => $data['type'],
                'type_id' => $data['type_id'],
                'action' => $data['action'],
                'title' => getTitle($data),
                'message' => get_message($data, null, 'system'),
            ]
        );
    }
    /**
     * Given a recipient identifier, returns the corresponding model instance.
     *
     * A recipient identifier is a string that starts with either 'u_' for a user or
     * 'c_' for a client, followed by the numeric identifier of the user or client.
     * For example, 'u_1' refers to a user with identifier 1, and 'c_2' refers to a
     * client with identifier 2.
     *
     * @param string $recipient The recipient identifier.
     * @return \App\Models\User|\App\Models\Client|null The recipient model instance, or null if not found.
     */
    function getRecipientModel($recipient)
    {
        $recipientId = substr($recipient, 2);
        if (substr($recipient, 0, 2) === 'u_') {
            return User::find($recipientId);
        } elseif (substr($recipient, 0, 2) === 'c_') {
            return Client::find($recipientId);
        }
        return null;
    }
    /**
     * Handles a notification for a recipient based on their notification preferences.
     *
     * This function takes a recipient model, a notification, a template, data about the
     * notification, and an array of notification types. It checks the recipient's
     * preferences for the notification types and sends notifications accordingly.
     * If the notification is already attached to the recipient, it will not be attached again.
     *
     * @param mixed $recipientModel The recipient model to send the notification to.
     * @param mixed $notification The notification to be sent.
     * @param mixed $template The template to use for the notification.
     * @param array $data An associative array containing details about the notification.
     * @param array $notificationTypes An array of notification types to check for.
     */
    function handleRecipientNotification($recipientModel, $notification, $template, $data, $notificationTypes)
    {
        Log::info('Handling recipient notification', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type']
        ]);
        $enabledNotifications = getUserPreferences('notification_preference', 'enabled_notifications', 'u_' . $recipientModel->id);
        // Attach the notification to the recipient
        attachNotificationIfNeeded($recipientModel, $notification, $template, $enabledNotifications, $data);
        Log::info('Starting notification delivery process', [
            'recipient_id' => $recipientModel->id,
            'notification_types' => $notificationTypes,
            'enabled_notifications' => $enabledNotifications
        ]);
        // Send notifications based on preferences
        sendEmailIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
        sendSMSIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
        sendWhatsAppIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
        sendSlackIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
    }
    /**
     * Attach a notification to the recipient if the recipient has enabled system notifications for the given type
     * of notification and the notification template is not found or is not enabled.
     *
     * @param mixed $recipientModel The recipient model (User or Client) to which the notification should be attached.
     * @param Notification $notification The notification to be attached to the recipient.
     * @param Template $template The notification template to be checked for enabled status.
     * @param array $enabledNotifications An array of enabled notification types for the recipient.
     * @param array $data The data for the notification, including the type of notification.
     */
    function attachNotificationIfNeeded($recipientModel, $notification, $template, $enabledNotifications, $data)
    {
        Log::debug('Checking if notification needs to be attached', [
            'recipient_id' => $recipientModel->id,
            'notification_id' => $notification ? $notification->id : null,
            'template_exists' => (bool) $template,
            'template_status' => $template ? $template->status : null
        ]);
        if (!$template || $template->status !== 0) {
            if (is_array($enabledNotifications) && (empty($enabledNotifications) || in_array('system_' . $data['type'], $enabledNotifications))) {
                $recipientModel->notifications()->attach($notification->id);
            }
        }
    }
    /**
     * Send an email notification if the recipient has enabled email notifications for the given type of notification.
     *
     * @param mixed $recipientModel The recipient model (User or Client) to which the notification should be sent.
     * @param array $enabledNotifications An array of enabled notification types for the recipient.
     * @param array $data The notification data.
     * @param array $notificationTypes An array of notification types for which email notifications should be sent.
     * @return void
     */
    function sendEmailIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {
        Log::debug('Checking email notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'email_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'email_' . $data['type'])) {
            try {
                sendEmailNotification($recipientModel, $data);
            } catch (\Exception $e) {
                Log::error('Email Notification Error: ' . $e->getMessage());
            }
        }
    }
    /**
     * Send SMS notification if enabled.
     *
     * This function sends an SMS notification to the given recipient if the
     * notification type is enabled in the recipient's preferences.
     *
     * @param  \App\Models\User|\App\Models\Client  $recipientModel
     * @param  array  $enabledNotifications
     * @param  array  $data
     * @param  array  $notificationTypes
     * @return void
     */
    function sendSMSIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {
        Log::debug('Checking SMS notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'sms_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'sms_' . $data['type'])) {
            try {
                sendSMSNotification($data, $recipientModel);
            } catch (\Exception $e) {
                Log::error('SMS Notification Error: ' . $e->getMessage());
            }
        }
    }
    /**
     * Send WhatsApp notification if enabled.
     *
     * This function sends a WhatsApp notification to the given recipient if the
     * notification type is enabled in the recipient's preferences.
     *
     * @param  \App\Models\User|\App\Models\Client  $recipientModel
     * @param  array  $enabledNotifications
     * @param  array  $data
     * @param  array  $notificationTypes
     * @return void
     */
    function sendWhatsAppIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {
        Log::debug('Checking WhatsApp notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'whatsapp_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'whatsapp_' . $data['type'])) {
            try {
                sendWhatsAppNotification($recipientModel, $data);
            } catch (\Exception $e) {
                Log::error('WhatsApp Notification Error: ' . $e->getMessage());
            }
        }
    }
    /**
     * Send a Slack notification if the recipient has enabled Slack notifications for the given type.
     *
     * @param User|Client $recipientModel The recipient model to send the notification to.
     * @param array $enabledNotifications An array of enabled notification types.
     * @param array $data An associative array containing the notification details,
     *                    including the 'type', 'type_id', and 'action'.
     * @param array $notificationTypes An array of notification types.
     */
    function sendSlackIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {
        Log::debug('Checking Slack notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'slack_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'slack_' . $data['type'])) {
            try {
                sendSlackNotification($recipientModel, $data);
            } catch (\Exception $e) {
                Log::error('Slack Notification Error: ' . $e->getMessage());
            }
        }
    }
    /**
     * Check if a notification type is enabled for a user/client.
     *
     * @param array $enabledNotifications An array of enabled notification types.
     * @param string $type The notification type to check.
     * @return bool True if the notification type is enabled.
     */
    function isNotificationEnabled($enabledNotifications, $type)
    {
        return is_array($enabledNotifications) && (empty($enabledNotifications) || in_array($type, $enabledNotifications));
    }
}
if (!function_exists('getDefaultStatus')) {
    /**
     * Get the default status ID based on the given status name.
     *
     * @param string $statusName
     * @return object|null
     */
    function getDefaultStatus(string $statusName): ?object
    {
        // Fetch the default status using the Statuses model
        $status = Status::where('title', $statusName)
            ->where('is_default', 1) // Assuming there's an 'is_default' column
            ->first();
        // Return the ID if found, or null
        return $status ? $status : null;
    }
}
if (!function_exists('getDefaultRoute')) {
    function getDefaultRoute($type)
    {
        $defaultView = getUserPreferences($type, 'default_view');
        switch ($type) {
            case 'meetings':
                return $defaultView === 'calendar' ? route('meetings.calendar-view') : route('meetings.index');
            case 'leave_requests':
                return $defaultView === 'calendar' ? route('leave-requests.calendar') : route('leave_requests.index');
            case 'activity_logs':
                return $defaultView === 'calendar' ? route('activity_log.calendar_view') : route('activity_log.index');
            case 'leads':
                return $defaultView === 'kanban' ? route('leads.kanban_view') : route('leads.index');
            default:
                return route('dashboard'); // Fallback route to avoid returning null
        }
    }
}
if (!function_exists('generateActivityUrl')) {
    function generateActivityUrl($activity)
    {
        $base_url = url('/');
        // Mapping of singular types to correct plural forms
        $pluralMapping = [
            'project' => 'projects',
            'task' => 'tasks',
            'media' => 'media',
            'comment' => 'comments',
            'milestone' => 'milestones',
            'invoice' => 'estimates-invoices',
            'estimate' => 'estimates-invoices',
            'time-tracker' => 'time-tracker',
            'leave-request' => 'leave-requests',
            'client' => 'clients',
            'user' => 'users',
            'expense' => 'expenses',
            'expense-type' => 'expenses/expense-types',
            'item' => 'items',
            'payment' => 'payments',
            'payment-method' => 'payment-methods',
            'tax' => 'taxes',
            'unit' => 'units',
            'contract' => 'contracts',
            'todo' => 'todos',
            'contract-type' => 'contracts/contract-types',
            'payslip' => 'payslips',
            'allowance' => 'allowances',
            'deduction' => 'deductions',
            'tag' => 'tags/manage',
            'status' => 'status/manage',
            'priority' => 'priority/manage',
            'workspace' => 'workspaces',
            'note' => 'notes',
            'meeting' => 'meetings',
        ];
        // Convert type to lowercase and replace spaces with dashes
        $type = strtolower(trim($activity['type']));
        $type = str_replace(' ', '-', $type); // Fix "Contract type" → "contract-type"
        $parentType = isset($activity['parent_type']) ? strtolower(trim($activity['parent_type'])) : '';
        $parentType = str_replace(' ', '-', $parentType);
        // Ensure plural form using mapping or fallback to Str::plural()
        $pluralType = $pluralMapping[$type] ?? Str::plural($type);
        $pluralParentType = $pluralMapping[$parentType] ?? Str::plural($parentType);
        // Define URL structure for each type
        $urlPatterns = [
            'projects' => "/projects/information/{id}",
            'tasks' => "/tasks/information/{id}",
            'media' => "/{parent_type}/information/{parent_id}",
            'comments' => "/{parent_type}/information/{parent_id}",
            'milestones' => "/projects/information/{parent_id}",
            'estimates-invoices' => "/estimates-invoices/view/{id}",
            'time-tracker' => "/time-tracker",
            'leave-requests' => "/leave-requests",
            'clients' => "/clients/profile/{id}",
            'users' => "/users/profile/{id}",
            'expenses' => "/expenses",
            'expenses/expense-types' => "/expenses/expense-types",
            'items' => "/items",
            'payments' => "/payments",
            'payment-methods' => "/payment-methods",
            'taxes' => "/taxes",
            'units' => "/units",
            'contracts' => "/contracts/sign/{id}",
            'todos' => "/todos",
            'contracts/contract-types' => "/contracts/contract-types",
            'payslips' => "/payslips",
            'allowances' => "/allowances",
            'deductions' => "/deductions",
            'tags/manage' => "/tags/manage",
            'status/manage' => "/status/manage",
            'priority/manage' => "/priority/manage",
            'workspaces' => "/workspaces",
            'notes' => "/notes",
            'meetings' => "/meetings",
        ];
        // Check if the plural type exists in the URL pattern
        if (isset($urlPatterns[$pluralType])) {
            return $base_url . str_replace(
                ['{id}', '{parent_id}', '{parent_type}'],
                [$activity['type_id'], $activity['parent_type_id'] ?? '', $pluralParentType],
                $urlPatterns[$pluralType]
            );
        }
        // Fallback to list page if type not in array
        return "{$base_url}/" . $pluralType;
    }
}
if (!function_exists('formatEstimateInvoice')) {
    function formatEstimateInvoice($invoice)
    {
        $invoice->load('client', 'items'); // Load relationships
        return [
            'id' => $invoice->id,
            'type' => $invoice->type,
            'status' => $invoice->status,
            'client_id' => $invoice->client_id,
            'client' => [
                'id' => $invoice->client->id,
                'first_name' => $invoice->client->first_name,
                'last_name' => $invoice->client->last_name,
                'email' => $invoice->client->email,
                'photo' => ($invoice->client->photo && Storage::disk('public')->exists($invoice->client->photo)) ? asset('storage/' . $invoice->client->photo) : asset('storage/photos/no-image.jpg'),
            ],
            'name' => $invoice->name,
            'address' => $invoice->address,
            'city' => $invoice->city,
            'state' => $invoice->state,
            'country' => $invoice->country,
            'zip_code' => $invoice->zip_code,
            'phone' => $invoice->phone,
            'note' => $invoice->note,
            'personal_note' => $invoice->personal_note,
            'from_date' => $invoice->from_date ? format_date($invoice->from_date, to_format: 'Y-m-d') : null,
            'to_date' => $invoice->to_date ? format_date($invoice->to_date, to_format: 'Y-m-d') : null,
            'total' => (string) $invoice->total,
            'tax_amount' => (string)$invoice->tax_amount,
            'final_total' => (string) $invoice->final_total,
            'created_at' => format_date($invoice->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($invoice->updated_at, to_format: 'Y-m-d'),
            'items' => $invoice->items->map(function ($item) {
                $taxTitle = null;
                if ($item->pivot->tax_id) {
                    $tax = \App\Models\Tax::find($item->pivot->tax_id);
                    $taxTitle = $tax?->title;
                }
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => (string) $item->unit,
                    'price' => (string)$item->price,
                    'amount' => $item->amount,
                    'tax' => $taxTitle
                ];
            }),
        ];
    }
}
if (!function_exists('formatLeadSource')) {
    function formatLeadSource($lead_source)
    {
        return app(\App\Services\FormatterService::class)->formatLeadSource($lead_source);
    }
}
if (!function_exists('formatLeadStage')) {
    function formatLeadStage($lead_stage)
    {
        return app(\App\Services\FormatterService::class)->formatLeadStage($lead_stage);
    }
}
if (!function_exists('formatLead')) {
    function formatLead($lead)
    {
        return app(\App\Services\FormatterService::class)->formatLead($lead);
    }
}
if (!function_exists('formatLeadUserHtml')) {
    function formatLeadUserHtml($lead)
    {
        return app(\App\Services\FormatterService::class)->formatLeadUserHtml($lead);
    }
}
if (!function_exists('formatLeadFollowUp')) {
    function formatLeadFollowUp($followUp)
    {
        return app(\App\Services\FormatterService::class)->formatLeadFollowUp($followUp);
    }
}
if (!function_exists('formatCustomField')) {
    function formatCustomField($field)
    {
        return app(\App\Services\FormatterService::class)->formatCustomField($field);
    }
}
if (!function_exists('formatPayslip')) {
    function formatPayslip($payslip)
    {
        return app(\App\Services\FormatterService::class)->formatPayslip($payslip);
    }
}
if (!function_exists('formatAllowance')) {
    function formatAllowance($allowance)
    {
        return app(\App\Services\FormatterService::class)->formatAllowance($allowance);
    }
}
if (!function_exists('formatDeduction')) {
    function formatDeduction($deduction)
    {
        return app(\App\Services\FormatterService::class)->formatDeduction($deduction);
    }
}
if (!function_exists('formatContract')) {
    function formatContract($contract)
    {
        return app(\App\Services\FormatterService::class)->formatContract($contract);
    }
}
if (!function_exists('formatContractType')) {
    function formatContractType($contract_type)
    {
        return app(\App\Services\FormatterService::class)->formatContractType($contract_type);
    }
}
if (!function_exists('generate_description_openrouter')) {
    function generate_description_openrouter(string $prompt, $apiKey = null)
    {
        return app(\App\Services\AiService::class)->generateDescriptionOpenRouter($prompt, $apiKey);
    }
}
if (!function_exists('generate_description_gemini')) {
    function generate_description_gemini(string $prompt, $apiKey = null)
    {
        return app(\App\Services\AiService::class)->generateDescriptionGemini($prompt, $apiKey);
    }
}
if (!function_exists('generate_description')) {
    function generate_description(string $prompt)
    {
        return app(\App\Services\AiService::class)->generateDescription($prompt);
    }
}
if (!function_exists('get_ai_settings')) {
    function get_ai_settings(?string $provider = null)
    {
        return app(\App\Services\AiService::class)->getAiSettings($provider);
    }
}
if (!function_exists('getStatusCounts')) {
    function getStatusCounts($statuses, $auth_user, $type = 'projects')
    {
        return app(\App\Services\FormatterService::class)->getStatusCounts($statuses, $auth_user, $type);
    }
}
if (!function_exists('formatComment')) {
    function formatComment($comment)
    {
        return app(\App\Services\FormatterService::class)->formatComment($comment);
    }
}
if (!function_exists('formatLeadForm')) {
    function formatLeadForm($leadForm)
    {
        return app(\App\Services\FormatterService::class)->formatLeadForm($leadForm);
    }
}
if (!function_exists('formatLeadFormResponse')) {
    function formatLeadFormResponse($lead)
    {
        return app(\App\Services\FormatterService::class)->formatLeadFormResponse($lead);
    }
}
/**
 * Calculate leave days from date range, handling partial leaves (0.5 for half day)
 */
if (!function_exists('calculate_leave_days')) {
    function calculate_leave_days($fromDate, $toDate, $fromTime = null, $toTime = null)
    {
        return app(\App\Services\LeaveService::class)->calculateLeaveDays($fromDate, $toDate, $fromTime, $toTime);
    }
}
if (!function_exists('get_user_leave_balance')) {
    function get_user_leave_balance($userId, $workspaceId, $year = null)
    {
        return app(\App\Services\LeaveService::class)->getUserLeaveBalance($userId, $workspaceId, $year);
    }
}
if (!function_exists('get_current_company_year')) {
    function get_current_company_year()
    {
        return app(\App\Services\LeaveService::class)->getCurrentCompanyYear();
    }
}
if (!function_exists('get_company_year_dates')) {
    function get_company_year_dates($companyYear = null)
    {
        return app(\App\Services\LeaveService::class)->getCompanyYearDates($companyYear);
    }
}
if (!function_exists('format_company_year')) {
    function format_company_year($companyYear = null, $detailed = false)
    {
        return app(\App\Services\LeaveService::class)->formatCompanyYear($companyYear, $detailed);
    }
}
if (!function_exists('number_to_words')) {
    function number_to_words($number)
    {
        return app(\App\Services\FormatterService::class)->numberToWords($number);
    }
}
if (!function_exists('imageToBase64')) {
    function imageToBase64($path)
    {
        return app(\App\Services\FormatterService::class)->imageToBase64($path);
    }
}
