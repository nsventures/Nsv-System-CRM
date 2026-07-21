<?php

namespace App\Services\BulkImport\Modules;

use App\Models\Template;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\AccountCreation;
use App\Notifications\VerifyEmail;
use App\Services\LeaveBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\BulkImport\ImportModuleInterface;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class UserImportModule implements ImportModuleInterface
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
        return User::class;
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

            $email = $row['email'] ?? null;
            $password = $row['password'] ?? null;
            $passwordConfirmation = $row['password_confirmation'] ?? null;
            $phone = $row['phone'] ?? null;
            $countryCode = $row['country_code'] ?? null;
            $countryIso = $row['country_iso_code'] ?? null;
            $roleId = $row['role_id'] ?? null;

            // Password match
            if ($password && $passwordConfirmation && $password !== $passwordConfirmation) {
                $errors[] = "Password mismatch at Row {$rowNumber}.";
            }

            // Phone / Country dependency
            if ($phone && !$countryCode) {
                $errors[] = "Country code required when phone is present at Row {$rowNumber}.";
            }
            if ($countryCode && !$phone) {
                $errors[] = "Phone required when country code is present at Row {$rowNumber}.";
            }
            if ($countryCode && !$countryIso) {
                $errors[] = "Country ISO code required at Row {$rowNumber}.";
            }

            // Duplicate phone+country inside sheet
            if ($phone && $countryCode) {
                $key = $phone . '-' . $countryCode;
                if (in_array($key, $seenPhoneCountry)) {
                    $errors[] = "Duplicate phone + country code in sheet at Row {$rowNumber}.";
                } else {
                    $seenPhoneCountry[] = $key;
                }
            }

            // Duplicate email inside sheet
            if ($email) {
                if (in_array($email, $seenEmails)) {
                    $errors[] = "Duplicate email in sheet at Row {$rowNumber}.";
                } else {
                    $seenEmails[] = $email;
                }
            }

            // Duplicate email+password inside sheet
            if ($email && $password) {
                $key = $email . '-' . $password;
                if (in_array($key, $seenEmailPassword)) {
                    $errors[] = "Duplicate email + password combination in sheet at Row {$rowNumber}.";
                } else {
                    $seenEmailPassword[] = $key;
                }
            }

            // DB unique email
            if ($email && DB::table('users')->where('email', $email)->exists()) {
                $errors[] = "Email '{$email}' already exists in database at Row {$rowNumber}.";
            }

            // DB unique phone + country
            if ($phone && $countryCode) {
                if (DB::table('users')->where('phone', $phone)->where('country_code', $countryCode)->exists()) {
                    $errors[] = "Phone + country code combination already exists in database at Row {$rowNumber}.";
                }
            }

            // Role existence
            if ($roleId) {
                $roleExists = DB::table('roles')->where('id', $roleId)->where('guard_name', 'web')->exists();
                if (!$roleExists) {
                    $errors[] = "Invalid Role ID '{$roleId}' at Row {$rowNumber}.";
                }
            }

            // Required fields check
            if (empty($email)) {
                $errors[] = "Email is required at Row {$rowNumber}.";
            }
            if (empty($password)) {
                $errors[] = "Password is required at Row {$rowNumber}.";
            }
            if (empty($row['first_name'] ?? null)) {
                $errors[] = "First name is required at Row {$rowNumber}.";
            }
            if (empty($row['last_name'] ?? null)) {
                $errors[] = "Last name is required at Row {$rowNumber}.";
            }
            if (empty($roleId)) {
                $errors[] = "Role ID is required at Row {$rowNumber}.";
            }
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

        $row['password_plain'] = $row['password'] ?? null;

        //  Don't hash during preview so the preview shows readable data
        if (!$isPreview) {
            $row['password'] = isset($row['password']) ? bcrypt($row['password']) : null;
        }

        $requireEv = $this->isAdmin && ($row['require_email_verification'] ?? 1) == 0 ? 0 : 1;
        $status    = $this->isAdmin && ($row['status'] ?? 0) == 1 ? 1 : 0;

        $row['email_verified_at'] = $requireEv == 0 ? now() : null;
        $row['status']            = $status;

        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Row Validation Rules
    |--------------------------------------------------------------------------
    */

    public function getValidationRules(array $row): array
    {
        return [
            'first_name'                 => 'required|string',
            'last_name'                  => 'required|string',
            'email'                      => [
                'required',
                'email',
                // DB uniqueness checked per-row so it catches the already-hashed state
                \Illuminate\Validation\Rule::unique('users', 'email'),
            ],
            'password'                   => 'required|min:6',
            'role_id'                    => 'required|exists:roles,id',
            'dob'                        => 'nullable|date_format:Y-m-d',
            'doj'                        => 'nullable|date_format:Y-m-d',
            'status'                     => 'required|boolean',
            'require_email_verification' => 'required|boolean',
            'phone'                      => 'nullable|string',
            'country_code'               => 'nullable|string',
            'country_iso_code'           => 'nullable|string',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | After Model Creation
    |--------------------------------------------------------------------------
    */

    public function afterCreate($user, array $row): void
    {
        try {

            $role = Role::where('id', $row['role_id'])
                ->where('guard_name', 'web')
                ->firstOrFail();

            $user->assignRole($role);

            Workspace::find($this->workspaceId)
                ->users()
                ->attach($user->id);

            // Initialize leave balance
            try {
                (new LeaveBalanceService())
                    ->getOrCreateBalance($user->id, $this->workspaceId);
            } catch (\Exception $e) {
                Log::error("Leave balance init failed for user {$user->id}: " . $e->getMessage());
            }

            if ($row['require_email_verification']) {
                $user->notify(new VerifyEmail($user));
            }

            if (isEmailConfigured()) {
                $template = Template::where('type', 'email')
                    ->where('name', 'account_creation')
                    ->first();

                if (!$template || $template->status !== 0) {
                    $user->notify(new AccountCreation($user, $row['password_plain']));
                }
            }

            logActivity('user', $user->id, $user->first_name . ' ' . $user->last_name);
        } catch (TransportExceptionInterface $e) {

            $user->delete();
            throw new \Exception("Email configuration error during user import.");
        } catch (Throwable $e) {

            $user->delete();
            throw new \Exception("User post-processing failed: " . $e->getMessage());
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
            return is_string($value)
                ? trim(strip_tags($value))
                : $value;
        }, $row);
    }
}
