<?php 

 namespace App\Services\BulkImport;

use App\Services\BulkImport\ImportModuleInterface;
use App\Services\BulkImport\Modules\ClientImportModule;
use App\Services\BulkImport\Modules\UserImportModule;
use App\Services\BulkImport\Modules\ProjectImportModule;
use App\Services\BulkImport\Modules\TaskImportModule;
use App\Services\BulkImport\Modules\LeadImportModule;


class ImportModuleResolver
{
    public static function resolve(string $type): ImportModuleInterface
    {
        return match ($type) {
            'users' => new UserImportModule(),
            'clients' => new ClientImportModule(),
            'leads' => new LeadImportModule(),
            'tasks' => new TaskImportModule(),
            'projects' => new ProjectImportModule(),
            default => throw new \Exception('Invalid import type'),
        };
    }
}
