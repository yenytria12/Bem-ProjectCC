<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    /**
     * Store device token
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'platform' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 422);
        }

        // Save or update token for this user
        // We use updateOrCreate to ensure one token entry per token value
        // But optimally we want to link it to current user
        
        // Fix: Include user_id in the creation attributes to satisfy NOT NULL constraint
        $token = DeviceToken::firstOrCreate(
            ['token' => $request->token],
            [
                'platform' => $request->platform,
                'user_id' => auth()->id() // Use authenticated user's ID
            ]
        );
        
        // Update user_id if changed (e.g. different user login on same device)
        if ($token->user_id !== auth()->id()) {
            $token->user_id = auth()->id();
            $token->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Token saved'
        ]);
    }

    /**
     * Remove token (on logout)
     */
    public function destroy(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        DeviceToken::where('token', $request->token)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token removed'
        ]);
    }
}
