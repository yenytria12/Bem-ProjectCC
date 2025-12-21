<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    /**
     * Get all active announcements (read by all roles)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        
        $announcements = Announcement::with('user:id,name')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        $data = $announcements->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'type' => $item->type,
                'author' => $item->user?->name,
                'published_at' => $item->published_at ?? $item->created_at,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ]
        ]);
    }

    /**
     * Get unread count for notification badge
     */
    public function unreadCount()
    {
        $count = Announcement::where('is_active', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Create new announcement (only high roles)
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Only Super Admin, Presiden BEM, Wakil Presiden BEM, Sekretaris, Bendahara can create
        $allowedRoles = ['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara'];
        
        if (!array_intersect($userRoles, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk membuat pengumuman'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'nullable|in:info,warning,important,event',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $announcement = Announcement::create([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type ?? 'info',
            'user_id' => auth()->id(),
            'is_active' => true,
            'published_at' => now(),
        ]);

        // Send Push Notification
        $this->sendPushNotification($announcement);

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dibuat',
            'data' => $announcement
        ], 201);
    }

    /**
     * Send push notification to all users
     */
    private function sendPushNotification($announcement)
    {
        try {
            $tokens = \App\Models\DeviceToken::pluck('token')->toArray();
            
            if (empty($tokens)) {
                \Illuminate\Support\Facades\Log::info('No device tokens found for push notification');
                return;
            }

            // Expo Push API URL
            $url = 'https://exp.host/--/api/v2/push/send';
            
            // Chunk tokens (100 per chunk as per Expo docs)
            $tokenChunks = array_chunk($tokens, 100);
            
            foreach ($tokenChunks as $chunk) {
                $notifications = [];
                foreach ($chunk as $token) {
                    if (!\Illuminate\Support\Str::startsWith($token, 'ExponentPushToken') && 
                        !\Illuminate\Support\Str::startsWith($token, 'ExpoPushToken')) {
                        continue;
                    }

                    $notifications[] = [
                        'to' => $token,
                        'sound' => 'default',
                        'title' => 'Pengumuman Baru: ' . $announcement->title,
                        'body' => \Illuminate\Support\Str::limit($announcement->content, 100),
                        'data' => [
                            'screen' => 'Notifications',
                            'announcement_id' => $announcement->id
                        ],
                        'priority' => 'high',
                        'channelId' => 'announcements',
                    ];
                }

                if (empty($notifications)) continue;

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notifications));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                \Illuminate\Support\Facades\Log::info('Push Notification Sent', ['code' => $httpCode, 'response' => $response]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Push Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete announcement
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        $allowedRoles = ['Super Admin', 'Presiden BEM'];
        
        if (!array_intersect($userRoles, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus pengumuman'
            ], 403);
        }

        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dihapus'
        ]);
    }
}
