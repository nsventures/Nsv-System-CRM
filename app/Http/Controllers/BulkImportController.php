<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Services\BulkImport\BulkImportModuleConfig;
use App\Services\BulkImport\BulkImportService;
use Illuminate\Http\Request;
use App\Services\BulkImport\ImportModuleResolver;
use Illuminate\Support\Facades\Validator;


class BulkImportController extends Controller
{
    protected BulkImportService $bulkImportService;

    public function __construct(BulkImportService $bulkImportService)
    {
        $this->bulkImportService = $bulkImportService;
    }

    // Render the generic bulk upload page for any module
    public function show(string $type)
    {

        $moduleConfig = BulkImportModuleConfig::get($type);
        return view('bulk-import.index', compact('moduleConfig'));
    }

    public function parse(Request $request)
    {
        try {
            $result = $this->bulkImportService->parse('temp_imports', $request->file('file'));
            return response()->json(['success' => true, 'message' => 'File parsed successfully', 'data' => $result->getData()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function preview(Request $request)
    {


        try {
            $module = ImportModuleResolver::resolve($request->type);
            $result = $this->bulkImportService->preview($request->temp_path, $request->mapping, $module);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function import(Request $request)
    {
        try {
            $module = ImportModuleResolver::resolve($request->type);
            $result = $this->bulkImportService->import($request->temp_path, $request->mapping, $module);


            $status = $result['failed'] > 0 ? ($result['successful'] > 0 ? 'partial' : 'failed') : 'success';

            return response()->json([
                'success' => $status === 'success',
                'message' => match ($status) {
                    'success' => 'Import completed successfully.',
                    'partial' => 'Import partially completed.',
                    'failed' => 'Import failed.',
                },
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
