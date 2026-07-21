<?php

namespace App\Services\BulkImport\Modules;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStage;
use App\Services\BulkImport\ImportModuleInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeadImportModule implements ImportModuleInterface
{
    protected int $workspaceId;
    protected int $authUserId;

    public function __construct()
    {
        $this->workspaceId = getWorkspaceId();
        $this->authUserId  = auth()->id();
    }

    public function getModelClass(): string
    {
        return Lead::class;
    }

    /*
    |--------------------------------------------------------------------------
    | Sheet-Level Validation (Before Import)
    |--------------------------------------------------------------------------
    */

    public function beforeImport(array $rows): array
    {
        $errors     = [];
        $seenEmails = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $email          = $row['email'] ?? null;
            $firstName      = $row['first_name'] ?? null;
            $lastName       = $row['last_name'] ?? null;
            $phone          = $row['phone'] ?? null;
            $countryCode    = $row['country_code'] ?? null;
            $countryIso     = $row['country_iso_code'] ?? null;
            $company        = $row['company'] ?? null;
            $source         = $row['source'] ?? null;
            $stage          = $row['stage'] ?? null;

            // Required fields
            if (empty($firstName))   $errors[] = "First name is required at Row {$rowNumber}.";
            if (empty($lastName))    $errors[] = "Last name is required at Row {$rowNumber}.";
            if (empty($company))     $errors[] = "Company is required at Row {$rowNumber}.";
            if (empty($source))      $errors[] = "Source is required at Row {$rowNumber}.";
            if (empty($stage))       $errors[] = "Stage is required at Row {$rowNumber}.";
            if (empty($phone))       $errors[] = "Phone is required at Row {$rowNumber}.";
            if (empty($countryCode)) $errors[] = "Country code is required at Row {$rowNumber}.";
            if (empty($countryIso))  $errors[] = "Country ISO code is required at Row {$rowNumber}.";

            // Email
            if (empty($email)) {
                $errors[] = "Email is required at Row {$rowNumber}.";
            } else {
                // Duplicate in sheet
                if (in_array($email, $seenEmails)) {
                    $errors[] = "Duplicate email '{$email}' in sheet at Row {$rowNumber}.";
                } else {
                    $seenEmails[] = $email;
                }

                // Duplicate in DB
                if (DB::table('leads')->where('email', $email)->exists()) {
                    $errors[] = "Email '{$email}' already exists in database at Row {$rowNumber}.";
                }
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

        if ($isPreview) {
            return $row;
        }

        // Resolve or create Source
        if (!empty($row['source'])) {
            $source = LeadSource::where('name', $row['source'])
                ->where(function ($q) {
                    $q->where('workspace_id', $this->workspaceId)
                        ->orWhere(function ($q2) {
                            $q2->whereNull('workspace_id')->where('is_default', true);
                        });
                })->first();

            if (!$source) {
                $source = LeadSource::create([
                    'workspace_id' => $this->workspaceId,
                    'name'         => $row['source'],
                    'is_default'   => false,
                ]);
            }

            $row['source_id'] = $source->id;
        }
        unset($row['source']);

        // Resolve or create Stage
        if (!empty($row['stage'])) {
            $stage = LeadStage::where('name', $row['stage'])
                ->where(function ($q) {
                    $q->where('workspace_id', $this->workspaceId)
                        ->orWhere(function ($q2) {
                            $q2->whereNull('workspace_id')->where('is_default', true);
                        });
                })->first();

            if (!$stage) {
                $maxOrder = LeadStage::getNextOrderForWorkspace($this->workspaceId);
                $stage = LeadStage::create([
                    'workspace_id' => $this->workspaceId,
                    'name'         => $row['stage'],
                    'slug'         => generateUniqueSlug($row['stage'], LeadStage::class),
                    'order'        => $maxOrder + 1,
                    'color'        => 'primary',
                    'is_default'   => false,
                ]);
            }

            $row['stage_id'] = $stage->id;
        }
        unset($row['stage']);

        // System fields
        $row['workspace_id'] = $this->workspaceId;
        $row['created_by']   = $this->authUserId;
        $row['assigned_to']  = $this->authUserId;

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
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|unique:leads,email',
            'phone'            => 'required|string|max:20',
            'country_code'     => 'required|string|max:5',
            'country_iso_code' => 'required|string|size:2',
            'source_id'        => 'required|exists:lead_sources,id',
            'stage_id'         => 'required|exists:lead_stages,id',
            'company'          => 'required|string|max:255',
            'job_title'        => 'nullable|string|max:255',
            'industry'         => 'nullable|string|max:255',
            'website'          => 'nullable|url|max:255',
            'linkedin'         => 'nullable|url|max:255',
            'instagram'        => 'nullable|url|max:255',
            'facebook'         => 'nullable|url|max:255',
            'pinterest'        => 'nullable|url|max:255',
            'city'             => 'nullable|string|max:255',
            'state'            => 'nullable|string|max:255',
            'zip'              => 'nullable|string|max:20',
            'country'          => 'nullable|string|max:255',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | After Model Creation
    |--------------------------------------------------------------------------
    */

    public function afterCreate($lead, array $row): void
    {
        // No pivot relationships or notifications needed for leads
        // logActivity can go here if needed
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