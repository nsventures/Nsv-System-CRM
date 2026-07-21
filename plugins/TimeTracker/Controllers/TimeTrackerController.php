<?php

namespace Plugins\TimeTracker\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Plugins\TimeTracker\Models\Screenshot;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;
use Plugins\TimeTracker\Models\TimeTrackerConfig;

class TimeTrackerController extends Controller
{
    /**
     * Log Update - Records employee activity logs.
     *
     * Records a start or end work activity for a specific employee. This can be used to start or stop a work session.
     *
     * @authenticated
     *
     * @group Time Tracking
     *
     * @bodyParam user_id integer required The ID of the employee. Example: 1
     * @bodyParam action string required The action performed. Must be "start_work" or "end_work". Example: start_work
     * @bodyParam timestamp string required The timestamp of the action. Must be a valid datetime. Example: 2025-06-17 10:00:00
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Log updated successfully.",
     *   "data": {
     *     "employeeId": 1,
     *     "action": "start_work",
     *     "timestamp": "2025-06-17 10:00:00"
     *   }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "The given data was invalid.",
     *   "data": {
     *     "user_id": ["The user_id field is required."]
     *   }
     * }
     */
    // public function logUpdate(Request $request)
    // {
    //     $isApi = $request->get('isApi', false);

    //     // ===== DEBUGGING: Log authentication context =====
    //     Log::info('========== LOG UPDATE REQUEST START ==========');

    //     // Log all auth guards
    //     $webUser = Auth::guard('web')->user();
    //     $clientUser = Auth::guard('client')->user();
    //     $sanctumUser = Auth::guard('sanctum')->user();
    //     $defaultAuthUser = Auth::user();
    //     $defaultAuthId = Auth::id();

    //     Log::info('AUTH GUARDS CHECK:', [
    //         'web_guard_check' => Auth::guard('web')->check(),
    //         'web_user_id' => $webUser ? $webUser->id : null,
    //         'web_user_name' => $webUser ? ($webUser->first_name . ' ' . $webUser->last_name) : null,
    //         'client_guard_check' => Auth::guard('client')->check(),
    //         'client_user_id' => $clientUser ? $clientUser->id : null,
    //         'sanctum_guard_check' => Auth::guard('sanctum')->check(),
    //         'sanctum_user_id' => $sanctumUser ? $sanctumUser->id : null,
    //         'sanctum_user_name' => $sanctumUser ? ($sanctumUser->first_name . ' ' . $sanctumUser->last_name) : null,
    //         'default_auth_id' => $defaultAuthId,
    //         'default_auth_user_name' => $defaultAuthUser ? ($defaultAuthUser->first_name . ' ' . $defaultAuthUser->last_name) : null,
    //     ]);

    //     // Log getAuthenticatedUser() helper
    //     $authenticatedUser = getAuthenticatedUser();
    //     Log::info('getAuthenticatedUser() RESULT:', [
    //         'user_id' => $authenticatedUser ? $authenticatedUser->id : null,
    //         'user_name' => $authenticatedUser ? ($authenticatedUser->first_name . ' ' . $authenticatedUser->last_name) : null,
    //         'user_email' => $authenticatedUser ? $authenticatedUser->email : null,
    //     ]);

    //     // Log request data BEFORE user_id merge
    //     Log::info('REQUEST DATA (before merge):', [
    //         'has_user_id' => $request->has('user_id'),
    //         'user_id_value' => $request->get('user_id'),
    //         'action' => $request->get('action'),
    //         'timestamp' => $request->get('timestamp'),
    //     ]);

    //     // Log request headers
    //     Log::info('REQUEST HEADERS:', [
    //         'authorization' => $request->header('Authorization'),
    //         'user_agent' => $request->header('User-Agent'),
    //         'x_requested_with' => $request->header('X-Requested-With'),
    //         'origin' => $request->header('Origin'),
    //         'referer' => $request->header('Referer'),
    //     ]);

    //     // Log request details
    //     Log::info('REQUEST DETAILS:', [
    //         'ip_address' => $request->ip(),
    //         'method' => $request->method(),
    //         'url' => $request->fullUrl(),
    //         'is_api' => $isApi,
    //     ]);
    //     // ===== END DEBUGGING =====

    //     if (! $request->has('user_id')) {
    //         $mergedUserId = getAuthenticatedUser()->id;
    //         Log::info('USER_ID MERGED:', [
    //             'merged_user_id' => $mergedUserId,
    //             'reason' => 'user_id not provided in request',
    //         ]);
    //         $request->merge(['user_id' => $mergedUserId]);
    //     }

    //     try {
    //         $data = $request->validate([
    //             'user_id' => 'required|integer|exists:users,id',
    //             'action' => 'required|string|in:clock-in,clock-out,idle-start,idle-stop,break-start,break-stop,manual-start,manual-stop',
    //             'timestamp' => 'required|date',
    //             'timestamp_timezone' => 'nullable|string', // Optional: timezone of incoming timestamp
    //         ]);

    //         // ===== DEBUGGING: Log validated data =====
    //         Log::info('VALIDATED DATA:', [
    //             'user_id' => $data['user_id'],
    //             'action' => $data['action'],
    //             'timestamp' => $data['timestamp'],
    //             'timestamp_timezone' => $data['timestamp_timezone'] ?? null,
    //         ]);

    //         // Fetch and log the user details from database
    //         $userFromDb = \App\Models\User::find($data['user_id']);
    //         if ($userFromDb) {
    //             Log::info('USER FROM DATABASE:', [
    //                 'user_id' => $userFromDb->id,
    //                 'user_name' => $userFromDb->first_name . ' ' . $userFromDb->last_name,
    //                 'user_email' => $userFromDb->email,
    //             ]);
    //         } else {
    //             Log::warning('USER NOT FOUND IN DATABASE for ID: ' . $data['user_id']);
    //         }
    //         // ===== END DEBUGGING =====

    //         // Get timezone from general settings
    //         $general_settings = get_settings('general_settings');
    //         $userTimezone = $general_settings['timezone'] ?? 'UTC';

    //         // Use provided timezone or default to user's timezone
    //         $incomingTimezone = $data['timestamp_timezone'] ?? $userTimezone;

    //         // Convert incoming timestamp from specified timezone to UTC for storage
    //         $timestampUTC = \Carbon\Carbon::parse($data['timestamp'], $incomingTimezone)->setTimezone('UTC');

    //         // ===== DEBUGGING: Log activity log being created =====
    //         Log::info('CREATING ACTIVITY LOG:', [
    //             'user_id' => $data['user_id'],
    //             'action' => $data['action'],
    //             'timestamp_utc' => $timestampUTC->format('Y-m-d H:i:s'),
    //         ]);
    //         // ===== END DEBUGGING =====

    //         // Store the activity log entry in UTC
    //         $activityLog = TimeTrackerActivityLog::create([
    //             'user_id' => $data['user_id'],
    //             'action' => $data['action'],
    //             'timestamp' => $timestampUTC, // Store in UTC
    //         ]);

    //         // ===== DEBUGGING: Log activity log created =====
    //         Log::info('ACTIVITY LOG CREATED:', [
    //             'log_id' => $activityLog->id,
    //             'stored_user_id' => $activityLog->user_id,
    //             'stored_action' => $activityLog->action,
    //         ]);
    //         Log::info('========== LOG UPDATE REQUEST END ==========');
    //         // ===== END DEBUGGING =====

    //         // Return timestamp in user's timezone for confirmation
    //         $timestampUserTZ = $timestampUTC->setTimezone($userTimezone)->format('Y-m-d H:i:s');

    //         return formatApiResponse(
    //             false,
    //             'Log updated successfully.',
    //             [
    //                 'data' => [
    //                     'employeeId' => $data['user_id'],
    //                     'user_id' => $data['user_id'],
    //                     'action' => $data['action'],
    //                     'timestamp' => $timestampUserTZ, // Return in user's timezone
    //                     'timestamp_utc' => $timestampUTC->format('Y-m-d H:i:s'), // Also include UTC for verification
    //                     'timezone' => $userTimezone,
    //                 ],
    //             ]
    //         );
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return formatApiValidationError($isApi, $e->errors());
    //     } catch (Exception $e) {
    //         Log::error('Log update failed: ' . $e->getMessage());
    //         return formatApiResponse(true, 'Failed to update log' .
    //             ' - ' . $e->getMessage(), [
    //                 'data' => [],
    //             ]);
    //     }
    // }

    public function logUpdate(Request $request)
    {
        $isApi = $request->get('isApi', false);

        // ===== DEBUGGING: Log authentication context =====
        Log::info('========== LOG UPDATE REQUEST START ==========');

        // ... [Keep your existing debug logging code] ...

        if (!$request->has('user_id')) {
            $mergedUserId = getAuthenticatedUser()->id;
            Log::info('USER_ID MERGED:', [
                'merged_user_id' => $mergedUserId,
                'reason' => 'user_id not provided in request',
            ]);
            $request->merge(['user_id' => $mergedUserId]);
        }

        try {
            $data = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'action' => 'required|string|in:clock-in,clock-out,idle-start,idle-stop,break-start,break-stop,manual-start,manual-stop,manual-processing-start,manual-processing-stop',
                'timestamp' => 'required|date',
                'timestamp_timezone' => 'nullable|string', // Optional: timezone of incoming timestamp
            ]);

            // Get timezone from general settings FIRST
            $general_settings = get_settings('general_settings');
            $systemTimezone = $general_settings['timezone'] ?? 'UTC';

            Log::info('TIMEZONE CONFIGURATION:', [
                'system_timezone' => $systemTimezone,
                'provided_timezone' => $data['timestamp_timezone'] ?? null,
            ]);

            // Determine which timezone to use for parsing the incoming timestamp
            // Priority: 1. Provided timezone, 2. System timezone from settings
            $incomingTimezone = $data['timestamp_timezone'] ?? $systemTimezone;

            Log::info('VALIDATED DATA:', [
                'user_id' => $data['user_id'],
                'action' => $data['action'],
                'timestamp' => $data['timestamp'],
                'incoming_timezone' => $incomingTimezone,
            ]);

            // Parse the timestamp assuming it's in the incoming timezone, then convert to UTC
            try {
                $timestampUTC = \Carbon\Carbon::parse($data['timestamp'], $incomingTimezone)->setTimezone('UTC');

                Log::info('TIMESTAMP CONVERSION:', [
                    'original_timestamp' => $data['timestamp'],
                    'parsed_timezone' => $incomingTimezone,
                    'converted_utc' => $timestampUTC->format('Y-m-d H:i:s'),
                ]);
            } catch (\Exception $e) {
                Log::error('Timestamp parsing failed:', [
                    'timestamp' => $data['timestamp'],
                    'timezone' => $incomingTimezone,
                    'error' => $e->getMessage(),
                ]);
                throw new \Exception('Invalid timestamp format: ' . $e->getMessage());
            }

            // Fetch and log the user details from database
            $userFromDb = \App\Models\User::find($data['user_id']);
            if ($userFromDb) {
                Log::info('USER FROM DATABASE:', [
                    'user_id' => $userFromDb->id,
                    'user_name' => $userFromDb->first_name . ' ' . $userFromDb->last_name,
                    'user_email' => $userFromDb->email,
                ]);
            } else {
                Log::warning('USER NOT FOUND IN DATABASE for ID: ' . $data['user_id']);
            }

            Log::info('CREATING ACTIVITY LOG:', [
                'user_id' => $data['user_id'],
                'action' => $data['action'],
                'timestamp_utc' => $timestampUTC->format('Y-m-d H:i:s'),
            ]);

            // Store the activity log entry in UTC
            $activityLog = TimeTrackerActivityLog::create([
                'user_id' => $data['user_id'],
                'action' => $data['action'],
                'timestamp' => $timestampUTC, // Store in UTC
            ]);

            Log::info('ACTIVITY LOG CREATED:', [
                'log_id' => $activityLog->id,
                'stored_user_id' => $activityLog->user_id,
                'stored_action' => $activityLog->action,
            ]);
            Log::info('========== LOG UPDATE REQUEST END ==========');

            // Return timestamp in system timezone for confirmation
            $timestampSystemTZ = $timestampUTC->setTimezone($systemTimezone)->format('Y-m-d H:i:s');

            return formatApiResponse(
                false,
                'Log updated successfully.',
                [
                    'data' => [
                        'employeeId' => $data['user_id'],
                        'user_id' => $data['user_id'],
                        'action' => $data['action'],
                        'timestamp' => $timestampSystemTZ, // Return in system timezone
                        'timestamp_utc' => $timestampUTC->format('Y-m-d H:i:s'), // Also include UTC for verification
                        'timezone' => $systemTimezone,
                    ],
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (Exception $e) {
            Log::error('Log update failed: ' . $e->getMessage());
            return formatApiResponse(true, 'Failed to update log - ' . $e->getMessage(), [
                'data' => [],
            ]);
        }
    }
    /**
     * Upload Screenshot - Saves employee screenshot.
     *
     * Uploads a screenshot file taken during tracking and stores it for the authenticated user.
     *
     * @authenticated
     *
     * @group Time Tracking
     *
     * @bodyParam screenshot file required The screenshot image file. Must be jpg, jpeg, or png.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Screenshot uploaded successfully.",
     *   "data": {
     *     "filename": "1687001200_desktop.png",
     *     "path": "/storage/screenshots/1687001200_desktop.png"
     *   }
     * }
     *
     * @response 400 {
     *   "error": true,
     *   "message": "No file uploaded.",
     *   "data": {}
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "The given data was invalid.",
     *   "data": {
     *     "screenshot": ["The screenshot must be an image."]
     *   }
     * }
     */
    // public function uploadScreenshot(Request $request)
    // {
    //     $isApi = $request->get('isApi', false);

    //     try {
    //         // ===== DEBUGGING: Log authentication context =====
    //         Log::info('========== SCREENSHOT UPLOAD REQUEST START ==========');

    //         // Log all auth guards
    //         $webUser = Auth::guard('web')->user();
    //         $clientUser = Auth::guard('client')->user();
    //         $sanctumUser = Auth::guard('sanctum')->user();
    //         $defaultAuthUser = Auth::user();
    //         $defaultAuthId = Auth::id();

    //         Log::info('AUTH GUARDS CHECK:', [
    //             'web_guard_check' => Auth::guard('web')->check(),
    //             'web_user_id' => $webUser ? $webUser->id : null,
    //             'web_user_name' => $webUser ? ($webUser->first_name . ' ' . $webUser->last_name) : null,
    //             'client_guard_check' => Auth::guard('client')->check(),
    //             'client_user_id' => $clientUser ? $clientUser->id : null,
    //             'sanctum_guard_check' => Auth::guard('sanctum')->check(),
    //             'sanctum_user_id' => $sanctumUser ? $sanctumUser->id : null,
    //             'sanctum_user_name' => $sanctumUser ? ($sanctumUser->first_name . ' ' . $sanctumUser->last_name) : null,
    //             'default_auth_id' => $defaultAuthId,
    //             'default_auth_user_name' => $defaultAuthUser ? ($defaultAuthUser->first_name . ' ' . $defaultAuthUser->last_name) : null,
    //         ]);

    //         // Log getAuthenticatedUser() helper
    //         $authenticatedUser = getAuthenticatedUser();
    //         Log::info('getAuthenticatedUser() RESULT:', [
    //             'user_id' => $authenticatedUser ? $authenticatedUser->id : null,
    //             'user_name' => $authenticatedUser ? ($authenticatedUser->first_name . ' ' . $authenticatedUser->last_name) : null,
    //             'user_email' => $authenticatedUser ? $authenticatedUser->email : null,
    //         ]);

    //         // Log request headers
    //         Log::info('REQUEST HEADERS:', [
    //             'authorization' => $request->header('Authorization'),
    //             'user_agent' => $request->header('User-Agent'),
    //             'x_requested_with' => $request->header('X-Requested-With'),
    //             'origin' => $request->header('Origin'),
    //             'referer' => $request->header('Referer'),
    //         ]);

    //         // Log request details
    //         Log::info('REQUEST DETAILS:', [
    //             'ip_address' => $request->ip(),
    //             'method' => $request->method(),
    //             'url' => $request->fullUrl(),
    //             'is_api' => $isApi,
    //         ]);
    //         // ===== END DEBUGGING =====

    //         if (! $request->hasFile('screenshot')) {
    //             return response()->json([
    //                 'error' => true,
    //                 'message' => 'No file uploaded.',
    //                 'data' => [],
    //             ], 400);
    //         }

    //         $data = $request->validate([
    //             'screenshot' => 'required|image|mimes:png,jpg,jpeg|max:5120', // limit to 5MB
    //             'metadata' => 'nullable|array',
    //             'captured_at' => 'nullable|date', // allow custom captured_at
    //             'captured_at_timezone' => 'nullable|string', // Optional: timezone of captured_at
    //         ]);

    //         $file = $request->file('screenshot');

    //         // Get timezone from general settings
    //         $general_settings = get_settings('general_settings');
    //         $userTimezone = $general_settings['timezone'] ?? 'UTC';

    //         // Use provided timezone or default to user's timezone
    //         $incomingTimezone = $data['captured_at_timezone'] ?? $userTimezone;

    //         // Convert captured_at from specified timezone to UTC for storage
    //         if (isset($data['captured_at'])) {
    //             $capturedAtUTC = \Carbon\Carbon::parse($data['captured_at'], $incomingTimezone)->setTimezone('UTC');
    //         } else {
    //             $capturedAtUTC = now(); // now() is already in UTC
    //         }

    //         // Use a structured filename for easy management
    //         $filename = now()->format('Ymd_His') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

    //         $path = $file->storeAs('screenshots', $filename, 'public');

    //         if (! $path) {
    //             return response()->json([
    //                 'error' => true,
    //                 'message' => 'Failed to store the screenshot.',
    //                 'data' => [],
    //             ], 500);
    //         }

    //         // Determine user_id - use getAuthenticatedUser() helper for consistency
    //         $userId = getAuthenticatedUser(true) ?? Auth::id() ?? 1;

    //         // ===== DEBUGGING: Log user_id being stored =====
    //         Log::info('SCREENSHOT STORAGE:', [
    //             'user_id_to_store' => $userId,
    //             'filename' => $filename,
    //             'file_size_bytes' => $file->getSize(),
    //             'captured_at_utc' => $capturedAtUTC->format('Y-m-d H:i:s'),
    //             'metadata' => $data['metadata'] ?? null,
    //         ]);

    //         // Fetch and log the user details from database
    //         $userFromDb = \App\Models\User::find($userId);
    //         if ($userFromDb) {
    //             Log::info('USER FROM DATABASE:', [
    //                 'user_id' => $userFromDb->id,
    //                 'user_name' => $userFromDb->first_name . ' ' . $userFromDb->last_name,
    //                 'user_email' => $userFromDb->email,
    //             ]);
    //         } else {
    //             Log::warning('USER NOT FOUND IN DATABASE for ID: ' . $userId);
    //         }
    //         // ===== END DEBUGGING =====

    //         // Store screenshot with captured_at in UTC
    //         $screenshot = Screenshot::create([
    //             'user_id' => $userId,
    //             'screenshot_path' => $path,
    //             'filename' => $filename,
    //             'file_size' => $file->getSize(),
    //             'captured_at' => $capturedAtUTC, // Store in UTC
    //             'metadata' => ! empty($data['metadata']) ? json_encode($data['metadata']) : null,
    //         ]);

    //         // ===== DEBUGGING: Log screenshot record created =====
    //         Log::info('SCREENSHOT CREATED IN DB:', [
    //             'screenshot_id' => $screenshot->id,
    //             'stored_user_id' => $screenshot->user_id,
    //             'stored_filename' => $screenshot->filename,
    //         ]);
    //         Log::info('========== SCREENSHOT UPLOAD REQUEST END ==========');
    //         // ===== END DEBUGGING =====

    //         return response()->json([
    //             'error' => false,
    //             'message' => 'Screenshot uploaded successfully.',
    //             'data' => [
    //                 'id' => $screenshot->id,
    //                 'filename' => $filename,
    //                 'url' => Storage::url($path),
    //                 'captured_at' => $screenshot->captured_at->setTimezone($userTimezone)->format('Y-m-d H:i:s'),
    //                 'captured_at_utc' => $screenshot->captured_at->format('Y-m-d H:i:s'), // UTC for verification
    //                 'captured_at_iso' => $screenshot->captured_at->setTimezone($userTimezone)->toIso8601String(),
    //                 'timezone' => $userTimezone,
    //                 'file_size_kb' => round($screenshot->file_size / 1024, 2) . ' KB',
    //                 'metadata' => $screenshot->metadata ? json_decode($screenshot->metadata, true) : null,

    //             ],
    //         ]);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'error' => true,
    //             'message' => 'Validation error.',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (Exception $e) {
    //         Log::error('Screenshot upload failed: ' . $e->getMessage());
    //         return response()->json([
    //             'error' => true,
    //             'message' => 'Failed to upload screenshot.',
    //             'data' => [],
    //         ], 500);
    //     }
    // }
    public function uploadScreenshot(Request $request)
    {
        $isApi = $request->get('isApi', false);

        try {
            // ===== DEBUGGING: Log authentication context =====
            Log::info('========== SCREENSHOT UPLOAD REQUEST START ==========');

            // ... [Keep your existing debug logging code] ...

            if (!$request->hasFile('screenshot')) {
                return response()->json([
                    'error' => true,
                    'message' => 'No file uploaded.',
                    'data' => [],
                ], 400);
            }

            // Handle metadata if sent as JSON string in form-data
            if ($request->has('metadata') && is_string($request->input('metadata'))) {
                $metadataString = $request->input('metadata');
                $decodedMetadata = json_decode($metadataString, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMetadata)) {
                    // Replace the string with the decoded array
                    $request->merge(['metadata' => $decodedMetadata]);
                } elseif (json_last_error() !== JSON_ERROR_NONE) {
                    // If JSON decode fails, try to handle it as empty or log error
                    Log::warning('Failed to parse metadata JSON string:', [
                        'metadata_string' => $metadataString,
                        'json_error' => json_last_error_msg()
                    ]);
                    $request->merge(['metadata' => null]);
                }
            }

            $data = $request->validate([
                'screenshot' => 'required|image|mimes:png,jpg,jpeg|max:5120', // limit to 5MB
                'metadata' => 'nullable|array',
                'captured_at' => 'nullable|date', // allow custom captured_at
                'captured_at_timezone' => 'nullable|string', // Optional: timezone of captured_at
            ]);

            $file = $request->file('screenshot');

            // Get timezone from general settings FIRST
            $general_settings = get_settings('general_settings');
            $systemTimezone = $general_settings['timezone'] ?? 'UTC';

            Log::info('TIMEZONE CONFIGURATION:', [
                'system_timezone' => $systemTimezone,
                'provided_timezone' => $data['captured_at_timezone'] ?? null,
            ]);

            // Determine which timezone to use for parsing the captured_at timestamp
            // Priority: 1. Provided timezone, 2. System timezone from settings
            $incomingTimezone = $data['captured_at_timezone'] ?? $systemTimezone;

            // Convert captured_at from specified timezone to UTC for storage
            if (isset($data['captured_at'])) {
                try {
                    $capturedAtUTC = \Carbon\Carbon::parse($data['captured_at'], $incomingTimezone)->setTimezone('UTC');

                    Log::info('TIMESTAMP CONVERSION:', [
                        'original_captured_at' => $data['captured_at'],
                        'parsed_timezone' => $incomingTimezone,
                        'converted_utc' => $capturedAtUTC->format('Y-m-d H:i:s'),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Timestamp parsing failed:', [
                        'captured_at' => $data['captured_at'],
                        'timezone' => $incomingTimezone,
                        'error' => $e->getMessage(),
                    ]);
                    $capturedAtUTC = now(); // Fallback to current time in UTC
                }
            } else {
                $capturedAtUTC = now(); // now() is already in UTC
            }

            // Use a structured filename for easy management
            $filename = now()->format('Ymd_His') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('screenshots', $filename, 'public');

            if (!$path) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to store the screenshot.',
                    'data' => [],
                ], 500);
            }

            // Determine user_id - use getAuthenticatedUser() helper for consistency
            $userId = getAuthenticatedUser(true) ?? Auth::id() ?? 1;

            Log::info('SCREENSHOT STORAGE:', [
                'user_id_to_store' => $userId,
                'filename' => $filename,
                'file_size_bytes' => $file->getSize(),
                'captured_at_utc' => $capturedAtUTC->format('Y-m-d H:i:s'),
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Fetch and log the user details from database
            $userFromDb = \App\Models\User::find($userId);
            if ($userFromDb) {
                Log::info('USER FROM DATABASE:', [
                    'user_id' => $userFromDb->id,
                    'user_name' => $userFromDb->first_name . ' ' . $userFromDb->last_name,
                    'user_email' => $userFromDb->email,
                ]);
            } else {
                Log::warning('USER NOT FOUND IN DATABASE for ID: ' . $userId);
            }

            // Store screenshot with captured_at in UTC
            $screenshot = Screenshot::create([
                'user_id' => $userId,
                'screenshot_path' => $path,
                'filename' => $filename,
                'file_size' => $file->getSize(),
                'captured_at' => $capturedAtUTC, // Store in UTC
                'metadata' => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
            ]);

            Log::info('SCREENSHOT CREATED IN DB:', [
                'screenshot_id' => $screenshot->id,
                'stored_user_id' => $screenshot->user_id,
                'stored_filename' => $screenshot->filename,
            ]);
            Log::info('========== SCREENSHOT UPLOAD REQUEST END ==========');

            return response()->json([
                'error' => false,
                'message' => 'Screenshot uploaded successfully.',
                'data' => [
                    'id' => $screenshot->id,
                    'filename' => $filename,
                    'url' => Storage::url($path),
                    'captured_at' => $screenshot->captured_at->setTimezone($systemTimezone)->format('Y-m-d H:i:s'),
                    'captured_at_utc' => $screenshot->captured_at->format('Y-m-d H:i:s'), // UTC for verification
                    'captured_at_iso' => $screenshot->captured_at->setTimezone($systemTimezone)->toIso8601String(),
                    'timezone' => $systemTimezone,
                    'file_size_kb' => round($screenshot->file_size / 1024, 2) . ' KB',
                    'metadata' => $screenshot->metadata ? json_decode($screenshot->metadata, true) : null,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Screenshot upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to upload screenshot.',
                'data' => [],
            ], 500);
        }
    }
    /**
     * Load Config - Returns time tracking configuration.
     *
     * Loads current configuration for time tracking such as screenshot interval, idle threshold, and break detection timing.
     *
     * @authenticated
     *
     * @group Time Tracking
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Config loaded successfully.",
     *   "data": {
     *     "screenshotInterval": 60000,
     *     "idleTimeThreshold": 300000,
     *     "breakTimeThreshold": 600000
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Failed to load configuration"
     * }
     */
    public function loadConfig(Request $request)
    {
        try {
            // Try to load from database first
            $configData = TimeTrackerConfig::where('name', 'time_tracker_config')->value('value');
            $general_settings = get_settings('general_settings');
            $timezone = $general_settings['timezone'] ?? 'UTC';

            $config = [
                'screenshotInterval' => (int) ($configData['screenshotInterval'] ?? '60000'), // Default to 60 seconds
                'idleTimeThreshold' => (int) ($configData['idleTimeThreshold'] ?? '300000'), // Default to 5 minutes
                'breakTimeThreshold' => (int) ($configData['breakTimeThreshold'] ?? '600000'), // Default to 10 minutes
                'maxDailyBreakTime' => (int) ($configData['maxDailyBreakTime'] ?? '3600000'), // Default to 1 hour
                'manualTimeApprover' => $configData['manualTimeApprover'] ?? [],
                'timezone' => $timezone, // Include dynamic timezone from general settings for desktop app
            ];
            return formatApiResponse(
                false,
                'Config loaded successfully.',
                [
                    'data' => $config,
                ]
            );
        } catch (Exception $e) {
            Log::error('Failed to load config: ' . $e->getMessage());
            return formatApiResponse(true, 'Failed to load configuration');
        }
    }
    /**
     * Display the time tracker index page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('timetracker::timetracker.index');
    }
    // Configuration Page
    public function configuration()
    {
        // Default values in milliseconds
        $defaultConfig = [
            'screenshotInterval' => 60000,        // 1 minute
            'idleTimeThreshold' => 300000,        // 5 minutes
            'breakTimeThreshold' => 600000,       // 10 minutes
            'maxDailyBreakTime' => 3600000,       // 1 hour
            'manualTimeApprover' => [],
            'workDayStartTime' => '09:00',        // Default work day start
            'auto_delete_screenshots_after_days' => 30, // Default to 30 days
        ];
        // Get saved config from DB
        $config = TimeTrackerConfig::where('name', 'time_tracker_config')->value('value');
        // Decode JSON if available
        $decoded = is_array($config) ? $config : json_decode($config, true);
        // Merge decoded config with defaults
        $time_tracker_config = array_merge($defaultConfig, $decoded ?? []);
        $users = User::select('id', 'first_name', 'last_name')->get()->mapWithKeys(function ($user) {
            return [$user->id => $user->first_name . ' ' . $user->last_name];
        });
        return view('timetracker::timetracker.configuration', compact('time_tracker_config', 'users'));
    }
    // Store Configuration
    public function storeConfig(Request $request)
    {
        $formFields = $request->validate([
            'screenshotInterval' => 'required|integer|min:1',
            'idleTimeThreshold' => 'required|integer|min:1',
            'breakTimeThreshold' => 'required|integer|min:1',
            'maxDailyBreakTime' => 'required|integer|min:1',
            'manualTimeApprover' => 'nullable|array',
            'manualTimeApprover.*' => 'exists:users,id',
            'workDayStartTime' => 'required|date_format:H:i', // Validate as time format
            'auto_delete_screenshots_after_days' => 'nullable|integer|min:1',
        ]);
        $config = [
            'screenshotInterval' => $formFields['screenshotInterval'] * 1000,
            'idleTimeThreshold' => $formFields['idleTimeThreshold'] * 1000,
            'breakTimeThreshold' => $formFields['breakTimeThreshold'] * 1000,
            'maxDailyBreakTime' => $formFields['maxDailyBreakTime'] * 1000,
            'manualTimeApprover' => $formFields['manualTimeApprover'],
            'workDayStartTime' => $formFields['workDayStartTime'],
            'auto_delete_screenshots_after_days' => $formFields['auto_delete_screenshots_after_days'] ?? 30, // Default to 30 days if not set
        ];
        try {
            DB::beginTransaction();
            TimeTrackerConfig::updateOrInsert(
                ['name' => 'time_tracker_config'],
                [
                    'value' => json_encode($config),
                    'updated_at' => now(),
                ]
            );
            DB::commit();
            return formatApiResponse(
                false,
                'Config stored successfully.',
                ['data' => $config]
            );
        } catch (Exception $e) {
            DB::rollBack();
            return formatApiResponse(
                true,
                'Config could not be stored. Please try again later.',
                [
                    'data' => [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                    ],
                ]
            );
        }
    }

    public function getTimeEntries()
    {
        $general_settings = get_settings('general_settings');
        $timezone = $general_settings['timezone'] ?? 'UTC';

        return formatApiResponse(false, 'Time entries fetched successfully.', [
            'data' => TimeTrackerActivityLog::where('user_id', getAuthenticatedUser()->id)
                ->orderBy('timestamp', 'desc')
                ->limit(100)
                ->get(),
            'timezone' => $timezone,
        ]);
    }

    public function getActivityLogs(Request $request)
    {
        $userId = $request->query('userId', getAuthenticatedUser()->id);
        $general_settings = get_settings('general_settings');
        $timezone = $general_settings['timezone'] ?? 'UTC';

        return formatApiResponse(false, 'Activity logs fetched successfully.', [
            'data' => TimeTrackerActivityLog::where('user_id', $userId)
                ->orderBy('timestamp', 'desc')
                ->limit(100)
                ->get(),
            'timezone' => $timezone,
        ]);
    }
    public function getScreenshots(Request $request, $userId = null)
    {
        $userId = $userId ?? getAuthenticatedUser()->id;
        $general_settings = get_settings('general_settings');
        $timezone = $general_settings['timezone'] ?? 'UTC';

        $screenshots = Screenshot::where('user_id', $userId)
            ->orderBy('captured_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($screenshot) use ($timezone) {
                return [
                    'id' => $screenshot->id,
                    'filename' => $screenshot->filename,
                    'url' => Storage::url($screenshot->screenshot_path),
                    'captured_at' => $screenshot->captured_at->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'captured_at_iso' => $screenshot->captured_at->setTimezone($timezone)->toIso8601String(),
                    'timezone' => $timezone,
                    'file_size_kb' => round($screenshot->file_size / 1024, 2) . ' KB',
                    'metadata' => $screenshot->metadata ? json_decode($screenshot->metadata, true) : null,
                ];
            });
        return formatApiResponse(false, 'Screenshots fetched successfully.', [
            'data' => $screenshots,
            'timezone' => $timezone,
        ]);
    }
}
