<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Candidate;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\Meeting;
use App\Models\Workspace;
use App\Models\LeaveRequest;
use App\Models\Template;
use App\Models\FcmToken;
use App\Models\UserClientPreference;
use App\Notifications\AssignmentNotification;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;
use Exception;

class NotificationService
{
    public function processNotificationsSynchronously($data, $recipients)
    {
        $emailNotificationTypes = ['project_assignment', 'project_status_updation', 'interview_assignment', 'interview_status_update', 'task_assignment', 'task_status_updation', 'workspace_assignment', 'meeting_assignment', 'leave_request_creation', 'leave_request_status_updation', 'team_member_on_leave_alert'];
        $smsNotificationTypes = ['project_assignment', 'project_status_updation', 'interview_assignment', 'interview_status_update', 'task_assignment', 'task_status_updation', 'workspace_assignment', 'meeting_assignment', 'leave_request_creation', 'leave_request_status_updation', 'team_member_on_leave_alert'];

        if (empty($recipients)) return;

        $type = $data['type'] == 'task_status_updation' ? 'task' : ($data['type'] == 'project_status_updation' ? 'project' : ($data['type'] == 'leave_request_creation' || $data['type'] == 'leave_request_status_updation' || $data['type'] == 'team_member_on_leave_alert' ? 'leave_request' : $data['type']));
        $systemNotificationTemplate = $this->getNotificationTemplate($data['type'], 'system');
        $pushNotificationTemplate = $this->getNotificationTemplate($data['type'], 'push');

        $notification = null;
        if (!$systemNotificationTemplate || $systemNotificationTemplate->status !== 0 || !$pushNotificationTemplate || $pushNotificationTemplate->status !== 0) {
            $notification = Notification::create([
                'workspace_id' => getWorkspaceId(),
                'from_id' => getGuardName() == 'client' ? 'c_' . getAuthenticatedUser()->id : 'u_' . getAuthenticatedUser()->id,
                'type' => $type,
                'type_id' => $data['type_id'],
                'action' => $data['action'],
                'title' => $this->getTitle($data),
                'message' => $this->getMessage($data, NULL, 'system'),
            ]);
        }

        $loggedInUserId = getGuardName() == 'client' ? 'c_' . getAuthenticatedUser()->id : 'u_' . getAuthenticatedUser()->id;
        $recipients = array_diff($recipients, [$loggedInUserId]);
        $recipients = array_unique($recipients);

        $whatsappNotificationTemplate = $this->getNotificationTemplate($data['type'], 'whatsapp');
        $slackNotificationTemplate = $this->getNotificationTemplate($data['type'], 'slack');

        foreach ($recipients as $recipient) {
            $isSystem = 0;
            $isPush = 0;
            $enabledNotifications = getUserPreferences('notification_preference', 'enabled_notifications', $recipient);
            $recipientId = substr($recipient, 2);
            $recipientModel = null;

            if (substr($recipient, 0, 2) === 'u_') {
                $recipientModel = User::find($recipientId);
            } elseif (substr($recipient, 0, 2) === 'c_') {
                $recipientModel = Client::find($recipientId);
            } elseif (substr($recipient, 0, 2) === 'ca') {
                $recipientModel = Candidate::find($recipientId);
            }

            if (!$recipientModel) continue;

            if (!$systemNotificationTemplate || ($systemNotificationTemplate->status !== 0)) {
                if ($this->isNotificationEnabled($enabledNotifications, 'system', $data['type'])) {
                    $isSystem = 1;
                }
            }

            if (!$pushNotificationTemplate || ($pushNotificationTemplate->status !== 0)) {
                if ($this->isNotificationEnabled($enabledNotifications, 'push', $data['type'])) {
                    $isPush = 1;
                    try {
                        $this->sendPushNotification($recipientModel, $data);
                    } catch (\Exception $e) {
                        Log::error('Push failed: ' . $e->getMessage());
                    }
                }
            }

            if (($isSystem || $isPush) && $notification) {
                $recipientModel->notifications()->attach($notification->id, [
                    'is_system' => $isSystem,
                    'is_push' => $isPush,
                ]);
            }

            if (in_array($data['type'] . '_assignment', $emailNotificationTypes) || in_array($data['type'], $emailNotificationTypes)) {
                if ($this->isNotificationEnabled($enabledNotifications, 'email', $data['type'])) {
                    try {
                        $this->sendEmailNotification($recipientModel, $data);
                    } catch (Throwable $e) {
                        Log::error('Email failed: ' . $e->getMessage());
                    }
                }
            }

            if (in_array($data['type'] . '_assignment', $smsNotificationTypes) || in_array($data['type'], $smsNotificationTypes)) {
                if ($this->isNotificationEnabled($enabledNotifications, 'sms', $data['type'])) {
                    try {
                        $this->sendSMSNotification($data, $recipientModel);
                    } catch (\Exception $e) {
                        Log::error('SMS failed: ' . $e->getMessage());
                    }
                }
            }

            if (!$whatsappNotificationTemplate || ($whatsappNotificationTemplate->status !== 0)) {
                if ($this->isNotificationEnabled($enabledNotifications, 'whatsapp', $data['type'])) {
                    try {
                        $this->sendWhatsAppNotification($recipientModel, $data);
                    } catch (\Exception $e) {
                        Log::error('WhatsApp failed: ' . $e->getMessage());
                    }
                }
            }

            if (!$slackNotificationTemplate || ($slackNotificationTemplate->status !== 0)) {
                if ($this->isNotificationEnabled($enabledNotifications, 'slack', $data['type'])) {
                    try {
                        $this->sendSlackNotification($recipientModel, $data);
                    } catch (\Exception $e) {
                        Log::error('Slack failed: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function isNotificationEnabled($enabledNotifications, $channel, $type)
    {
        return (is_array($enabledNotifications) && empty($enabledNotifications)) ||
               (is_array($enabledNotifications) && (in_array($channel . '_' . $type . '_assignment', $enabledNotifications) || in_array($channel . '_' . $type, $enabledNotifications)));
    }

    public function sendPushNotification($recipientModel, $data)
    {
        $serviceAccountPath = storage_path('app/firebase/firebase-service-account.json');
        if (!file_exists($serviceAccountPath)) return;

        $googleClient = new GoogleClient();
        $googleClient->setAuthConfig($serviceAccountPath);
        $googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $accessToken = $googleClient->fetchAccessTokenWithAssertion()['access_token'];
        $projectId = json_decode(file_get_contents($serviceAccountPath), true)['project_id'];

        $deviceTokens = [];
        if ($recipientModel instanceof User) {
            $deviceTokens = FcmToken::where('user_id', $recipientModel->id)->pluck('fcm_token')->toArray();
        } elseif ($recipientModel instanceof Client) {
            $deviceTokens = FcmToken::where('client_id', $recipientModel->id)->pluck('fcm_token')->toArray();
        }

        if (empty($deviceTokens)) return;

        $httpClient = new HttpClient([
            'base_uri' => 'https://fcm.googleapis.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        $title = $this->getTitle($data, $recipientModel, 'push');
        $body = $this->getMessage($data, $recipientModel, 'push');

        $message = [
            'message' => [
                'notification' => ['title' => $title, 'body' => $body],
                'android' => ['notification' => ['click_action' => 'FLUTTER_NOTIFICATION_CLICK']],
                'apns' => ['payload' => ['aps' => ['alert' => ['title' => $title, 'body' => $body]]]],
            ],
        ];

        if ($data['type'] == 'project' || $data['type'] == 'project_status_updation') {
            $project = Project::find($data['type_id']);
            if ($project) $message['message']['data']['item'] = json_encode(['type' => 'project', 'item' => formatProject($project)]);
        } elseif ($data['type'] == 'task' || $data['type'] == 'task_status_updation') {
            $task = Task::find($data['type_id']);
            if ($task) $message['message']['data']['item'] = json_encode(['type' => 'task', 'item' => formatTask($task)]);
        } elseif ($data['type'] == 'meeting') {
            $meeting = Meeting::find($data['type_id']);
            if ($meeting) $message['message']['data']['item'] = json_encode(['type' => 'meeting', 'item' => formatMeeting($meeting)]);
        } elseif ($data['type'] == 'workspace') {
            $workspace = Workspace::find($data['type_id']);
            if ($workspace) $message['message']['data']['item'] = json_encode(['type' => 'workspace', 'item' => formatWorkspace($workspace)]);
        } elseif (in_array($data['type'], ['leave_request_creation', 'team_member_on_leave_alert', 'leave_request_status_updation'])) {
            $leaveRequest = LeaveRequest::find($data['type_id']);
            if ($leaveRequest) $message['message']['data']['item'] = json_encode(['type' => 'leave_request', 'item' => formatLeaveRequest($leaveRequest)]);
        }

        foreach ($deviceTokens as $deviceToken) {
            try {
                $message['message']['token'] = $deviceToken;
                $httpClient->post("projects/{$projectId}/messages:send", ['json' => $message]);
            } catch (\Exception $e) {
                Log::error('FCM Token error: ' . $e->getMessage());
            }
        }
    }

    public function sendEmailNotification($recipientModel, $data)
    {
        $template = $this->getNotificationTemplate($data['type']);
        if (!$template || ($template->status !== 0)) {
            $recipientModel->notify(new AssignmentNotification($recipientModel, $data));
        }
    }

    public function sendSMSNotification($data, $recipient)
    {
        $template = $this->getNotificationTemplate($data['type'], 'sms');
        if (!$template || ($template->status !== 0)) {
            $this->send_sms($recipient, $data);
        }
    }

    public function getNotificationTemplate($type, $emailOrSMS = 'email')
    {
        $template = Template::where('type', $emailOrSMS)
            ->where('name', $type . '_assignment')
            ->first();
        if (!$template) {
            $template = Template::where('type', $emailOrSMS)
                ->where('name', $type)
                ->first();
        }
        return $template;
    }

    public function send_sms($recipient, $itemData = NULL, $message = NULL)
    {
        $msg = $itemData ? $this->getMessage($itemData, $recipient) : $message;
        try {
            $sms_gateway_settings = get_settings('sms_gateway_settings', true);
            $data = [
                "base_url" => $sms_gateway_settings['base_url'],
                "sms_gateway_method" => $sms_gateway_settings['sms_gateway_method'],
                "body" => [],
                "header" => [],
                "params" => []
            ];

            if (isset($sms_gateway_settings["body_formdata"])) {
                foreach ($sms_gateway_settings["body_formdata"] as $key => $value) {
                    $data["body"][$key] = $this->parse_sms($value, $recipient->phone, $msg, $recipient->country_code);
                }
            }
            if (isset($sms_gateway_settings["header_data"])) {
                foreach ($sms_gateway_settings["header_data"] as $key => $value) {
                    $data["header"][] = $key . ": " . $this->parse_sms($value, $recipient->phone, $msg, $recipient->country_code);
                }
            }
            if (isset($sms_gateway_settings["params_data"])) {
                foreach ($sms_gateway_settings["params_data"] as $key => $value) {
                    $data["params"][$key] = $this->parse_sms($value, $recipient->phone, $msg, $recipient->country_code);
                }
            }
            return $this->curl_sms($data["base_url"], $data["sms_gateway_method"], $data["body"], $data["header"]);
        } catch (Exception $e) {
            Log::error('SMS Error: ' . $e->getMessage());
            if ($itemData == NULL) throw $e;
        }
    }

    public function curl_sms($url, $method = 'GET', $data = [], $headers = [])
    {
        $ch = curl_init();
        $curl_options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ];
        if (!empty($headers)) $curl_options[CURLOPT_HTTPHEADER] = $headers;
        if (strtolower($method) == 'post') {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
        } else {
            $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }
        curl_setopt_array($ch, $curl_options);
        $result = [
            'body' => json_decode(curl_exec($ch), true),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ];
        curl_close($ch);
        return $result;
    }

    public function parse_sms($template, $phone, $msg, $country_code)
    {
        return str_replace(['{only_mobile_number}', '{message}', '{country_code}'], [$phone, $msg, $country_code], $template);
    }

    public function sendWhatsAppNotification($recipient, $itemData = NULL, $message = NULL)
    {
        $msg = $itemData ? $this->getMessage($itemData, $recipient, 'whatsapp') : $message;
        $whatsapp_settings = get_settings('whatsapp_settings', true);
        $general_settings = get_settings('general_settings');
        $company_title = $general_settings['company_title'] ?? 'Taskify';
        $client = new GuzzleHttpClient();
        try {
            $response = $client->post('https://graph.facebook.com/v20.0/' . $whatsapp_settings['whatsapp_phone_number_id'] . '/messages', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $whatsapp_settings['whatsapp_access_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient->country_code . ($recipient->phone ?? ''),
                    'type' => 'template',
                    'template' => [
                        'name' => 'taskify_saas_notification',
                        'language' => ['code' => 'en'],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $msg],
                                    ['type' => 'text', 'text' => $company_title]
                                ]
                            ]
                        ]
                    ]
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            if ($itemData == NULL) return $data;
        } catch (RequestException $e) {
            Log::error('WhatsApp error: ' . $e->getMessage());
            if ($itemData == NULL) throw $e;
        }
    }

    public function sendSlackNotification($recipient, $itemData = NULL, $message = NULL)
    {
        $slack_settings = get_settings('slack_settings');
        if (!$slack_settings || empty($slack_settings['slack_bot_token'])) return;

        $message = $itemData ? $this->getMessage($itemData, $recipient, 'slack') : $message;
        $botToken = $slack_settings['slack_bot_token'];
        $client = new GuzzleHttpClient([
            'base_uri' => 'https://slack.com/api/',
            'headers' => [
                'Authorization' => 'Bearer ' . $botToken,
                'Content-Type' => 'application/json',
            ],
        ]);
        $userId = $this->getSlackUserIdByEmail($client, $recipient->email);
        if ($userId) {
            try {
                $response = $client->post('chat.postMessage', [
                    'json' => [
                        'channel' => $userId,
                        'text' => $message,
                        'username' => 'Taskify Notification',
                        'icon_emoji' => ':office:',
                    ]
                ]);
                $responseBody = json_decode($response->getBody(), true);
                if ($itemData === NULL) return ['status' => $responseBody['ok'] ? 'success' : 'error', 'message' => $responseBody['ok'] ? 'Slack DM sent' : $responseBody['error']];
            } catch (\Exception $e) {
                if ($itemData === NULL) return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }
    }

    public function getSlackUserIdByEmail($client, $email)
    {
        try {
            $response = $client->get('users.lookupByEmail', ['query' => ['email' => $email]]);
            $body = json_decode($response->getBody(), true);
            return $body['ok'] ? $body['user']['id'] : null;
        } catch (\Exception $e) {
            Log::error('Slack User Lookup error: ' . $e->getMessage());
        }
    }

    public function getMessage($data, $recipient, $type = 'sms')
    {
        $authUser = getAuthenticatedUser();
        $general_settings = get_settings('general_settings');
        $company_title = $general_settings['company_title'] ?? 'Taskify';
        $siteUrl = $general_settings['site_url'] ?? request()->getSchemeAndHttpHost();
        $fetched_data = Template::where('type', $type)->where('name', $data['type'] . '_assignment')->first() ?: Template::where('type', $type)->where('name', $data['type'])->first();

        $contentPlaceholders = [];
        $templateContent = $fetched_data->content ?? 'Default Content';

        if ($type === 'system' || $type === 'push') {
             switch ($data['type']) {
                case 'project':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} assigned you new project: {PROJECT_TITLE}, ID:#{PROJECT_ID}.';
                    break;
                case 'project_status_updation':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'] ?? '',
                        '{NEW_STATUS}' => $data['new_status'] ?? '',
                        '{PROJECT_URL}' => $siteUrl . '/' . ($data['access_url'] ?? ''),
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of project {PROJECT_TITLE}, ID:#{PROJECT_ID}, from {OLD_STATUS} to {NEW_STATUS}.';
                    break;
                case 'task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} assigned you new task: {TASK_TITLE}, ID:#{TASK_ID}.';
                    break;
                case 'task_status_updation':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'] ?? '',
                        '{NEW_STATUS}' => $data['new_status'] ?? '',
                        '{TASK_URL}' => $siteUrl . '/' . ($data['access_url'] ?? ''),
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of task {TASK_TITLE}, ID:#{TASK_ID}, from {OLD_STATUS} to {NEW_STATUS}.';
                    break;
                case 'workspace':
                    $contentPlaceholders = [
                        '{WORKSPACE_ID}' => $data['type_id'],
                        '{WORKSPACE_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{WORKSPACE_URL}' => $siteUrl . '/workspaces'
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} added you in a new workspace {WORKSPACE_TITLE}, ID:#{WORKSPACE_ID}.';
                    break;
                case 'meeting':
                    $contentPlaceholders = [
                        '{MEETING_ID}' => $data['type_id'],
                        '{MEETING_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{MEETING_URL}' => $siteUrl . '/meetings'
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} added you in a new meeting {MEETING_TITLE}, ID:#{MEETING_ID}.';
                    break;
                case 'leave_request_creation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'] ?? '',
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'] ?? '',
                        '{TYPE}' => $data['leave_type'] ?? '',
                        '{FROM}' => $data['from'] ?? '',
                        '{TO}' => $data['to'] ?? '',
                        '{DURATION}' => $data['duration'] ?? '',
                        '{REASON}' => $data['reason'] ?? '',
                        '{COMMENT}' => $data['comment'] ?? '',
                        '{STATUS}' => $data['status'] ?? '',
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    if (!isset($fetched_data->content)) $templateContent = 'New Leave Request ID:#{ID} Has Been Created By {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME}.';
                    break;
                case 'leave_request_status_updation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'] ?? '',
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'] ?? '',
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'] ?? '',
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'] ?? '',
                        '{TYPE}' => $data['leave_type'] ?? '',
                        '{FROM}' => $data['from'] ?? '',
                        '{TO}' => $data['to'] ?? '',
                        '{DURATION}' => $data['duration'] ?? '',
                        '{REASON}' => $data['reason'] ?? '',
                        '{COMMENT}' => $data['comment'] ?? '',
                        '{OLD_STATUS}' => $data['old_status'] ?? '',
                        '{NEW_STATUS}' => $data['new_status'] ?? '',
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    if (!isset($fetched_data->content)) $templateContent = 'Leave Request ID:#{ID} Status Updated From {OLD_STATUS} To {NEW_STATUS}.';
                    break;
                case 'team_member_on_leave_alert':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'] ?? '',
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'] ?? '',
                        '{TYPE}' => $data['leave_type'] ?? '',
                        '{FROM}' => $data['from'] ?? '',
                        '{TO}' => $data['to'] ?? '',
                        '{DURATION}' => $data['duration'] ?? '',
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} will be on {TYPE} leave from {FROM} to {TO}.';
                    break;
                case 'birthday_wish':
                    $contentPlaceholders = [
                        '{FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{BIRTHDAY_COUNT}' => $data['birthday_count'] ?? '',
                        '{ORDINAL_SUFFIX}' => $data['ordinal_suffix'] ?? '',
                        '{COMPANY_TITLE}' => $company_title,
                        '{SITE_URL}' => $siteUrl
                    ];
                    if (!isset($fetched_data->content)) $templateContent = 'Hello {FIRST_NAME} {LAST_NAME}, {COMPANY_TITLE} wishes you a very Happy Birthday!';
                    break;
                case 'work_anniversary_wish':
                    $contentPlaceholders = [
                        '{FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                        '{LAST_NAME}' => $recipient ? $recipient->last_name : '',
                        '{WORK_ANNIVERSARY_COUNT}' => $data['work_anniversary_count'] ?? '',
                        '{ORDINAL_SUFFIX}' => $data['ordinal_suffix'] ?? '',
                        '{COMPANY_TITLE}' => $company_title,
                        '{SITE_URL}' => $siteUrl
                    ];
                    if (!isset($fetched_data->content)) $templateContent = 'Hello {FIRST_NAME} {LAST_NAME}, {COMPANY_TITLE} wishes you a very happy work anniversary!';
                    break;
                case 'task_reminder':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . ($data['access_url'] ?? ''),
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    if (!isset($fetched_data->content)) $templateContent = 'You have a task reminder for Task #{TASK_ID} - "{TASK_TITLE}". You can view the task here: {TASK_URL}.';
                    break;
                case 'recurring_task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . ($data['access_url'] ?? ''),
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    if (!isset($fetched_data->content)) $templateContent = 'The recurring task #{TASK_ID} - "{TASK_TITLE}" has been executed. You can view the new instance here: {TASK_URL}';
                    break;
                case 'todo_reminder':
                    $contentPlaceholders = [
                        '{TODO_ID}' => $data['type_id'],
                        '{TODO_TITLE}' => $data['type_title'],
                        '{TODO_URL}' => $siteUrl . '/' . ($data['access_url'] ?? ''),
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    if (!isset($fetched_data->content)) $templateContent = 'You have a todo reminder for Todo #{TODO_ID} - "{TODO_TITLE}". You can view the task here: {TODO_URL}.';
                    break;
                 case 'interview_assignment':
                    $contentPlaceholders = [
                        '{INTERVIEW_ID}' => $data['type_id'],
                        '{CANDIDATE_NAME}' => $data['candidate_name'] ?? '',
                        '{ROUND}' => $data['round'] ?? '',
                        '{SCHEDULED_AT}' => $data['scheduled_at'] ?? '',
                        '{INTERVIEWER_FIRST_NAME}' => $data['interviewer_first_name'] ?? '',
                        '{INTERVIEWER_LAST_NAME}' => $data['interviewer_last_name'] ?? '',
                        '{FULL_NAME}' =>  $recipient ? $recipient->name : '',
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} from {COMPANY_TITLE} has scheduled a new interview for {CANDIDATE_NAME}. Interview ID: #{INTERVIEW_ID}, Round: {ROUND}, Scheduled at: {SCHEDULED_AT}, Interviewer: {INTERVIEWER_FIRST_NAME} {INTERVIEWER_LAST_NAME}.';
                    break;
                case 'interview_status_update':
                    $contentPlaceholders = [
                        '{INTERVIEW_ID}' => $data['type_id'],
                        '{CANDIDATE_NAME}' => $data['candidate_name'] ?? '',
                        '{ROUND}' => $data['round'] ?? '',
                        '{SCHEDULED_AT}' => $data['scheduled_at'] ?? '',
                        '{INTERVIEWER_FIRST_NAME}' => $data['interviewer_first_name'] ?? '',
                        '{INTERVIEWER_LAST_NAME}' => $data['interviewer_last_name'] ?? '',
                        '{FULL_NAME}' =>  $recipient ? $recipient->name : '',
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'] ?? '',
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'] ?? '',
                        '{OLD_STATUS}' => $data['old_status'] ?? '',
                        '{NEW_STATUS}' => $data['new_status'] ?? '',
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    if (!isset($fetched_data->content)) $templateContent = '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of your interview (ID: #{INTERVIEW_ID}) for {CANDIDATE_NAME} from "{OLD_STATUS}" to "{NEW_STATUS}".';
                    break;
            }
        } elseif ($type === 'slack') {
             switch ($data['type']) {
                case 'project':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient->first_name,
                        '{USER_LAST_NAME}' => $recipient->last_name,
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    $templateContent = '*New Project Assigned:* {PROJECT_TITLE}, ID: #{PROJECT_ID}. By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} You can find the project here :{PROJECT_URL}';
                    break;
                case 'project_status_updation':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient->first_name,
                        '{USER_LAST_NAME}' => $recipient->last_name,
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '*Project Status Updated:* By {UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} , {PROJECT_TITLE}, ID: #{PROJECT_ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`. You can find the project here :{PROJECT_URL}';
                    break;
                case 'task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient->first_name,
                        '{USER_LAST_NAME}' => $recipient->last_name,
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    $templateContent = '*New Task Assigned:* {TASK_TITLE}, ID: #{TASK_ID}. By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} You can find the task here : {TASK_URL}';
                    break;
                case 'task_status_updation':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{USER_FIRST_NAME}' => $recipient->first_name,
                        '{USER_LAST_NAME}' => $recipient->last_name,
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '*Task Status Updated:* By {UPDATER_FIRST_NAME} {UPDATER_LAST_NAME},  {TASK_TITLE}, ID: #{TASK_ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`. You can find the Task here : {TASK_URL}';
                    break;
                // Add more Slack cases as needed
                default:
                    $templateContent = $fetched_data->content ?? 'Default Slack Content';
                    break;
             }
        } else {
            // Default/SMS
            $contentPlaceholders = [
                '{FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                '{LAST_NAME}' => $recipient ? $recipient->last_name : '',
                '{COMPANY_TITLE}' => $company_title,
                '{SITE_URL}' => $siteUrl,
            ];
            // Add more default cases as needed
            $templateContent = $fetched_data->content ?? 'Default SMS Content';
        }

        if (filled(Arr::get($fetched_data, 'content'))) {
            $templateContent = $fetched_data->content;
        }

        return str_replace(array_keys($contentPlaceholders), array_values($contentPlaceholders), $templateContent);
    }

    public function getTitle($data, $recipient = NULL, $type = 'system')
    {
        $authUser = getAuthenticatedUser();
        $general_settings = get_settings('general_settings');
        $companyTitle = $general_settings['company_title'] ?? 'Taskify';

        $fetched_data = Template::where('type', $type)->where('name', $data['type'] . '_assignment')->first() ?: Template::where('type', $type)->where('name', $data['type'])->first();

        $subject = $fetched_data->subject ?? 'Default Subject';
        $subjectPlaceholders = [];

        switch ($data['type']) {
            case 'project':
                $subjectPlaceholders = [
                    '{PROJECT_ID}' => $data['type_id'],
                    '{PROJECT_TITLE}' => $data['type_title'],
                    '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                    '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'task':
                $subjectPlaceholders = [
                    '{TASK_ID}' => $data['type_id'],
                    '{TASK_TITLE}' => $data['type_title'],
                    '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                    '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'workspace':
                $subjectPlaceholders = [
                    '{WORKSPACE_ID}' => $data['type_id'],
                    '{WORKSPACE_TITLE}' => $data['type_title'],
                    '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                    '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'meeting':
                $subjectPlaceholders = [
                    '{MEETING_ID}' => $data['type_id'],
                    '{MEETING_TITLE}' => $data['type_title'],
                    '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                    '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'leave_request_creation':
                $subjectPlaceholders = [
                    '{ID}' => $data['type_id'],
                    '{STATUS}' => $data['status'] ?? '',
                    '{USER_FIRST_NAME}' => $recipient ? $recipient->first_name : '',
                    '{USER_LAST_NAME}' => $recipient ? $recipient->last_name : '',
                    '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'] ?? '',
                    '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'] ?? '',
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            // Add more cases as needed
        }

        if (!isset($fetched_data->subject)) {
            $typeToSubject = [
                'leave_request_creation' => 'Leave Requested',
                'leave_request_status_updation' => 'Leave Request Status Updated',
                'team_member_on_leave_alert' => 'Team Member on Leave Alert',
                'project_status_updation' => 'Project Status Updated',
                'task_status_updation' => 'Task Status Updated',
                'birthday_wish' => 'Happy Birthday!',
                'work_anniversary_wish' => 'Happy Work Anniversary!',
            ];
            $subject = $typeToSubject[$data['type']] ?? 'New ' . ucfirst($data['type']) . ' Assigned';
        }

        return str_replace(array_keys($subjectPlaceholders), array_values($subjectPlaceholders), $subject);
    }
}
