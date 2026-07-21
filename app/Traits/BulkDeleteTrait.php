<?php

namespace App\Traits;

trait BulkDeleteTrait
{

    protected function bulkDeleteResponse(
        string $entityName,
        array $deleted = [],
        array $blocked = [],
        array $extra = []
    ) {
        $messageParts = [];

        if (!empty($deleted)) {
            $messageParts[] = count($deleted) . " {$entityName}(s) deleted successfully";
        }

        if (!empty($blocked['default'] ?? [])) {
            $messageParts[] =
                "The following {$entityName}(s) are default and cannot be deleted: "
                . implode(', ', array_unique($blocked['default']));
        }

        if (!empty($blocked['in_use'] ?? [])) {
            $messageParts[] =
                "The following {$entityName}(s) are in use and cannot be deleted: "
                . implode(', ', array_unique($blocked['in_use']));
        }

        if (!empty($blocked['failed'] ?? [])) {
            $messageParts[] =
                "Deletion failed for: "
                . implode(', ', array_unique($blocked['failed']));
        }

        $hasDeleted = !empty($deleted);

        return response()->json(array_merge([
            'error' => !$hasDeleted,
            'message' => implode('. ', $messageParts),
            'deleted' => $deleted,
            'blocked' => $blocked,
        ], $extra));
    }
}
