<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Get all activity logs for Super Admin
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $type = $request->get('type'); // filter by activity_type
        
        $query = ActivityLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc');
        
        if ($type) {
            $query->where('activity_type', $type);
        }
        
        $logs = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ]);
    }

    /**
     * Get activity log detail
     */
    public function show($id)
    {
        $log = ActivityLog::with('user:id,name,email')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $log
        ]);
    }

    /**
     * Get activity types for filter
     */
    public function types()
    {
        $types = ActivityLog::distinct()->pluck('activity_type')->filter()->values();
        
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }
}
