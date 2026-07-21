<?php

namespace App\Services\BulkImport\Modules;

use App\Models\Client;
use App\Models\Template;
use App\Models\Workspace;
use App\Notifications\AccountCreation;
use App\Notifications\VerifyEmail;
use App\Services\BulkImport\ImportModuleInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class ClientImportModule implements ImportModuleInterface
{
    protected int $workspaceId;
    protected bool $isAdmin;

    public function __construct()
    {
        $this->workspaceId = getWorkspaceId();
        $this->isAdmin = isAdminOrHasAllDataAccess();
    }

    public function getModelClass(): string
    {
        return Client::class;
    }

    /*
    |--------------------------------------------------------------------------
    | Sheet-Level Validation (Before Import)
    |--------------------------------------------------------------------------
    */

    public function beforeImport(array $rows): array
    {
        $errors = [];
        $seenEmails = [];
        $seenPhoneCountry = [];
        $seenEmailPassword = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $email                   = $row['email'] ?? null;
            $password                = $row['password'] ?? null;
            $passwordConfirmation    = $row['password_confirmation'] ?? null;
            $phone                   = $row['phone'] ?? null;
            $countryCode             = $row['country_code'] ?? null;
            $countryIso              = $row['country_iso_code'] ?? null;
            $isForInternalPurpose    = $row['is_for_internal_purpose'] ?? null;
            $status                  = $row['status'] ?? null;
            $requireEv               = $row['require_email_verification'] ?? null;

            // is_for_internal_purpose required
            if (!isset($isForInternalPurpose) || !in_array($isForInternalPurpose, ['0', '1', 0, 1], true)) {
                $errors[] = "'Is for internal purpose' must be 0 or 1 at Row {$rowNumber}.";
            }

            // If not internal, password + status + require_ev are required
            if ($isForInternalPurpose == 0) {
                if (empty($password) || empty($passwordConfirmation)) {
                    $errors[] = "Password and password confirmation are required for non-internal clients at Row {$rowNumber}.";
                }
                if (!isset($status) || !in_array($status, ['0', '1', 0, 1], true)) {
                    $errors[] = "Status is required for non-internal clients at Row {$rowNumber}.";
                }
                if (!isset($requireEv) || !in_array($requireEv, ['0', '1', 0, 1], true)) {
                    $errors[] = "Require email verification is required for non-internal clients at Row {$rowNumber}.";
                }
            }

            // Password match
            if ($password && $passwordConfirmation && $password !== $passwordConfirmation) {
                $errors[] = "Password and password confirmation must match at Row {$rowNumber}.";
            }

            // Phone / Country dependency
            if ($phone && !$countryCode) {
                $errors[] = "Country code required when phone is present at Row {$rowNumber}.";
            }
            if ($countryCode && !$phone) {
                $errors[] = "Phone required when country code is present at Row {$rowNumber}.";
            }
            if ($countryCode && !$countryIso) {
                $errors[] = "Country ISO code required when country code is present at Row {$rowNumber}.";
            }

            // Duplicate phone+country in sheet
            if ($phone && $countryCode) {
                $key = $phone . '-' . $countryCode;
                if (in_array($key, $seenPhoneCountry)) {
                    $errors[] = "Duplicate phone + country code in sheet at Row {$rowNumber}.";
                } else {
                    $seenPhoneCountry[] = $key;
                }
            }

            // Duplicate email in sheet
            if ($email) {
                if (in_array($email, $seenEmails)) {
                    $errors[] = "Duplicate email in sheet at Row {$rowNumber}.";
                } else {
                    $seenEmails[] = $email;
                }
            }

            // Duplicate email+password in sheet
            if ($email && $password) {
                $key = $email . '-' . $password;
                if (in_array($key, $seenEmailPassword)) {
                    $errors[] = "Duplicate email + password combination in sheet at Row {$rowNumber}.";
                } else {
                    $seenEmailPassword[] = $key;
                }
            }

            // DB unique email in clients
            if ($email && DB::table('clients')->where('email', $email)->exists()) {
                $errors[] = "Email '{$email}' already exists in database at Row {$rowNumber}.";
            }

            // DB unique phone + country
            if ($phone && $countryCode) {
                if (DB::table('clients')->where('phone', $phone)->where('country_code', $countryCode)->exists()) {
                    $errors[] = "Phone + country code combination already exists in database at Row {$rowNumber}.";
                }
            }

            // Required fields
            if (empty($email))              $errors[] = "Email is required at Row {$rowNumber}.";
            if (empty($row['first_name'] ?? null)) $errors[] = "First name is required at Row {$rowNumber}.";
            if (empty($row['last_name'] ?? null))  $errors[] = "Last name is required at Row {$rowNumber}.";
        }

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Row Transformation
    |--------------------------------------------------------------------------
    */

    public function transformRow(array $row, bool $isPreview = false): array
    {
        $row = $this->sanitize($row);

        $isInternal = ($row['is_for_internal_purpose'] ?? 0) == 1;

        $row['password_plain'] = $row['password'] ?? null;

        // Only hash during actual import, not preview
        if (!$isPreview) {
            $row['password'] = !$isInternal && isset($row['password'])
                ? bcrypt($row['password'])
                : null;
        }

        $requireEv = $this->isAdmin && ($row['require_email_verification'] ?? 1) == 0 ? 0 : 1;
        $status    = !$isInternal && $this->isAdmin && ($row['status'] ?? 0) == 1 ? 1 : 0;

        $row['internal_purpose']    = $isInternal ? 1 : 0;
        $row['email_verified_at']   = $requireEv == 0 ? now() : null;
        $row['status']              = $status;

        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Row Validation Rules
    |--------------------------------------------------------------------------
    */

    public function getValidationRules(array $row): array
    {
        $isInternal = ($row['is_for_internal_purpose'] ?? 0) == 1;

        return [
            'first_name'               => 'required|string',
            'last_name'                => 'required|string',
            'email'                    => [
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('clients', 'email'),
            ],
            'is_for_internal_purpose'  => 'required|boolean',
            'password'                 => $isInternal ? 'nullable' : 'required|min:6',
            'password_confirmation'    => $isInternal ? 'nullable' : 'required|min:6',
            'dob'                      => 'nullable|date_format:Y-m-d',
            'doj'                      => 'nullable|date_format:Y-m-d',
            'status'                   => $isInternal ? 'nullable|boolean' : 'required|boolean',
            'require_email_verification' => $isInternal ? 'nullable|boolean' : 'required|boolean',
            'phone'                    => 'nullable|string',
            'country_code'             => 'nullable|string',
            'country_iso_code'         => 'nullable|string',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | After Model Creation
    |--------------------------------------------------------------------------
    */

    public function afterCreate($client, array $row): void
    {
        try {
            $role = Role::where('guard_name', 'client')->firstOrFail();
            $client->assignRole($role->id);

            Workspace::find($this->workspaceId)
                ->clients()
                ->attach($client->id);

            $isInternal = ($row['internal_purpose'] ?? 0) == 1;
            $requireEv  = $row['email_verified_at'] === null; // null means verification required

            if (!$isInternal && $requireEv) {
                $client->notify(new VerifyEmail($client));
                $client->update(['email_verification_mail_sent' => 1]);
            } else {
                $client->update(['email_verification_mail_sent' => 0]);
            }

            if (!$isInternal && isEmailConfigured()) {
                $template = Template::where('type', 'email')
                    ->where('name', 'account_creation')
                    ->first();

                if (!$template || $template->status !== 0) {
                    $client->notify(new AccountCreation($client, $row['password_plain']));
                    $client->update(['acct_create_mail_sent' => 1]);
                } else {
                    $client->update(['acct_create_mail_sent' => 0]);
                }
            } else {
                $client->update(['acct_create_mail_sent' => 0]);
            }

            logActivity('client', $client->id, $client->first_name . ' ' . $client->last_name);

        } catch (TransportExceptionInterface $e) {
            $client->delete();
            throw new \Exception("Email configuration error during client import.");
        } catch (Throwable $e) {
            $client->delete();
            throw new \Exception("Client post-processing failed: " . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function sanitize(array $row): array
    {
        return array_map(function ($value) {
            return is_string($value) ? trim(strip_tags($value)) : $value;
        }, $row);
    }
}