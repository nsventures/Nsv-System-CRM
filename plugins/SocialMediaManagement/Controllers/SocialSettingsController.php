<?php

namespace Plugins\SocialMediaManagement\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class SocialSettingsController extends Controller
{
    //

    public function index(){

        $settings = Setting::where('variable', 'social_settings')->first();

        $socialSettings = $settings ? json_decode($settings->value, true) : [];

        return view('social-media-scheduler::social-media-scheduler.settings.index', compact('socialSettings'));
    }

    public function update(Request $request){

        $validated = $request->validate([
            'facebook_access_token' => 'nullable|string',
            'facebook_page_id' => 'nullable|string',
            'instagram_access_token' => 'nullable|string',
            'instagram_business_account_id' => 'nullable|string',
            'twitter_consumer_key' => 'nullable|string',
            'twitter_consumer_secret' => 'nullable|string',
            'twitter_access_token' => 'nullable|string',
            'twitter_access_token_secret' => 'nullable|string',
            'linkedin_access_token' => 'nullable|string',
            'linkedin_person_id' => 'nullable|string',
            'pinterest_app_id' => 'nullable|string',
            'pinterest_app_secret' => 'nullable|string',
            'pinterest_app_type' => 'nullable|string',
            'youtube_client_id' => 'nullable|string',
            'youtube_client_secret' => 'nullable|string',
            'youtube_access_token' => 'nullable|string',
            'youtube_refresh_token' => 'nullable|string'
        ]);

        try{

            Setting::updateOrCreate(
                ['variable' => 'social_settings'],
                ['value' => json_encode($validated)]
            );

            return response()->json([
                'error' => false,
                'message' => 'Social media tokens updated successfully.'
            ]);


        }catch(\Exception $e){
            return response()->json([
                'error' => true,
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ]);
        }
    }
}
