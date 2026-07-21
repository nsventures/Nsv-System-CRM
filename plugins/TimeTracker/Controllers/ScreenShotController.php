<?php

namespace Plugins\TimeTracker\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\TimeTracker\Models\Screenshot;

class ScreenShotController extends Controller
{
    /**
     * Display the screenshot gallery page.
     */
    public function index(Request $request)
    {
        $users = User::select('id', 'first_name', 'last_name')->orderBy('id')->get();

        return view('timetracker::screenshots.index', compact('users'));
    }

    /**
     * Fetch screenshots data for gallery with filters and pagination.
     */
    public function data(Request $request)
    {
        $query = Screenshot::query()
            ->with('user:id,first_name,last_name')
            ->orderByDesc('captured_at');

        if ($request->filled('user_ids')) {
            $query->whereIn('user_id', $request->user_ids);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('captured_at', '>=', Carbon::parse($request->start_date));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('captured_at', '<=', Carbon::parse($request->end_date));
        }

        $screenshots = $query->paginate(24);

        return response()->json([
            'data' => $screenshots->map(function ($screenshot) {
                return [
                    'id' => $screenshot->id,
                    'user_name' => $screenshot->user->first_name .' '. $screenshot->user->last_name ?? 'N/A',
                    'image_url' => asset('storage/' . $screenshot->screenshot_path),
                    'captured_at' => $screenshot->captured_at->toISOString(),
                    'file_size_kb' => $screenshot->file_size ? round($screenshot->file_size / 1024, 2) . ' KB' : 'N/A',
                    'metadata' => $screenshot->metadata ? json_decode($screenshot->metadata, true) : [],
                ];
            }),
            'pagination' => [
                'current_page' => $screenshots->currentPage(),
                'last_page' => $screenshots->lastPage(),
                'per_page' => $screenshots->perPage(),
                'total' => $screenshots->total(),
            ],
        ]);
    }

    public function show($id)
    {
        // Logic to show a specific screenshot
        // This is a placeholder, implement your logic here
        return response()->json([]);
    }

    public function destroy($id)
    {
        // Logic to delete a specific screenshot
        // This is a placeholder, implement your logic here
        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        // Logic to bulk delete screenshots
        // This is a placeholder, implement your logic here
        return response()->json(['success' => true]);
    }
}
