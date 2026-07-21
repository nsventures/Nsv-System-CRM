<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $recipients;
    protected $workspaceId;
    protected $authId;
    protected $authGuard;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $recipients, $workspaceId, $authId, $authGuard)
    {
        $this->data = $data;
        $this->recipients = $recipients;
        $this->workspaceId = $workspaceId;
        $this->authId = $authId;
        $this->authGuard = $authGuard;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Re-establish workspace context
            if ($this->workspaceId) {
                session(['workspace_id' => $this->workspaceId]);
            }

            // Re-establish authentication context if necessary
            if ($this->authId && $this->authGuard) {
                Auth::guard($this->authGuard)->loginUsingId($this->authId);
            }

            // Call the synchronous processing function
            if (function_exists('processNotificationsSynchronously')) {
                processNotificationsSynchronously($this->data, $this->recipients);
            } else {
                Log::error('processNotificationsSynchronously function not found in job.');
            }
        } catch (\Exception $e) {
            Log::error('Error in ProcessNotificationsJob: ' . $e->getMessage());
            throw $e;
        }
    }
}
