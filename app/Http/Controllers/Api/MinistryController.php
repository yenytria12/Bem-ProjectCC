<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ministry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MinistryController extends Controller
{
    /**
     * Get all ministries
     */
    public function index()
    {
        $ministries = Ministry::select('id', 'nama', 'deskripsi')
            ->withCount('users')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ministries
        ]);
    }

    /**
     * Get ministry detail
     */
    public function show($id)
    {
        $ministry = Ministry::with('users:id,name,email,ministry_id')
            ->withCount('users')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ministry
        ]);
    }

    /**
     * Create new ministry
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255|unique:ministries,nama',
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ministry = Ministry::create([
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kementerian berhasil ditambahkan',
            'data' => $ministry
        ], 201);
    }

    /**
     * Update ministry
     */
    public function update(Request $request, $id)
    {
        $ministry = Ministry::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|string|max:255|unique:ministries,nama,' . $id,
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ministry->update($request->only(['nama', 'deskripsi']));

        return response()->json([
            'success' => true,
            'message' => 'Kementerian berhasil diupdate',
            'data' => $ministry
        ]);
    }

    /**
     * Delete ministry
     */
    public function destroy($id)
    {
        $ministry = Ministry::withCount('users')->findOrFail($id);

        if ($ministry->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus kementerian yang masih memiliki anggota'
            ], 422);
        }

        $ministry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kementerian berhasil dihapus'
        ]);
    }
}
