<?php

namespace Plugins\SocialMediaManagement\Controllers;

use App\Models\User;
use App\Services\DeletionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Plugins\SocialMediaManagement\Models\SocialAccount;

class SocialAccountsController extends Controller
{
    public function index()
    {
        $accounts = SocialAccount::with('creator')->orderBy('created_at', 'desc')->get();
        return view('social-media-scheduler::social-media-scheduler.accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('social-media-scheduler::social-media-scheduler.accounts.create');
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive',

                // Facebook
                'facebook_access_token' => 'nullable|string',
                'facebook_page_id' => 'nullable|string',

                // Instagram
                'instagram_access_token' => 'nullable|string',
                'instagram_business_account_id' => 'nullable|string',

                // LinkedIn
                'linkedin_access_token' => 'nullable|string',
                'linkedin_person_id' => 'nullable|string',

                // Pinterest
                'pinterest_app_id' => 'nullable|string',
                'pinterest_app_secret' => 'nullable|string',
                'pinterest_app_type' => 'nullable|string',

                // YouTube
                'youtube_client_id' => 'nullable|string',
                'youtube_client_secret' => 'nullable|string',
                'youtube_access_token' => 'nullable|string',
                'youtube_refresh_token' => 'nullable|string',
            ]);

            // Build social_settings JSON
            $socialSettings = $this->buildSocialSettings($data);

            SocialAccount::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'social_settings' => $socialSettings,
                'created_by' => auth()->id(),
                'status' => $data['status'],
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Social account created successfully.',
                'redirect_url' => route('social.accounts.index')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => config('app.debug') ? $e->getMessage() : "An error occurred while creating the social account.",
            ], 500);
        }
    }

    public function edit($id)
    {
        $account = SocialAccount::findOrFail($id);
        return view('social-media-scheduler::social-media-scheduler.accounts.edit', compact('account'));
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive',

                // Facebook
                'facebook_access_token' => 'nullable|string',
                'facebook_page_id' => 'nullable|string',

                // Instagram
                'instagram_access_token' => 'nullable|string',
                'instagram_business_account_id' => 'nullable|string',

                // LinkedIn
                'linkedin_access_token' => 'nullable|string',
                'linkedin_person_id' => 'nullable|string',

                // Pinterest
                'pinterest_app_id' => 'nullable|string',
                'pinterest_app_secret' => 'nullable|string',
                'pinterest_app_type' => 'nullable|string',

                // YouTube
                'youtube_client_id' => 'nullable|string',
                'youtube_client_secret' => 'nullable|string',
                'youtube_access_token' => 'nullable|string',
                'youtube_refresh_token' => 'nullable|string',
            ]);

            $account = SocialAccount::findOrFail($id);

            // Build social_settings JSON
            $socialSettings = $this->buildSocialSettings($data);

            $account->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'social_settings' => $socialSettings,
                'status' => $data['status'],
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Social account updated successfully.',
                'redirect_url' => route('social.accounts.index')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => config('app.debug') ? $e->getMessage() : "An error occurred while updating the social account.",
            ], 500);
        }
    }

    public function destroy($id)
    {
        $response = DeletionService::delete(SocialAccount::class, $id, 'Social Account');
        return $response;
    }

    public function list()
    {
        $search = request('search');
        $limit = request('limit', 10);
        $offset = request('offset', 0);
        $order = request('order', 'DESC');
        $sort = request('sort', 'id');
        $status = request('status');



        $query = SocialAccount::query();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();

        $canEdit = isAdminOrHasAllDataAccess();
        $canDelete = isAdminOrHasAllDataAccess();





        $accounts = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($account) use ($canDelete, $canEdit) {

                $actions = '';

                if ($canEdit) {
                    $actions .= '<a href="' . route('social.accounts.edit', $account->id) . '" 
                                    title="' . get_label('update', 'Update') . '">
                                    <i class="bx bx-edit mx-1"></i>
                                 </a>';
                }

                if ($canDelete) {
                    $actions .= '<button type="button"
                                    class="btn delete"
                                    data-id="' . $account->id . '"
                                    data-type="social-media-scheduler/social-accounts"
                                    data-table="table"
                                    title="' . get_label('delete', 'Delete') . '">
                                    <i class="bx bx-trash text-danger mx-1"></i>
                                 </button>';
                }

                $socialPlatforms = config('social.platforms', []);

                // Get configured platforms
                $configuredPlatforms = $account->getConfiguredPlatforms();
                $platformBadges = '';

                foreach ($configuredPlatforms as $platform) {
                    if (isset($socialPlatforms[$platform])) {
                        $icon = $socialPlatforms[$platform]['icon'];
                        $color = $socialPlatforms[$platform]['color'];
                        $platformBadges .= '<span class="badge bg-light text-dark me-1">';
                        $platformBadges .= '<i class="bx ' . $icon . ' me-1" style="color: ' . $color . ';"></i>' . ucfirst($platform);
                        $platformBadges .= '</span>';
                    }
                }


                return [
                    'id' => $account->id,
                    'name' => ucwords($account->name),
                    'description' => $account->description ?: '-',
                    'platforms' => $platformBadges ?: '-',
                    'status' => '<span class="badge bg-' . ($account->status === 'active' ? 'success' : 'secondary') . '">' . ucfirst($account->status) . '</span>',
                    'created_at' => format_date($account->created_at),
                    'updated_at' => format_date($account->updated_at),
                    'actions' => $actions ?: '-'
                ];
            });

        return response()->json([
            'rows' => $accounts,
            'total' => $total,
        ]);
    }

    public function getActiveAccounts(Request $request)
    {
        try {
            $accounts = SocialAccount::where('created_by', auth()->id())
                ->where('status', 'active')
                ->select('id', 'name', 'description', 'social_settings')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'description' => $account->description,
                        'platforms' => $account->getConfiguredPlatforms()
                    ];
                });
            return response()->json([
                'error' => false,
                'data' => $accounts
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching active accounts: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Failed to load accounts'
            ], 500);
        }
    }

    /**
     * Build social settings array from request data
     */
    private function buildSocialSettings(array $data): array
    {
        $settings = [];

        // Facebook
        if (!empty($data['facebook_access_token']) || !empty($data['facebook_page_id'])) {
            $settings['facebook'] = array_filter([
                'facebook_access_token' => $data['facebook_access_token'] ?? null,
                'facebook_page_id' => $data['facebook_page_id'] ?? null,
            ]);
        }

        // Instagram
        if (!empty($data['instagram_access_token']) || !empty($data['instagram_business_account_id'])) {
            $settings['instagram'] = array_filter([
                'instagram_access_token' => $data['instagram_access_token'] ?? null,
                'instagram_business_account_id' => $data['instagram_business_account_id'] ?? null,
            ]);
        }

        // LinkedIn
        if (!empty($data['linkedin_access_token']) || !empty($data['linkedin_person_id'])) {
            $settings['linkedin'] = array_filter([
                'linkedin_access_token' => $data['linkedin_access_token'] ?? null,
                'linkedin_person_id' => $data['linkedin_person_id'] ?? null,
            ]);
        }

        // Pinterest
        if (!empty($data['pinterest_app_id']) || !empty($data['pinterest_app_secret'])) {
            $settings['pinterest'] = array_filter([
                'pinterest_app_id' => $data['pinterest_app_id'] ?? null,
                'pinterest_app_secret' => $data['pinterest_app_secret'] ?? null,
                'pinterest_app_type' => $data['pinterest_app_type'] ?? null,
            ]);
        }

        // YouTube
        if (!empty($data['youtube_client_id']) || !empty($data['youtube_access_token'])) {
            $settings['youtube'] = array_filter([
                'youtube_client_id' => $data['youtube_client_id'] ?? null,
                'youtube_client_secret' => $data['youtube_client_secret'] ?? null,
                'youtube_access_token' => $data['youtube_access_token'] ?? null,
                'youtube_refresh_token' => $data['youtube_refresh_token'] ?? null,
            ]);
        }

        return $settings;
    }
}
