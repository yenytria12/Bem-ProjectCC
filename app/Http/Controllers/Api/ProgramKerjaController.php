<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgramKerjaController extends Controller
{
    /**
     * Get all program kerja with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $status = $request->get('status');
        $ministryId = $request->get('ministry_id');
        $user = auth()->user();
        
        $query = ProgramKerja::with(['ministry:id,nama', 'user:id,name'])
            ->orderBy('created_at', 'desc');
        
        // Role-based filtering
        $userRoles = $user->roles->pluck('name')->toArray();
        
        if (in_array('Anggota', $userRoles)) {
            // Anggota can only see their own program kerja
            $query->where('user_id', $user->id);
        } elseif (in_array('Menteri', $userRoles)) {
            // Menteri can see program kerja from their ministry
            $query->where('ministry_id', $user->ministry_id);
        }
        // Super Admin, Presiden BEM, Wakil Presiden, Sekretaris, Bendahara can see all
        
        if ($search) {
            $query->where('nama_program', 'like', "%{$search}%");
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($ministryId) {
            $query->where('ministry_id', $ministryId);
        }
        
        $programs = $query->paginate($perPage);
        
        $data = $programs->map(function ($program) {
            return [
                'id' => $program->id,
                'nama_program' => $program->nama_program,
                'deskripsi' => $program->deskripsi,
                'ministry' => $program->ministry?->nama,
                'ministry_id' => $program->ministry_id,
                'user' => $program->user?->name,
                'user_id' => $program->user_id,
                'status' => $program->status,
                'tanggal_mulai' => $program->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $program->tanggal_selesai?->format('Y-m-d'),
                'anggaran' => $program->anggaran,
                'created_at' => $program->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ]
        ]);
    }

    /**
     * Get program kerja detail
     */
    public function show($id)
    {
        $program = ProgramKerja::with(['ministry', 'user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $program->id,
                'nama_program' => $program->nama_program,
                'deskripsi' => $program->deskripsi,
                'ministry' => $program->ministry?->nama,
                'ministry_id' => $program->ministry_id,
                'user' => $program->user?->name,
                'user_id' => $program->user_id,
                'status' => $program->status,
                'tanggal_mulai' => $program->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $program->tanggal_selesai?->format('Y-m-d'),
                'anggaran' => $program->anggaran,
                'catatan' => $program->catatan,
                'created_at' => $program->created_at,
            ]
        ]);
    }

    /**
     * Create new program kerja
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_program' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'ministry_id' => 'required|exists:ministries,id',
            'status' => 'nullable|string|in:Belum Mulai,Sedang Berjalan,Selesai,Ditunda',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'anggaran' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $program = ProgramKerja::create([
            'nama_program' => $request->nama_program,
            'deskripsi' => $request->deskripsi,
            'ministry_id' => $request->ministry_id,
            'user_id' => auth()->id(),
            'status' => $request->status ?? 'Belum Mulai',
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'anggaran' => $request->anggaran,
            'catatan' => $request->catatan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Program Kerja created successfully',
            'data' => $program
        ], 201);
    }

    /**
     * Update program kerja
     */
    public function update(Request $request, $id)
    {
        $program = ProgramKerja::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama_program' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string',
            'ministry_id' => 'sometimes|exists:ministries,id',
            'status' => 'sometimes|string|in:Belum Mulai,Sedang Berjalan,Selesai,Ditunda',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'anggaran' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $program->update($request->only([
            'nama_program', 'deskripsi', 'ministry_id', 'status', 
            'tanggal_mulai', 'tanggal_selesai', 'anggaran', 'catatan'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Program Kerja updated successfully',
            'data' => $program
        ]);
    }

    /**
     * Delete program kerja
     */
    public function destroy($id)
    {
        $program = ProgramKerja::findOrFail($id);
        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program Kerja deleted successfully'
        ]);
    }

    /**
     * Get status options
     */
    public function statuses()
    {
        return response()->json([
            'success' => true,
            'data' => ['Belum Mulai', 'Sedang Berjalan', 'Selesai', 'Ditunda']
        ]);
    }
}
