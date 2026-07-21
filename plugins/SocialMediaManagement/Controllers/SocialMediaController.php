<?php

namespace Plugins\SocialMediaManagement\Controllers;

use App\Services\DeletionService;
use Carbon\Carbon;
use FontLib\Table\Type\post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Config\Exception\ValidationException;
use Plugins\SocialMediaManagement\Models\SocialAccount;
use Plugins\SocialMediaManagement\Models\SocialPost;
use Plugins\SocialMediaManagement\Services\SocialMediaService;

class SocialMediaController extends Controller
{
    protected $publisher;

    public function __construct(SocialMediaService $publisher)
    {
        $this->publisher = $publisher;
    }

    public function index()
    {
        $posts = SocialPost::with('user')->orderBy('created_at', 'desc')->get();
        return view('social-media-scheduler::social-media-scheduler.index', compact('posts'));
    }

    public function create()
    {
        return view('social-media-scheduler::social-media-scheduler.create');
    }

    public function show($id)
    {
        try {
            $post = SocialPost::with(['user', 'media'])->findOrFail($id);

            return response()->json([
                'error' => false,
                'post' => [
                    'id' => $post->id,
                    'caption' => $post->caption,
                    'platforms' => $post->platforms,
                    'status' => $post->status,
                    'scheduled_at' => $post->scheduled_at ? $post->scheduled_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $post->updated_at->format('Y-m-d H:i:s'),
                    'user' => $post->user->name ?? 'Unknown',
                    'media_count' => $post->getMedia('social-media')->count(),
                    'media' => $post->getMedia('social-media')->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'name' => $media->name,
                            'url' => $media->getFullUrl(),
                            'type' => $media->mime_type
                        ];
                    }),
                    'response_logs' => $post->response_logs,
                    'successful_platforms' => $post->getSuccessfulPlatforms(),
                    'failed_platforms' => $post->getFailedPlatforms()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Post not found'
            ], 404);
        }
    }

    public function post(Request $request)
    {

        $maxVideoSize = in_array('youtube', $request->platforms ?? [])
            ? 2048000    // 2GB for YouTube
            : 10240;    // 10MB default limit

        $validator = Validator::make($request->all(), [
            'social_account_id' => 'required|exists:social_accounts,id',
            'caption' => 'nullable|string|max:5000',
            'scheduled_at' => 'nullable|date|after:now',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:facebook,instagram,linkedin,pinterest,youtube',
            'media.*' => "nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:$maxVideoSize",
            'post_type' => 'required|in:now,schedule',

            // YouTube specific
            'title' => 'nullable|string|max:100',
            'tags' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:50',
            'privacy_status' => 'nullable|in:public,private,unlisted',
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png|max:5120'
        ]);


        // Custom validation
        $validator->after(function ($validator) use ($request) {
            // Validate account is active and has required platforms
            $account = SocialAccount::find($request->social_account_id);

            if (!$account) {
                $validator->errors()->add('social_account_id', 'Social account not found.');
                return;
            }

            if ($account->status !== 'active') {
                $validator->errors()->add('social_account_id', 'Selected social account is not active.');
                return;
            }

            $requestedPlatforms = $request->platforms ?? [];
            $configuredPlatforms = $account->getConfiguredPlatforms();

            $missingPlatforms = array_diff($requestedPlatforms, $configuredPlatforms);
            if (!empty($missingPlatforms)) {
                $platformNames = array_map('ucfirst', $missingPlatforms);
                $validator->errors()->add(
                    'platforms',
                    'The selected account is not configured for: ' . implode(', ', $platformNames)
                );
            }

            // YouTube validation
            $platforms = $request->platforms ?? [];
            if (in_array('youtube', $platforms, true)) {
                if (!$request->filled('title')) {
                    $validator->errors()->add('title', 'Title is required when publishing to YouTube.');
                }
                if (!$request->filled('category')) {
                    $validator->errors()->add('category', 'Category is required when publishing to YouTube.');
                }
                if (!$request->filled('privacy_status')) {
                    $validator->errors()->add('privacy_status', 'Privacy status is required when publishing to YouTube.');
                }

                $hasVideo = false;
                if ($request->hasFile('media')) {
                    $mediaFiles = $request->file('media');
                    foreach ($mediaFiles as $file) {
                        $mime = $file->getClientMimeType();
                        if (str_starts_with($mime, 'video/')) {
                            $hasVideo = true;
                            break;
                        }
                    }
                }
                if (!$hasVideo) {
                    $validator->errors()->add('media', 'At least one video is required when publishing to YouTube.');
                }
            }

            // Schedule validation
            if ($request->post_type === 'schedule') {
                if (!$request->scheduled_at) {
                    $validator->errors()->add('scheduled_at', 'Schedule date and time is required for scheduled posts.');
                    return;
                }
                $settings = get_settings('general_settings');
                $userTz = $settings['general']['timezone'] ?? 'Asia/Kolkata';
                $scheduledLocal = \Carbon\Carbon::parse($request->scheduled_at, $userTz);
                if ($scheduledLocal->lessThanOrEqualTo(now($userTz))) {
                    $validator->errors()->add('scheduled_at', 'Schedule date and time must be in the future (your timezone).');
                }
            }

            // Mixed media validation
            if ($request->hasFile('media')) {
                $mediaFiles = $request->file('media');
                $platforms = $request->platforms ?? [];

                $hasImages = false;
                $hasVideos = false;
                foreach ($mediaFiles as $file) {
                    $mimeType = $file->getMimeType();
                    if (str_starts_with($mimeType, 'image/')) {
                        $hasImages = true;
                    } elseif (str_starts_with($mimeType, 'video/')) {
                        $hasVideos = true;
                    }
                }

                if ($hasImages && $hasVideos) {
                    $restrictedPlatforms = ['facebook', 'pinterest', 'linkedin'];
                    $conflictingPlatforms = array_intersect($platforms, $restrictedPlatforms);
                    if (!empty($conflictingPlatforms)) {
                        $platformNames = [
                            'facebook' => 'Facebook',
                            'pinterest' => 'Pinterest',
                            'linkedin' => 'LinkedIn'
                        ];
                        $readableNames = array_map(fn($platform) => $platformNames[$platform], $conflictingPlatforms);
                        $validator->errors()->add(
                            'media',
                            'Mixed media (images and videos together) is not supported on ' .
                                implode(', ', $readableNames) .
                                '. Please upload either images only or videos only for these platforms, or remove them from your selection.'
                        );
                    }
                }
            }
        });

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('media')) {
                $mediaError = $errors->first('media');
                return response()->json([
                    'error' => true,
                    'message' => $mediaError,
                    'errors' => $errors
                ], 422);
            }
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $post = new SocialPost();
            $post->user_id = Auth::id();
            $post->social_account_id = $validated['social_account_id']; // IMPORTANT: Add this
            $post->caption = $validated['caption'];
            $post->platforms = $validated['platforms'];

            $settings = get_settings('general_settings');

            if ($validated['post_type'] === 'schedule') {
                $userTimezone = $settings['general']['timezone'] ?? 'Asia/Kolkata';
                $post->scheduled_at = Carbon::parse($validated['scheduled_at'], $userTimezone)->setTimezone('UTC');
                $post->status = 'scheduled';
            } else {
                $post->scheduled_at = null;
                $post->status = 'pending';
            }

            $post->save();

            $platformMeta = is_array($post->platform_meta)
                ? $post->platform_meta
                : (!empty($post->platform_meta) ? json_decode($post->platform_meta, true) : []);

            // YouTube metadata preparation
            if (in_array('youtube', $validated['platforms'])) {
                $tags = [];
                if (!empty($validated['tags'])) {
                    $tags = array_filter(array_map('trim', explode(',', $validated['tags'])));
                }

                $youtubeDescription = $validated['caption'] ?? '';
                $youtubeDescription = html_entity_decode($youtubeDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $youtubeDescription = strip_tags($youtubeDescription);
                $youtubeDescription = preg_replace('/\s+/', ' ', $youtubeDescription);
                $youtubeDescription = trim($youtubeDescription);
                $youtubeDescription = mb_substr($youtubeDescription, 0, 5000, 'UTF-8');

                $youtubeCategory = $validated['category'] ?? null;
                $youtubeCategory = is_numeric($youtubeCategory) ? intval($youtubeCategory) : null;

                $platformMeta['youtube'] = [
                    'title' => $validated['title'] ?? '',
                    'tags' => $tags,
                    'description' => $youtubeDescription,
                    'privacy_status' => $validated['privacy_status'] ?? 'public',
                    'category' => $youtubeCategory
                ];
            }

            // Attach media files
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $post->addMedia($file)->toMediaCollection('social-media');
                }
            }

            if (in_array('youtube', $validated['platforms']) && $request->hasFile('thumbnail')) {
                $thumbnailMedia = $post->addMedia($request->file('thumbnail'))
                    ->toMediaCollection('youtube-thumbnail');
                $platformMeta['youtube']['thumbnail_media_id'] = $thumbnailMedia->id ?? null;
            }

            $post->update([
                'platform_meta' => $platformMeta
            ]);

            Log::info("Post saved with platform_meta", [
                'post_id' => $post->id,
                'platform_meta' => $post->platform_meta
            ]);

            // If post now, publish immediately
            if ($validated['post_type'] === 'now') {
                try {
                    Log::info("=== CONTROLLER: STARTING IMMEDIATE PUBLISH ===", [
                        'post_id' => $post->id,
                        'platforms' => $post->platforms,
                        'account_id' => $post->social_account_id,
                        'platform_meta' => $post->platform_meta,
                        'media_count' => $post->getMedia('social-media')->count()
                    ]);

                    $responses = $this->publisher->publishPost($post);
                    $post = $post->fresh();

                    Log::info("=== CONTROLLER: PUBLISH COMPLETED ===", [
                        'post_id' => $post->id,
                        'final_status' => $post->status,
                        'successful_platforms' => $post->getSuccessfulPlatforms(),
                        'failed_platforms' => $post->getFailedPlatforms()
                    ]);

                    $detailedErrors = $this->extractDetailedErrors($post);

                    if (in_array($post->status, ['published', 'partially_published'])) {
                        $message = $post->status === 'published'
                            ? 'Post published successfully!'
                            : 'Post partially published!';

                        $response = [
                            'error' => false,
                            'message' => $message,
                            'post_id' => $post->id,
                            'status' => $post->status,
                            'successful_platforms' => $post->getSuccessfulPlatforms(),
                            'failed_platforms' => $post->getFailedPlatforms()
                        ];

                        if ($post->status === 'partially_published' && !empty($detailedErrors)) {
                            $response['platform_errors'] = $detailedErrors;
                        }

                        return response()->json($response);
                    } else {
                        return response()->json([
                            'error' => true,
                            'message' => 'Failed to publish to any platform',
                            'post_id' => $post->id,
                            'status' => $post->status,
                            'failed_platforms' => $post->getFailedPlatforms(),
                            'platform_errors' => $detailedErrors,
                            'exception' => config('app.debug') ? true : null,
                            'debug_info' => config('app.debug') ? [
                                'response_logs' => $post->response_logs
                            ] : null
                        ], 500);
                    }
                } catch (\Exception $e) {
                    $errorDetails = [
                        'service_error' => [
                            'success' => false,
                            'status' => 'failed',
                            'error' => $e->getMessage(),
                            'error_code' => 'SERVICE_EXCEPTION',
                            'failed_at' => now()->toISOString(),
                            'exception' => config('app.debug') ? true : null,
                            'exception_details' => [
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'trace' => config('app.debug') ? $e->getTraceAsString() : null
                            ]
                        ]
                    ];

                    $post->update([
                        'status' => 'failed',
                        'response_logs' => $errorDetails
                    ]);

                    Log::error('=== CONTROLLER: EXCEPTION DURING PUBLISH ===', [
                        'post_id' => $post->id,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    return response()->json([
                        'error' => true,
                        'message' => 'Post created but failed to publish',
                        'post_id' => $post->id,
                        'platform_errors' => [
                            'service_exception' => [
                                'error' => $e->getMessage(),
                                'error_code' => 'SERVICE_EXCEPTION',
                                'file' => config('app.debug') ? $e->getFile() : null,
                                'line' => config('app.debug') ? $e->getLine() : null
                            ]
                        ],
                        'debug_info' => config('app.debug') ? [
                            'exception_trace' => $e->getTraceAsString()
                        ] : null
                    ], 500);
                }
            }

            return response()->json([
                'error' => false,
                'message' => 'Post scheduled successfully!',
                'post_id' => $post->id,
                'scheduled_for' => $post->scheduled_at->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating social post: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['media'])
            ]);

            return response()->json([
                'error' => true,
                'message' => 'An error occurred while creating the post',
                'error_details' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }


    /**
     * Extract detailed error information from post response logs
     */
    private function extractDetailedErrors(SocialPost $post): array
    {
        $detailedErrors = [];
        $responseLogs = $post->response_logs ?? [];

        foreach ($responseLogs as $platform => $log) {
            if (isset($log['success']) && !$log['success']) {
                $detailedErrors[$platform] = [
                    'error' => $log['error'] ?? 'Unknown error',
                    'error_code' => $log['error_code'] ?? null,
                    'status_code' => $log['status_code'] ?? null,
                    'failed_at' => $log['failed_at'] ?? null,
                    'api_response' => config('app.debug') ? ($log['api_response'] ?? null) : null
                ];

                // Remove null values to keep response clean
                $detailedErrors[$platform] = array_filter($detailedErrors[$platform], function ($value) {
                    return $value !== null;
                });
            }
        }

        return $detailedErrors;
    }

    function getSocialPlatformsStatus(): array
    {
        $socialSettings = get_settings('social_settings');

        return [
            'facebook' => !empty($socialSettings['facebook_access_token']) && !empty($socialSettings['facebook_page_id']),
            'instagram' => !empty($socialSettings['instagram_access_token']) && !empty($socialSettings['instagram_business_account_id']),
            'linkedin' => !empty($socialSettings['linkedin_access_token']) && !empty($socialSettings['linkedin_person_id']),
            'pinterest' => !empty($socialSettings['pinterest_app_id'])
                && !empty($socialSettings['pinterest_app_secret'])
                && !empty($socialSettings['pinterest_app_type']),
            'youtube' => !empty($socialSettings['youtube_access_token'])
                && !empty($socialSettings['youtube_refresh_token']),
        ];
    }


    public function list()
    {
        $search = request('search');
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $limit = request('limit', 10);
        $offset = request('offset', 0);
        $startDate = request()->input('start_date');
        $endDate = request()->input('end-date');
        $status = request()->input('status');
        $platform = request()->input('platform');
        $account = request()->input('account');
// dd($account);
        $query = SocialPost::with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('caption', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%")
                    ->orWhereJsonContains('platforms', $search);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($platform) {
            $query->whereJsonContains('platforms', $platform);
        }

        if ($account) {
            $query->where('social_account_id', $account);
        }

        if ($startDate & $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $posts = $query->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $canEdit = (isAdminOrHasAllDataAccess() || auth()->user()->can('edit_posts'));
        $canDelete = (isAdminOrHasAllDataAccess() || auth()->user()->can('delete_posts'));

        $rows = $posts->map(function ($post) use ($canDelete, $canEdit) {
            $actions = '';

            // Delete button
            if ($canDelete) {
                $actions .= '<button type="button" class="btn delete btn-sm"
                                    data-id="' . $post->id . '"
                                    data-type="social-media-scheduler"
                                    title="' . get_label('delete', 'Delete') . '">
                                    <i class="bx bx-trash text-danger"></i>
                                </button>';
            }

            $actions .= '<a href="' . route('social.post.detail', $post->id) . '"
                class="btn btn-sm me-1"
                title="View Details">
                <i class="bx bx-info-circle text-primary"></i>
            </a>';

            // Quick View button
            $actions .= '<button type="button" class="btn btn-sm me-1"
                data-bs-toggle="modal"
                data-bs-target="#quickViewModal"
                onclick="showPostQuickView(' . $post->id . ')"
                title="Quick View">
                <i class="bx bx-show-alt text-info"></i>
            </button>';

            // Edit button (only for pending and scheduled posts)
            if ($canEdit && in_array($post->status, ['pending', 'scheduled'])) {
                $actions .= '<a href="' . route('social.edit', $post->id) . '" class="edit-candidate-btn"
                data-post=\'' . htmlspecialchars(json_encode($post), ENT_QUOTES, 'UTF-8') . '\'
                title="' . get_label('update', 'Update') . '">
                <i class="bx bx-edit mx-1"></i>
            </a>';
            }

            $socialPlatforms = config('social.platforms', []);

            $platformsDisplay = collect($post->platforms)->map(function ($platform) use ($socialPlatforms) {

                if (isset($socialPlatforms[$platform])) {
                    $icon = $socialPlatforms[$platform]['icon'];
                    $color = $socialPlatforms[$platform]['color'];
                } else {
                    // fallback
                    $icon = 'bxl-question-circle';
                    $color = '#6c757d'; // gray
                }

                return '<span class="badge bg-light text-dark me-1">
                <i class="bx ' . $icon . ' me-1" style="color: ' . $color . ';"></i>' . ucfirst($platform) . '
            </span>';
            })->implode('');


            // Status badge with better styling
            $statusClasses = [
                'published' => 'bg-success',
                'scheduled' => 'bg-warning',
                'failed' => 'bg-danger',
                'pending' => 'bg-secondary',
                'partially_published' => 'bg-primary',
            ];
            $statusClass = $statusClasses[$post->status] ?? 'bg-secondary';

            // Media count
            $mediaCount = $post->getMedia('social-media')->count();
            $mediaDisplay = $mediaCount > 0 ?
                '<small class="text-muted"><i class="bx bx-image me-1"></i>' . $mediaCount . '</small>' : '-';

            $postStatus = str_replace('_', ' ', ucfirst($post->status));

            return [
                'id' => $post->id,
                'caption' => $post->caption ? Str::limit($post->caption, 50) : '-',
                'platforms' => $platformsDisplay,
                'media' => $mediaDisplay,
                'status' => '<span class="badge ' . $statusClass . '">' . ucfirst($postStatus) . '</span>',
                'account' => $post->socialAccount->name ?? 'N/A',
                'scheduled_at' => $post->scheduled_at ? format_date($post->scheduled_at, true) : '-',
                'created_at' => format_date($post->created_at, false, 'Y-m-d H:i'),
                'updated_at' => format_date($post->updated_at, false, 'Y-m-d H:i'),
                // 'published_at' => $post->response_logs['published_at'] ? format_date($post->response_logs['published_at'], false, 'Y-m-d H:i') : '-',
                'actions' => $actions
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    public function edit($id)
    {

        $post = SocialPost::findOrFail($id);
        // Decode platform_meta if it's a JSON string
        $platformMeta = is_string($post->platform_meta)
            ? json_decode($post->platform_meta, true)
            : ($post->platform_meta ?? []);

        // Extract YouTube metadata
        $youtubeMeta = $platformMeta['youtube'] ?? [];
        return view('social-media-scheduler::social-media-scheduler.edit', compact('post', 'youtubeMeta'));
    }

    public function update(Request $request, $id)
    {
        try {
            $post = SocialPost::findOrFail($id);

            if (!in_array($post->status, ['pending', 'scheduled'])) {
                return response()->json([
                    'error' => true,
                    'message' => 'Only pending and scheduled posts can be edited.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'caption' => 'nullable|string|max:2000',
                'scheduled_at' => 'nullable|date|after:now',
                'platforms' => 'required|array|min:1',
                'platforms.*' => 'in:facebook,instagram,linkedin,pinterest,youtube',
                'post_type' => 'required|in:now,schedule'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            $post->caption = $validated['caption'];
            $post->platforms = $validated['platforms'];

            if ($validated['post_type'] === 'schedule') {
                $userTimezone = $settings['general']['timezone'] ?? 'Asia/Kolkata';
                $post->scheduled_at = Carbon::parse($validated['scheduled_at'], $userTimezone)->setTimezone('UTC');
                $post->status = 'scheduled';
            } else {
                $post->scheduled_at = null;
                $post->status = 'pending';
            }

            $post->save();

            // Attach media files
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $post->addMedia($file)->toMediaCollection('social-media');
                }
            }
            return response()->json([
                'error' => false,
                'message' => 'Post updated successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating post: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $post = SocialPost::findOrFail($id);
            $response = DeletionService::delete(SocialPost::class, $post->id, 'Post');
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Post not found'
            ], 404);
        }
    }

    public function destroy_multiple(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:social_posts,id'
        ]);

        $ids = $validatedData['ids'];
        $deletedIds = [];

        foreach ($ids as $id) {
            try {
                $post = SocialPost::findOrFail($id);
                DeletionService::delete(SocialPost::class, $post->id, 'Post');
                $deletedIds[] = $id;
            } catch (\Exception $e) {
                Log::error("Error deleting post {$id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'Post(s) deleted successfully!',
            'id' => $deletedIds,
        ]);
    }

    public function destroyMedia($id)
    {
        try {
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($id);
            $media->delete();

            return response()->json([
                'error' => false,
                'message' => 'Media deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Media not found'
            ], 404);
        }
    }

    public function generateSocialCaption(Request $request)
    {
        $request->validate([
            'prompt' => 'nullable|string|max:500',
            'existingCaption' => 'nullable|string',
            'isCustomPrompt' => 'required|in:true,false,0,1,TRUE,FALSE',
            'platforms' => 'nullable|array',
            'platforms.*' => 'in:facebook,instagram,linkedin,pinterest,youtube'
        ]);

        try {
            $userPrompt = $request->input('prompt', '');
            $existingCaption = $request->input('existingCaption', '');
            $isCustomPrompt = filter_var($request->input('isCustomPrompt'), FILTER_VALIDATE_BOOLEAN);
            $platforms = $request->input('platforms', []);
            $currentLocale = session('my_locale', 'en');

            // Determine platform context
            $platformContext = '';
            $maxLength = 2000; // Default max length

            if (!empty($platforms)) {
                $platformNames = [
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'linkedin' => 'LinkedIn',
                    'pinterest' => 'Pinterest',
                    'youtube' => 'YouTube'
                ];

                $selectedPlatformNames = array_map(function ($platform) use ($platformNames) {
                    return $platformNames[$platform] ?? $platform;
                }, $platforms);

                $platformContext = "Target platforms: " . implode(', ', $selectedPlatformNames) . ".";

                // Set max length based on most restrictive platform
                if (in_array('pinterest', $platforms)) {
                    $maxLength = 500;
                } elseif (in_array('instagram', $platforms)) {
                    $maxLength = min($maxLength, 2200);
                } elseif (in_array('linkedin', $platforms)) {
                    $maxLength = min($maxLength, 3000);
                }
            }

            if ($isCustomPrompt) {
                // Validate custom prompt is provided
                if (empty(trim($userPrompt))) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Custom prompt is required when custom prompt mode is enabled.'
                    ], 422);
                }

                $fullPrompt = "Create a social media caption based on the custom request.

{$platformContext}

User Request:
{$userPrompt}

" . ($existingCaption ? "Current Caption (to improve/replace):\n{$existingCaption}\n" : "No existing caption.") . "

Instructions:
- Create an engaging, platform-appropriate social media caption
- Use HTML formatting: <p> for paragraphs, <strong> for emphasis, <br> for line breaks
- Include relevant emojis and hashtags where appropriate
- Keep under {$maxLength} characters total
- Make it conversational and authentic
- Language: {$currentLocale}

Output only the HTML caption content—no explanations or extra text.";
            } else {
                // Auto-generate mode - improve existing or create new
                $action = $existingCaption ? "enhance and improve" : "create";

                $fullPrompt = "Create an engaging social media caption.

{$platformContext}

" . ($existingCaption ? "Current Caption:\n{$existingCaption}\n" : "No existing caption - create from scratch.") . "

Instructions:
- {$action} a compelling social media caption
- Use HTML formatting: <p> for paragraphs, <strong> for emphasis, <br> for line breaks
- Include appropriate emojis and relevant hashtags
- Keep under {$maxLength} characters total
- Make it engaging, authentic, and platform-appropriate
- Include a call-to-action or conversation starter
- Language: {$currentLocale}

Output only the HTML caption content—no explanations or extra text.";
            }

            // Call your AI generation function
            $result = generate_description($fullPrompt);

            if ($result['error']) {
                return response()->json([
                    'error' => true,
                    'message' => $result['message'] ?? 'Failed to generate caption. Please try again.',
                ], 200);
            }

            // Clean up the generated content
            $generatedCaption = $result['data'] ?? '';

            // Remove any code block markers
            $generatedCaption = preg_replace('/^```html\s*|```$/m', '', $generatedCaption);
            $generatedCaption = preg_replace('/^```\s*|```$/m', '', $generatedCaption);
            $generatedCaption = trim($generatedCaption);

            // Validate length
            $textLength = strlen(strip_tags($generatedCaption));
            if ($textLength > $maxLength) {
                // Try to truncate gracefully
                $generatedCaption = $this->truncateCaption($generatedCaption, $maxLength);
            }

            return response()->json([
                'error' => false,
                'message' => 'Caption generated successfully!',
                'caption' => $generatedCaption,
                'character_count' => strlen(strip_tags($generatedCaption)),
                'platforms' => $platforms
            ]);
        } catch (\Exception $e) {
            Log::error('Social Media Caption AI Generation Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => auth()->id(),
                'prompt' => $userPrompt,
                'platforms' => $platforms ?? []
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to generate caption. Please try again or contact support if the issue persists.',
            ], 500);
        }
    }

    /**
     * Helper method to truncate caption while preserving HTML structure
     */
    private function truncateCaption($html, $maxLength)
    {
        $text = strip_tags($html);
        if (strlen($text) <= $maxLength) {
            return $html;
        }

        // Find a good breaking point (sentence end, word boundary, etc.)
        $truncated = substr($text, 0, $maxLength - 10); // Leave some buffer
        $lastSpace = strrpos($truncated, ' ');
        $lastSentence = strrpos($truncated, '.');

        $breakPoint = max($lastSpace, $lastSentence);
        if ($breakPoint > $maxLength * 0.8) { // Don't break too early
            $truncated = substr($text, 0, $breakPoint + ($lastSentence === $breakPoint ? 1 : 0));
        }

        // Simple HTML preservation - wrap in paragraph if needed
        if (!preg_match('/^<p/', trim($truncated))) {
            $truncated = '<p>' . $truncated . '</p>';
        }

        return $truncated;
    }

    /**
     * Display calendar view
     */
    public function calendar()
    {
        $platforms = config('social.platforms', []);
        return view('social-media-scheduler::social-media-scheduler.calendar', compact('platforms'));
    }

    /**
     * Get calendar data for both month and week views (Enhanced with multi-account support)
     */
    public function getCalendarData(Request $request)
    {
        try {
            $view = $request->input('view', 'month');
            $accountId = $request->input('account_id'); // NEW: Get account filter

            // Get user timezone from settings
            $settings = get_settings('general_settings');
            $userTimezone = $settings['timezone'] ?? 'Asia/Kolkata';

            // DEBUG: Log timezone info
            Log::info('=== CALENDAR DEBUG ===', [
                'user_timezone'   => $userTimezone,
                'server_timezone' => config('app.timezone'),
                'current_utc'     => now('UTC')->toDateTimeString(),
                'current_user_tz' => now($userTimezone)->toDateTimeString(),
                'account_id'      => $accountId
            ]);

            // -------------------
            // Handle week view
            // -------------------
            if ($view === 'week') {
                $startDate = $request->input('start_date');
                $endDate   = $request->input('end_date');

                if (!$startDate || !$endDate) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Start date and end date are required for week view'
                    ], 400);
                }

                // Parse in USER timezone and convert to UTC for DB query
                $userStart = Carbon::createFromFormat('Y-m-d', $startDate, $userTimezone)->startOfDay();
                $userEnd   = Carbon::createFromFormat('Y-m-d', $endDate, $userTimezone)->endOfDay();

                // Convert to UTC for DB query
                $startDate = $userStart->copy()->utc();
                $endDate   = $userEnd->copy()->utc();
            }

            // -------------------
            // Handle month view
            // -------------------
            else {
                $month = $request->input('month', now()->month);
                $year  = $request->input('year', now()->year);

                // Validate month/year
                if ($month < 1 || $month > 12) {
                    $month = now()->month;
                }
                if ($year < 2020 || $year > 2030) {
                    $year = now()->year;
                }

                // Create range in USER timezone
                $userStart = Carbon::create($year, $month, 1, 0, 0, 0, $userTimezone)
                    ->startOfMonth()
                    ->startOfWeek();

                $userEnd = Carbon::create($year, $month, 1, 0, 0, 0, $userTimezone)
                    ->endOfMonth()
                    ->endOfWeek();

                // Convert to UTC for DB query
                $startDate = $userStart->copy()->utc();
                $endDate   = $userEnd->copy()->utc();
            }

            // DEBUG: Log date ranges
            Log::info('=== DATE RANGES ===', [
                'query_start_utc'    => $startDate->toDateTimeString(),
                'query_end_utc'      => $endDate->toDateTimeString(),
                'user_start'         => $userStart->setTimezone($userTimezone)->toDateTimeString(),
                'user_end'           => $userEnd->setTimezone($userTimezone)->toDateTimeString()
            ]);

            // Build query with eager loading
            $query = SocialPost::with(['user', 'socialAccount'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('scheduled_at', [$startDate, $endDate])
                        ->orWhereBetween('created_at', [$startDate, $endDate])
                        ->orWhereBetween('updated_at', [$startDate, $endDate]);
                });

            // NEW: Apply account access control
            if (!isAdminOrHasAllDataAccess()) {
                // Regular users: only posts from accounts they created
                $query->whereHas('socialAccount', function ($q) {
                    $q->where('created_by', auth()->id());
                });
            }

            // NEW: Filter by specific account if provided
            if ($accountId && $accountId !== 'all') {
                $query->where('social_account_id', $accountId);
            }

            $posts = $query->orderBy('scheduled_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Prepare containers
            $calendarData = [];
            $totalStats = [
                'published' => 0,
                'scheduled' => 0,
                'failed' => 0,
                'partially_published' => 0,
                'pending' => 0
            ];

            // -------------------
            // Group posts by day
            // -------------------
            foreach ($posts as $post) {
                // Get the primary date for grouping - prioritize scheduled_at
                $primaryDate = $post->scheduled_at ?: $post->created_at;

                // Ensure we have a Carbon instance and convert to user timezone ONCE
                if (!$primaryDate instanceof Carbon) {
                    $primaryDate = Carbon::parse($primaryDate);
                }

                // Convert from UTC (database) to user timezone for display grouping
                $postDateInUserTz = $primaryDate->copy()->setTimezone($userTimezone);

                // Use the date in user timezone for grouping
                $dateKey = $postDateInUserTz->format('Y-m-d');

                // DEBUG first few posts
                if (count($calendarData) < 3) {
                    Log::info('=== POST DEBUG ===', [
                        'post_id'                => $post->id,
                        'account_id'             => $post->social_account_id,
                        'account_name'           => $post->socialAccount ? $post->socialAccount->name : 'N/A',
                        'scheduled_at_utc'       => $post->scheduled_at ? $post->scheduled_at->utc()->toDateTimeString() : null,
                        'created_at_utc'         => $post->created_at->utc()->toDateTimeString(),
                        'primary_date_utc'       => $primaryDate->utc()->toDateTimeString(),
                        'post_date_user_tz'      => $postDateInUserTz->toDateTimeString(),
                        'date_key'               => $dateKey,
                        'user_timezone'          => $userTimezone
                    ]);
                }

                // Initialize date bucket if not exists
                if (!isset($calendarData[$dateKey])) {
                    $calendarData[$dateKey] = [];
                }

                // Get platforms (names only - icons/colors handled in frontend)
                $platforms = is_array($post->platforms) ? $post->platforms : json_decode($post->platforms, true) ?? [];

                // Update stats
                if (isset($totalStats[$post->status])) {
                    $totalStats[$post->status]++;
                }

                // Get media count
                $mediaCount = 0;
                if (method_exists($post, 'getMedia')) {
                    $mediaCount = $post->getMedia('social-media')->count();
                } elseif (isset($post->media_count)) {
                    $mediaCount = $post->media_count;
                }

                // Get successful and failed platforms
                $successfulPlatforms = method_exists($post, 'getSuccessfulPlatforms')
                    ? $post->getSuccessfulPlatforms()
                    : [];
                $failedPlatforms = method_exists($post, 'getFailedPlatforms')
                    ? $post->getFailedPlatforms()
                    : [];

                // All datetime conversions done consistently
                $calendarData[$dateKey][] = [
                    'id'                   => $post->id,
                    'caption'              => $this->sanitizeCaption($post->caption),
                    'status'               => $post->status,
                    'time'                 => $postDateInUserTz->format('H:i'),
                    'platforms'            => $platforms,
                    'media_count'          => $mediaCount,
                    'user'                 => $post->user->name ?? 'Unknown',
                    'account_id'           => $post->social_account_id, // NEW
                    'account_name'         => $post->socialAccount ? $post->socialAccount->name : 'N/A', // NEW
                    'is_scheduled'         => $post->scheduled_at !== null,
                    'successful_platforms' => $successfulPlatforms,
                    'failed_platforms'     => $failedPlatforms,
                    'created_at'           => $post->created_at->copy()->setTimezone($userTimezone)->format('Y-m-d H:i:s'),
                    'updated_at'           => $post->updated_at->copy()->setTimezone($userTimezone)->format('Y-m-d H:i:s'),
                    'scheduled_at'         => $post->scheduled_at ? $post->scheduled_at->copy()->setTimezone($userTimezone)->format('Y-m-d H:i:s') : null,
                    'display_date'         => $postDateInUserTz->format('Y-m-d H:i:s'),
                    'display_date_iso'     => $postDateInUserTz->toISOString()
                ];
            }

            // DEBUG: Log final keys and verify grouping
            Log::info('=== CALENDAR DATA SUMMARY ===', [
                'calendar_dates'     => array_keys($calendarData),
                'total_posts'        => $posts->count(),
                'posts_per_date'     => array_map('count', $calendarData),
                'user_timezone'      => $userTimezone,
                'account_filter'     => $accountId ?? 'all',
                'sample_post_dates'  => array_slice(array_keys($calendarData), 0, 5)
            ]);

            // Response
            $responseData = [
                'error'           => false,
                'data'            => $calendarData,
                'stats'           => $totalStats,
                'total_posts'     => $posts->count(),
                'view'            => $view,
                'timezone'        => $userTimezone,
                'selected_account' => $accountId ?? 'all' // NEW
            ];

            if ($view === 'week') {
                $responseData['start_date'] = Carbon::parse($request->input('start_date'))->format('Y-m-d');
                $responseData['end_date']   = Carbon::parse($request->input('end_date'))->format('Y-m-d');
            } else {
                $responseData['month']      = $month ?? now()->month;
                $responseData['year']       = $year ?? now()->year;
                $responseData['month_name'] = Carbon::create($year ?? now()->year, $month ?? now()->month, 1)->format('F Y');
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Error fetching calendar data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'view' => $request->input('view'),
                'account_id' => $request->input('account_id'),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error'   => true,
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to load calendar data'
            ], 500);
        }
    }

    /**
     * Get posts for a specific date (for daily view or detailed view)
     */
    public function getPostsByDate(Request $request)
    {
        try {
            $date = $request->input('date');
            $accountId = $request->input('account_id'); // NEW

            if (!$date) {
                return response()->json([
                    'error' => true,
                    'message' => 'Date parameter is required'
                ], 400);
            }

            // Get user timezone
            $settings = get_settings('general_settings');
            $userTimezone = $settings['timezone'] ?? 'Asia/Kolkata';

            // Parse the date in user timezone and convert to UTC for database query
            $targetDate = Carbon::parse($date, $userTimezone);
            $startOfDay = $targetDate->copy()->startOfDay()->utc();
            $endOfDay = $targetDate->copy()->endOfDay()->utc();

            $query = SocialPost::with(['user', 'socialAccount']) // NEW: Added socialAccount
                ->where(function ($query) use ($startOfDay, $endOfDay) {
                    $query->whereBetween('scheduled_at', [$startOfDay, $endOfDay])
                        ->orWhereBetween('created_at', [$startOfDay, $endOfDay]);
                });

            // NEW: Apply account access control
            if (!isAdminOrHasAllDataAccess()) {
                $query->whereHas('socialAccount', function ($q) {
                    $q->where('created_by', auth()->id());
                });
            }

            // NEW: Filter by specific account if provided
            if ($accountId && $accountId !== 'all') {
                $query->where('social_account_id', $accountId);
            }

            $posts = $query->orderBy('scheduled_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            $postsData = $posts->map(function ($post) use ($userTimezone) {
                $platforms = is_array($post->platforms) ? $post->platforms : json_decode($post->platforms, true) ?? [];

                $media = [];
                $mediaCount = 0;

                if (method_exists($post, 'getMedia')) {
                    $mediaCollection = $post->getMedia('social-media');
                    $mediaCount = $mediaCollection->count();
                    $media = $mediaCollection->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'name' => $media->name,
                            'url' => $media->getFullUrl(),
                            'type' => $media->mime_type
                        ];
                    });
                }

                return [
                    'id' => $post->id,
                    'caption' => $post->caption,
                    'status' => $post->status,
                    'platforms' => $platforms,
                    'account_id' => $post->social_account_id, // NEW
                    'account_name' => $post->socialAccount ? $post->socialAccount->name : 'N/A', // NEW
                    'scheduled_at' => $post->scheduled_at ? $post->scheduled_at->setTimezone($userTimezone)->format('Y-m-d H:i:s') : null,
                    'created_at' => $post->created_at->setTimezone($userTimezone)->format('Y-m-d H:i:s'),
                    'updated_at' => $post->updated_at->setTimezone($userTimezone)->format('Y-m-d H:i:s'),
                    'user' => $post->user->name ?? 'Unknown',
                    'media_count' => $mediaCount,
                    'media' => $media,
                    'successful_platforms' => method_exists($post, 'getSuccessfulPlatforms')
                        ? $post->getSuccessfulPlatforms()
                        : [],
                    'failed_platforms' => method_exists($post, 'getFailedPlatforms')
                        ? $post->getFailedPlatforms()
                        : []
                ];
            });

            return response()->json([
                'error' => false,
                'data' => $postsData,
                'date' => $date,
                'total' => $posts->count(),
                'selected_account' => $accountId ?? 'all' // NEW
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching posts by date: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Failed to load posts for the specified date'
            ], 500);
        }
    }

    /**
     * Get calendar statistics
     */
    public function getCalendarStats(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $accountId = $request->input('account_id'); // NEW

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $query = SocialPost::whereBetween('created_at', [$startDate, $endDate]);

            // NEW: Apply account access control
            if (!isAdminOrHasAllDataAccess()) {
                $query->whereHas('socialAccount', function ($q) {
                    $q->where('created_by', auth()->id());
                });
            }

            // NEW: Filter by specific account if provided
            if ($accountId && $accountId !== 'all') {
                $query->where('social_account_id', $accountId);
            }

            $stats = [
                'total' => $query->count(),
                'published' => $query->clone()->where('status', 'published')->count(),
                'scheduled' => $query->clone()->where('status', 'scheduled')->count(),
                'failed' => $query->clone()->where('status', 'failed')->count(),
                'partially_published' => $query->clone()->where('status', 'partially_published')->count(),
                'pending' => $query->clone()->where('status', 'pending')->count(),
            ];

            // Platform breakdown
            $platformStats = [];
            $platforms = ['facebook', 'instagram', 'linkedin', 'pinterest', 'youtube'];

            foreach ($platforms as $platform) {
                $platformStats[$platform] = $query->clone()
                    ->where(function ($q) use ($platform) {
                        $q->whereRaw("JSON_CONTAINS(platforms, '\"" . $platform . "\"')")
                            ->orWhereRaw("JSON_CONTAINS(platforms, '\"" . ucfirst($platform) . "\"')");
                    })
                    ->count();
            }

            return response()->json([
                'error' => false,
                'stats' => $stats,
                'platform_stats' => $platformStats,
                'month' => $month,
                'year' => $year,
                'selected_account' => $accountId ?? 'all' // NEW
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching calendar stats: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }



    /**
     * Helper method to sanitize caption text
     */
    private function sanitizeCaption($caption)
    {
        if (!$caption) return '';

        // Remove HTML tags and decode entities
        $caption = html_entity_decode(strip_tags($caption));

        // Limit length for display
        return strlen($caption) > 100 ? substr($caption, 0, 100) . '...' : $caption;
    }



    /**
     * Display analytics dashboard
     */
    public function analytics()
    {
        return view('social-media-scheduler::social-media-scheduler.analytics');
    }

    /**
     * Get comprehensive analytics data
     */
    public function getAnalyticsData(Request $request)
    {
        try {
            $dateRange = $request->input('date_range', '30');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $accountId = $request->input('account_id');

            // Set date range
            if ($startDate && $endDate) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
            } else {
                $end = Carbon::now();
                $start = match ($dateRange) {
                    '7' => $end->copy()->subDays(7),
                    '30' => $end->copy()->subDays(30),
                    '90' => $end->copy()->subDays(90),
                    '365' => $end->copy()->subYear(),
                    default => $end->copy()->subDays(30)
                };
            }

            // Build query with user's accounts only
            $query = SocialPost::whereBetween('created_at', [$start, $end])
                ->whereHas('socialAccount', function ($q) {
                    $q->where('created_by', auth()->id());
                });

            // Filter by specific account if provided
            if ($accountId && $accountId !== 'all') {
                $query->where('social_account_id', $accountId);
            }

            $posts = $query->get();

            // Overall Statistics
            $totalPosts = $posts->count();
            $publishedCount = $posts->where('status', 'published')->count();
            $partiallyPublishedCount = $posts->where('status', 'partially_published')->count();
            $successfulPosts = $publishedCount + $partiallyPublishedCount;

            $overallStats = [
                'total_posts' => $totalPosts,
                'published' => $publishedCount,
                'partially_published' => $partiallyPublishedCount,
                'scheduled' => $posts->where('status', 'scheduled')->count(),
                'failed' => $posts->where('status', 'failed')->count(),
                'pending' => $posts->where('status', 'pending')->count(),
                'total_media_files' => $posts->sum(function ($post) {
                    return method_exists($post, 'getMedia') ? $post->getMedia('social-media')->count() : 0;
                }),
                'success_rate' => $totalPosts > 0 ? round(($successfulPosts / $totalPosts) * 100, 2) : 0
            ];

            // Platform Statistics
            $platformStats = [];
            $allPlatforms = array_keys(config('social.platforms'));

            foreach ($allPlatforms as $platform) {
                $platformPosts = $posts->filter(function ($post) use ($platform) {
                    $postPlatforms = $post->platforms;
                    return in_array($platform, array_map('strtolower', $postPlatforms));
                });

                $successfulCount = 0;
                $failedCount = 0;

                foreach ($platformPosts as $post) {
                    if ($post->response_logs && isset($post->response_logs[$platform])) {
                        if ($post->response_logs[$platform]['success'] ?? false) {
                            $successfulCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                }

                $platformStats[$platform] = [
                    'total_posts' => $platformPosts->count(),
                    'successful' => $successfulCount,
                    'failed' => $failedCount,
                    'success_rate' => $platformPosts->count() > 0 ?
                        round(($successfulCount / $platformPosts->count()) * 100, 2) : 0
                ];
            }

            // Daily Activity Chart Data
            $chartStart = $end->copy()->subDays(29);
            $dailyStats = [];

            for ($date = $chartStart->copy(); $date->lte($end); $date->addDay()) {
                $dayPosts = $posts->filter(function ($post) use ($date) {
                    return $post->created_at->format('Y-m-d') === $date->format('Y-m-d');
                });

                $dailyStats[] = [
                    'date' => $date->format('Y-m-d'),
                    'total' => $dayPosts->count(),
                    'published' => $dayPosts->where('status', 'published')->count(),
                    'partially_published' => $dayPosts->where('status', 'partially_published')->count(),
                    'scheduled' => $dayPosts->where('status', 'scheduled')->count(),
                    'failed' => $dayPosts->where('status', 'failed')->count(),
                    'pending' => $dayPosts->where('status', 'pending')->count()
                ];
            }

            // Status Distribution - FIXED VERSION
            $statusDistribution = [];
            $statusColors = config('social.status_colors', []);

            // Debug: Log the config value
            \Log::info('Status Colors Config:', ['colors' => $statusColors]);

            // If config is null or empty, use fallback colors
            if (empty($statusColors)) {
                $statusColors = [
                    'published' => '#28a745',
                    'partially_published' => '#007bff',
                    'scheduled' => '#ffc107',
                    'failed' => '#dc3545',
                    'pending' => '#6c757d',
                ];
            }

            foreach ($statusColors as $status => $color) {
                $statusKey = $status === 'partially_published' ? 'partially_published' : $status;
                $count = $overallStats[$statusKey] ?? 0;

                $statusDistribution[] = [
                    'status' => ucwords(str_replace('_', ' ', $status)),
                    'count' => $count,
                    'color' => $color ?? '#6c757d' // Fallback color
                ];
            }

            // Platform Distribution
            $platformDistribution = [];
            foreach ($platformStats as $platform => $stats) {
                if ($stats['total_posts'] > 0) {
                    $platformDistribution[] = [
                        'platform' => ucfirst($platform),
                        'count' => $stats['total_posts'],
                        'color' => config("social.platforms.$platform.color", '#6c757d')
                    ];
                }
            }

            // Recent Activity
            $recentActivity = $posts->sortByDesc('created_at')
                ->take(10)
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'caption' => $this->sanitizeCaption($post->caption),
                        'status' => $post->status,
                        'platforms' => $post->platforms,
                        'account_name' => $post->socialAccount ? $post->socialAccount->name : 'N/A',
                        'created_at' => $post->created_at->format('M d, Y H:i'),
                        'scheduled_at' => $post->scheduled_at ? $post->scheduled_at->format('M d, Y H:i') : null,
                        'media_count' => method_exists($post, 'getMedia') ? $post->getMedia('social-media')->count() : 0
                    ];
                })
                ->values();

            // Peak Hours Analysis
            $hourlyStats = array_fill(0, 24, 0);
            foreach ($posts as $post) {
                $hour = (int) $post->created_at->format('H');
                $hourlyStats[$hour]++;
            }

            $peakHours = [];
            for ($i = 0; $i < 24; $i++) {
                $peakHours[] = [
                    'hour' => sprintf('%02d:00', $i),
                    'count' => $hourlyStats[$i]
                ];
            }

            // Scheduling Statistics
            $schedulingStats = [
                'immediate' => $posts->whereNull('scheduled_at')->count(),
                'scheduled' => $posts->whereNotNull('scheduled_at')->count()
            ];

            // Media Usage Statistics
            $postsWithMedia = $posts->filter(function ($post) {
                return method_exists($post, 'getMedia') && $post->getMedia('social-media')->count() > 0;
            })->count();

            $mediaStats = [
                'posts_with_media' => $postsWithMedia,
                'posts_without_media' => $totalPosts - $postsWithMedia,
                'total_media_files' => $overallStats['total_media_files'],
                'avg_media_per_post' => $totalPosts > 0 ?
                    round($overallStats['total_media_files'] / $totalPosts, 2) : 0
            ];

            // Account-wise statistics
            $accountStats = [];
            if (!$accountId || $accountId === 'all') {
                $accounts = SocialAccount::where('created_by', auth()->id())
                    ->active()
                    ->get();

                foreach ($accounts as $account) {
                    $accountPosts = $posts->where('social_account_id', $account->id);
                    $accountPublished = $accountPosts->where('status', 'published')->count();
                    $accountPartiallyPublished = $accountPosts->where('status', 'partially_published')->count();
                    $accountSuccessful = $accountPublished + $accountPartiallyPublished;

                    $accountStats[] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'total_posts' => $accountPosts->count(),
                        'published' => $accountPublished,
                        'partially_published' => $accountPartiallyPublished,
                        'scheduled' => $accountPosts->where('status', 'scheduled')->count(),
                        'failed' => $accountPosts->where('status', 'failed')->count(),
                        'success_rate' => $accountPosts->count() > 0 ?
                            round(($accountSuccessful / $accountPosts->count()) * 100, 2) : 0,
                        'platforms' => $account->getConfiguredPlatforms()
                    ];
                }
            }

            return response()->json([
                'error' => false,
                'data' => [
                    'overall_stats' => $overallStats,
                    'platform_stats' => $platformStats,
                    'daily_activity' => $dailyStats,
                    'status_distribution' => $statusDistribution,
                    'platform_distribution' => $platformDistribution,
                    'recent_activity' => $recentActivity,
                    'peak_hours' => $peakHours,
                    'scheduling_stats' => $schedulingStats,
                    'media_stats' => $mediaStats,
                    'account_stats' => $accountStats,
                    'date_range' => [
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d'),
                        'days' => $start->diffInDays($end) + 1
                    ],
                    'selected_account' => $accountId
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching analytics data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'error' => true,
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to load analytics data'
            ], 500);
        }
    }

    /**
     * Get posting trends data for different periods
     */
    public function getPostingTrends(Request $request)
    {
        try {
            $period = $request->input('period', 'daily');
            $dateRange = $request->input('date_range', '30');
            $accountId = $request->input('account_id'); // NEW

            $end = Carbon::now();
            $start = match ($dateRange) {
                '7' => $end->copy()->subDays(7),
                '30' => $end->copy()->subDays(30),
                '90' => $end->copy()->subDays(90),
                '365' => $end->copy()->subYear(),
                default => $end->copy()->subDays(30)
            };

            // Build query with user's accounts only
            $query = SocialPost::whereBetween('created_at', [$start, $end])
                ->whereHas('socialAccount', function ($q) {
                    $q->where('created_by', auth()->id());
                });

            // Filter by specific account if provided
            if ($accountId && $accountId !== 'all') {
                $query->where('social_account_id', $accountId);
            }

            $posts = $query->get();

            $trendsData = match ($period) {
                'weekly' => $this->getWeeklyTrends($posts, $start, $end),
                'monthly' => $this->getMonthlyTrends($posts, $start, $end),
                default => $this->getDailyTrends($posts, $start, $end),
            };

            return response()->json([
                'error' => false,
                'data' => $trendsData
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching trends data: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Failed to load trends data'
            ], 500);
        }
    }

    /**
     * Get daily trends data
     */
    private function getDailyTrends($posts, $start, $end)
    {
        $dailyStats = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayPosts = $posts->filter(function ($post) use ($date) {
                return $post->created_at->format('Y-m-d') === $date->format('Y-m-d');
            });

            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'date_formatted' => $date->format('M d'),
                'total' => $dayPosts->count(),
                'published' => $dayPosts->where('status', 'published')->count(),
                'partially_published' => $dayPosts->where('status', 'partially_published')->count(),
                'scheduled' => $dayPosts->where('status', 'scheduled')->count(),
                'failed' => $dayPosts->where('status', 'failed')->count(),
                'pending' => $dayPosts->where('status', 'pending')->count()
            ];
        }

        return $dailyStats;
    }

    /**
     * Get weekly trends data
     */
    private function getWeeklyTrends($posts, $start, $end)
    {
        $weeklyStats = [];
        $current = $start->copy()->startOfWeek();

        while ($current->lte($end)) {
            $weekEnd = $current->copy()->endOfWeek();
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $weekPosts = $posts->filter(function ($post) use ($current, $weekEnd) {
                return $post->created_at->between($current, $weekEnd);
            });

            $weeklyStats[] = [
                'date' => $current->format('Y-m-d'),
                'date_formatted' => 'Week of ' . $current->format('M d'),
                'total' => $weekPosts->count(),
                'published' => $weekPosts->where('status', 'published')->count(),
                'partially_published' => $weekPosts->where('status', 'partially_published')->count(),
                'scheduled' => $weekPosts->where('status', 'scheduled')->count(),
                'failed' => $weekPosts->where('status', 'failed')->count(),
                'pending' => $weekPosts->where('status', 'pending')->count()
            ];

            $current->addWeek();
        }

        return $weeklyStats;
    }

    /**
     * Get monthly trends data
     */
    private function getMonthlyTrends($posts, $start, $end)
    {
        $monthlyStats = [];
        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthEnd = $current->copy()->endOfMonth();
            if ($monthEnd->gt($end)) {
                $monthEnd = $end->copy();
            }

            $monthPosts = $posts->filter(function ($post) use ($current, $monthEnd) {
                return $post->created_at->between($current, $monthEnd);
            });

            $monthlyStats[] = [
                'date' => $current->format('Y-m-d'),
                'date_formatted' => $current->format('M Y'),
                'total' => $monthPosts->count(),
                'published' => $monthPosts->where('status', 'published')->count(),
                'partially_published' => $monthPosts->where('status', 'partially_published')->count(),
                'scheduled' => $monthPosts->where('status', 'scheduled')->count(),
                'failed' => $monthPosts->where('status', 'failed')->count(),
                'pending' => $monthPosts->where('status', 'pending')->count()
            ];

            $current->addMonth();
        }

        return $monthlyStats;
    }

    /**
     * Helper method to sanitize caption for display
     */
    // private function sanitizeCaption($caption)
    // {
    //     if (!$caption) return '';
    //     return strlen($caption) > 100 ? substr($caption, 0, 100) . '...' : $caption;
    // }




    public function getPostDetail($id)
    {

        try {
            $post = SocialPost::with(['user', 'media'])->findOrFail($id);

            // Permission check
            $canView = (isAdminOrHasAllDataAccess() || auth()->user()->can('manage_posts'));
            if (!$canView) {
                abort(403, 'You do not have permission to view this post.');
            }

            // Get media files with proper URLs
            $mediaFiles = $post->getMedia('social-media')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'is_image' => str_starts_with($media->mime_type, 'image/'),
                    'is_video' => str_starts_with($media->mime_type, 'video/'),
                    'human_readable_size' => $this->formatBytes($media->size)
                ];
            });

            // Decode platform_meta (be tolerant of string/array/null)
            $platformMeta = is_string($post->platform_meta)
                ? json_decode($post->platform_meta, true)
                : ($post->platform_meta ?? []);

            // Prepare platforms info
            $platformsInfo = collect($post->platforms)->map(function ($platform) use ($post, $platformMeta) {
                $platformData = [
                    'name' => $platform,
                    'display_name' => ucfirst($platform),
                    'icon' => config('social.platforms.' . $platform . '.icon'),
                    'color' => config('social.platforms.' . $platform . '.color'),
                    'status' => 'pending', // default
                    'post_url' => null,
                    'published_at' => null,
                ];

                // Get status / logs
                if ($post->response_logs && isset($post->response_logs[$platform])) {
                    $platformLog = $post->response_logs[$platform];
                    $platformData['status'] = !empty($platformLog['success']) ? 'published' : 'failed';
                    $platformData['published_at'] = isset($platformLog['published_at'])
                        ? \Carbon\Carbon::parse($platformLog['published_at'])
                        : null;
                    $platformData['post_url'] = $platformLog['post_url'] ?? null;
                    $platformData['error'] = $platformLog['error'] ?? null;
                }

                // Attach YouTube-specific metadata (if present)
                if ($platform === 'youtube' && !empty($platformMeta['youtube'])) {
                    $yt = $platformMeta['youtube'];

                    // Normalize tags to array
                    $tags = [];
                    if (!empty($yt['tags'])) {
                        if (is_array($yt['tags'])) {
                            $tags = $yt['tags'];
                        } elseif (is_string($yt['tags'])) {
                            // Sometimes saved as comma separated
                            $tags = array_filter(array_map('trim', explode(',', $yt['tags'])));
                        }
                    }

                    // Thumbnail: try multiple methods to fetch
                    $thumbnailUrl = null;

                    // Method 1: Try thumbnail_media_id from youtube-thumbnail collection
                    if (!empty($yt['thumbnail_media_id'])) {
                        $thumb = $post->getMedia('youtube-thumbnail')->firstWhere('id', $yt['thumbnail_media_id']);
                        if ($thumb) {
                            $thumbnailUrl = $thumb->getUrl();
                        } else {
                            // Fallback: try to fetch media model directly
                            try {
                                $thumbModel = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($yt['thumbnail_media_id']);
                                if ($thumbModel) {
                                    $thumbnailUrl = $thumbModel->getUrl();
                                }
                            } catch (\Throwable $ex) {
                                Log::warning('Failed to load YouTube thumbnail by ID', [
                                    'media_id' => $yt['thumbnail_media_id'],
                                    'error' => $ex->getMessage()
                                ]);
                            }
                        }
                    }

                    // Method 2: If no media_id, try getting first thumbnail from collection
                    if (empty($thumbnailUrl)) {
                        $thumbMedia = $post->getFirstMedia('youtube-thumbnail');
                        if ($thumbMedia) {
                            $thumbnailUrl = $thumbMedia->getUrl();
                        }
                    }



                    $platformData['youtube_meta'] = [
                        'title' => $yt['title'] ?? '',
                        'description' => $yt['description'] ?? '',
                        'tags' => $tags,
                        'privacy_status' => $yt['privacy_status'] ?? 'public',
                        'category' => $yt['category'] ?? null,
                        'thumbnail_media_id' => $yt['thumbnail_media_id'] ?? null,
                        'thumbnail_url' => $thumbnailUrl,
                    ];
                }

                return $platformData;
            });

            // Check permissions for actions
            $canEdit = (isAdminOrHasAllDataAccess() || auth()->user()->can('edit_posts')) &&
                in_array($post->status, ['pending', 'scheduled']);
            $canDelete = (isAdminOrHasAllDataAccess() || auth()->user()->can('delete_posts'));

            return view('social-media-scheduler::social-media-scheduler.post_info', compact(
                'post',
                'mediaFiles',
                'platformsInfo',
                'canEdit',
                'canDelete'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading post details: ' . $e->getMessage(), [
                'post_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('social.index')->with('error', 'Post not found or access denied.');
        }
    }

    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }



    // Youtube

    public function getYoutubeCategories()
    {
        try {
            $social_settings = get_settings('social_settings');
            $ytService = new \Plugins\SocialMediaManagement\Services\SocialMedia\YouTubeService($social_settings);

            // Get a valid token (auto-refresh if expired)
            $token = $ytService->getValidAccessToken();


            if (!$token) {
                return response()->json([
                    'error' => true,
                    'message' => 'Could not get valid YouTube token'
                ], 401);
            }

            $response = Http::withToken($token)->get(
                'https://www.googleapis.com/youtube/v3/videoCategories',
                ['part' => 'snippet', 'regionCode' => 'US']
            );

            if ($response->failed()) {
                return response()->json([
                    'error' => true,
                    'status' => $response->status(),
                    'details' => $response->json()
                ], $response->status());
            }

            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ]);
        }
    }
}
