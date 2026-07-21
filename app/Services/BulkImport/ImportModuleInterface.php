<?php

namespace App\Services\BulkImport;


interface ImportModuleInterface
{
    public function getModelClass(): string;

    public function getValidationRules(array $row): array;

    public function transformRow(array $row, bool $isPreview = false): array;

    public function afterCreate($model, array $row): void;

    public function beforeImport(array $rows): array;

}