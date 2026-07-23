<?php

namespace Plugins\TimeTracker\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Plugins\TimeTracker\Models\AppDownload;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppDownloadController extends Controller
{
    public function index()
    {
        $downloads = AppDownload::latest()
            ->get()
            ->groupBy(fn($item) => "{$item->platform}-{$item->arch}");
        // dd($downloads);
        return view('timetracker::downloads.index', compact('downloads'));
    }
    public function uploadForm()
    {
        return view('timetracker::downloads.upload');
    }
    public function store(Request $request)
    {
        $isApi = $request->get('isApi', false);
        // Relaxed: any setup/file may be uploaded. Platform/version are optional
        // (only meaningful for OS installer builds); a title labels general files.
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'platform' => 'nullable|in:windows,mac,linux',
            'arch' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:50',
            'file' => 'required|file|max:512000', // 500MB, any type (see denylist below)
            'changelog' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return formatApiValidationError($isApi, $validator->errors());
        }

        try {
            $uploadedFile = $request->file('file');

            // Validate file upload
            if (!$uploadedFile->isValid()) {
                return formatApiResponse(true, 'File upload failed. Please try again.');
            }

            $fileType = strtolower($uploadedFile->getClientOriginalExtension());

            // Security: these are served publicly from the web-accessible disk, so refuse
            // server-executable / inline-scriptable types (they could run on your domain).
            $blockedExtensions = [
                'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar', 'pht',
                'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'asp', 'aspx', 'jsp', 'jspx',
                'htaccess', 'html', 'htm', 'xhtml', 'svg', 'xml',
            ];
            if (in_array($fileType, $blockedExtensions, true)) {
                return formatApiResponse(true, "For security, .{$fileType} files cannot be uploaded here.");
            }

            // Generate unique filename to prevent conflicts
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $sanitizedName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $originalName);
            $uniqueFilename = time() . '_' . $sanitizedName . '.' . $fileType;

            // Store in public disk so it's accessible via Storage::url()
            $filePath = $uploadedFile->storeAs('app_downloads', $uniqueFilename, 'public');

            // Verify file was actually stored
            if (!Storage::disk('public')->exists($filePath)) {
                return formatApiResponse(true, 'Failed to store the uploaded file.');
            }

            $appDownload = AppDownload::create([
                'title' => $request->title ?: pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
                'platform' => $request->platform ?: null,
                'arch' => $request->arch ?: null,
                'version' => $request->version ?: null,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'changelog' => $request->changelog,
            ]);

            return formatApiResponse(
                false,
                'App uploaded successfully.',
                [
                    'data' => $appDownload,
                ]
            );
        } catch (QueryException $e) {
            // Clean up uploaded file if database operation fails
            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            if ($e->errorInfo[1] === 1062) {
                return formatApiResponse(
                    true,
                    'Duplicate entry: This platform, architecture, and version combination already exists.',
                    [
                        'data' => [
                            'error' => $e->getMessage(),
                            'line' => $e->getLine()
                        ]
                    ]
                );
            }

            return formatApiResponse(
                true,
                'An error occurred while saving the app.',
                [
                    'data' => [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine()
                    ]
                ]
            );
        } catch (Exception $e) {
            // Clean up uploaded file on any other exception
            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return formatApiResponse(
                true,
                'An unexpected error occurred while processing the upload.',
                [
                    'data' => [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine()
                    ]
                ]
            );
        }
    }
    public function download($id)
    {
        $app = AppDownload::findOrFail($id);

        // Files live on the public disk (storage/app/public/app_downloads) — always
        // read them from that disk explicitly. The default disk follows the media
        // storage setting and may point at S3, which never holds these uploads.
        if (!$app->file_path || !Storage::disk('public')->exists($app->file_path)) {
            abort(404, 'This file is no longer available.');
        }

        $app->increment('download_count');

        return Storage::disk('public')->download($app->file_path);
    }

    public function destroy(Request $request, $id)
    {
        $isApi = $request->get('isApi', false);

        try {
            $appDownload = AppDownload::findOrFail($id);

            // Optional: Prevent deletion of the latest version
            $isLatestVersion = AppDownload::where('platform', $appDownload->platform)
                ->where('arch', $appDownload->arch)
                ->orderBy('created_at', 'desc')
                ->first()->id === $appDownload->id;

            if ($isLatestVersion) {
                // Uncomment if you want to prevent latest version deletion
                // return formatApiResponse(true, 'Cannot delete the latest version. Upload a newer version first.');
            }

            // Store file path before deletion
            $filePath = $appDownload->file_path;

            // Delete from database first
            $appDownload->delete();

            // Then delete the actual file
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return formatApiResponse(
                false,
                'App deleted successfully.',
                ['data' => ['deleted_id' => $id]]
            );
        } catch (ModelNotFoundException $e) {
            return formatApiResponse(true, 'App not found.');
        } catch (Exception $e) {
            return formatApiResponse(
                true,
                'An error occurred while deleting the app.',
                [
                    'data' => [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine()
                    ]
                ]
            );
        }
    }
}
