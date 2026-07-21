<?php

namespace App\Services\BulkImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\BulkImport\ImportModuleInterface;
use Maatwebsite\Excel\Facades\Excel;

class BulkImportService
{
    public function parse(string $directory, UploadedFile $file)
    {
        try {
            $tempPath = $file->store($directory);
            $extension = strtolower($file->getClientOriginalExtension());
            $fullPath = storage_path("app/{$tempPath}");

            if (in_array($extension, ['xlsx', 'xls'])) {
                $data = Excel::toArray([], $fullPath);
            } elseif ($extension === 'csv') {
                $data = Excel::toArray([], $fullPath, null, \Maatwebsite\Excel\Excel::CSV);
            } else {
                throw new \Exception('Unsupported file type');
            }

            $sheet = $data[0] ?? [];

            if (count($sheet) < 2) {
                throw new \Exception('File contains insufficient data');
            }

            // Find which column indexes have valid (non-null, non-empty) headers
            $rawHeaders = $sheet[0];
            $validIndexes = [];
            $headers = [];

            foreach ($rawHeaders as $index => $header) {
                if (!is_null($header) && trim((string)$header) !== '' && strtolower(trim((string)$header)) !== 'null') {
                    $validIndexes[] = $index;
                    $headers[] = $header;
                }
            }

            // Strip invalid columns from each preview row too
            $rows = array_slice($sheet, 1, 5);
            $filteredRows = array_map(function ($row) use ($validIndexes) {
                return array_values(array_intersect_key($row, array_flip($validIndexes)));
            }, $rows);

            return response()->json([
                'headers'    => $headers,
                'rows'       => $filteredRows,
                'temp_path'  => $tempPath,
                'total_rows' => count($sheet) - 1
            ]);
        } catch (\Exception $e) {
            Log::error('File parsing error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function preview(string $tempPath, array $mapping, ImportModuleInterface $module)
    {


        if (!Storage::exists($tempPath)) {
            throw new \Exception('Uploaded file not found');
        }

        $filePath = storage_path("app/{$tempPath}");
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'])) {
            $data = Excel::toArray([], $filePath);
        } elseif ($extension === 'csv') {
            $data = Excel::toArray([], $filePath, null, \Maatwebsite\Excel\Excel::CSV);
        } else {
            throw new \Exception('Unsupported file type');
        }

        $sheet = $data[0] ?? [];

        if (count($sheet) < 2) {
            throw new \Exception('File contains insufficient data');
        }

        $headers = array_map(fn($h) => strtolower(trim($h)), $sheet[0]);
        $rows = array_slice($sheet, 1);

        $mappedData = [];

        foreach ($rows as $index => $row) {

            if ($index >= 5) break;

            $mappedRow = [];

            foreach ($mapping as $dbField => $excelField) {

                $excelField = strtolower(trim($excelField));
                $columnIndex = array_search($excelField, $headers);

                $mappedRow[$dbField] =
                    ($columnIndex !== false && isset($row[$columnIndex]))
                    ? trim($row[$columnIndex])
                    : null;
            }

            // Optional: allow module to transform preview data
            $mappedRow = $module->transformRow($mappedRow, true);

            $mappedData[] = $mappedRow;
        }

        return [
            'mapped_data' => $mappedData,
            'total_rows' => count($rows)
        ];
    }

    public function import(string $tempPath, array $mapping, ImportModuleInterface $module): array
    {
        if (!Storage::exists($tempPath)) {
            throw new \Exception('Uploaded file not found');
        }

        $filePath = storage_path("app/{$tempPath}");
        $rows = Excel::toArray([], $filePath)[0] ?? [];

        if (count($rows) <= 1) {
            throw new \Exception('No data to import');
        }

        $headers = array_map(fn($h) => strtolower(trim($h)), $rows[0]);
        unset($rows[0]);

        // Map ALL rows first
        $allMappedRows = [];
        foreach ($rows as $row) {
            $allMappedRows[] = $this->mapRow($row, $headers, $mapping);
        }

        // Run sheet-level validation BEFORE touching the DB
        $beforeErrors = $module->beforeImport($allMappedRows);
        if (!empty($beforeErrors)) {
            Storage::delete($tempPath);
            return [
                'successful'  => 0,
                'failed'      => count($allMappedRows),
                'total'       => count($allMappedRows),
                'imported'    => [],
                'failed_rows' => [['row' => 'Sheet Validation', 'data' => [], 'errors' => $beforeErrors]],
                'ids'         => [],
            ];
        }

        // Now process row by row
        $errors = [];
        $successful = [];
        $failedRows = [];
        $ids = [];
        $rowNumber = 1;

        foreach ($allMappedRows as $mappedRow) {
            $rowNumber++;

            try {
                $mappedRow = $module->transformRow($mappedRow);

                $validator = Validator::make($mappedRow, $module->getValidationRules($mappedRow));

                if ($validator->fails()) {
                    $errors["Row {$rowNumber}"] = $validator->errors()->messages();
                    $failedRows[] = [
                        'row'    => $rowNumber,
                        'data'   => $mappedRow,
                        'errors' => $validator->errors()->messages()
                    ];
                    continue;
                }

                $modelClass = $module->getModelClass();
                $model = $modelClass::create($mappedRow);
                $module->afterCreate($model, $mappedRow);

                $successful[] = $model->toArray();
                $ids[] = $model->id;
            } catch (\Exception $e) {
                $errors["Row {$rowNumber}"] = ['exception' => $e->getMessage()];
                $failedRows[] = [
                    'row'    => $rowNumber,
                    'data'   => $mappedRow,
                    'errors' => ['exception' => $e->getMessage()]
                ];
            }
        }

        Storage::delete($tempPath);

        return [
            'successful'  => count($successful),
            'failed'      => count($failedRows),
            'total'       => count($allMappedRows),
            'imported'    => $successful,
            'failed_rows' => $failedRows,
            'ids'         => $ids,
        ];
    }

    private function mapRow(array $row, array $headers, array $mapping): array
    {
        $mappedRow = [];

        foreach ($mapping as $dbField => $excelField) {
            $excelField = strtolower(trim($excelField));
            $columnIndex = array_search($excelField, $headers);

            $mappedRow[$dbField] = ($columnIndex !== false && isset($row[$columnIndex]))
                ? trim($row[$columnIndex])
                : null;
        }

        return $mappedRow;
    }
}
