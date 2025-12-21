<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProposalController extends Controller
{
    /**
     * Get all proposals with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $status = $request->get('status');
        $user = auth()->user();
        
        $query = Proposal::with(['ministry:id,nama', 'user:id,name', 'status:id,name'])
            ->orderBy('created_at', 'desc');
        
        // Role-based filtering
        // Anggota: only see own proposals
        // Menteri: only see proposals from own ministry
        // Higher roles (Super Admin, Presiden BEM, Wakil Presiden BEM, Sekretaris, Bendahara): see all
        $userRoles = $user->roles->pluck('name')->toArray();
        
        if (in_array('Anggota', $userRoles)) {
            // Anggota can only see their own proposals
            $query->where('user_id', $user->id);
        } elseif (in_array('Menteri', $userRoles)) {
            // Menteri can see proposals from their ministry
            $query->where('ministry_id', $user->ministry_id);
        }
        // Super Admin, Presiden BEM, Wakil Presiden, Sekretaris, Bendahara can see all
        
        if ($search) {
            $query->where('judul', 'like', "%{$search}%");
        }
        
        if ($status) {
            $query->where('status_id', $status);
        }
        
        $proposals = $query->paginate($perPage);
        
        $data = $proposals->map(function ($proposal) {
            return [
                'id' => $proposal->id,
                'judul' => $proposal->judul,
                'deskripsi' => $proposal->deskripsi,
                'ministry' => $proposal->ministry?->nama,
                'ministry_id' => $proposal->ministry_id,
                'user' => $proposal->user?->name,
                'user_id' => $proposal->user_id,
                'status' => $proposal->status?->name,
                'status_id' => $proposal->status_id,
                'tanggal_pengajuan' => $proposal->tanggal_pengajuan,
                'created_at' => $proposal->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'per_page' => $proposals->perPage(),
                'total' => $proposals->total(),
            ]
        ]);
    }

    /**
     * Get proposal detail
     */
    public function show($id)
    {
        $proposal = Proposal::with(['ministry', 'user', 'status'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $proposal->id,
                'judul' => $proposal->judul,
                'deskripsi' => $proposal->deskripsi,
                'keterangan' => $proposal->keterangan,
                'ministry' => $proposal->ministry?->nama,
                'ministry_id' => $proposal->ministry_id,
                'user' => $proposal->user?->name,
                'user_id' => $proposal->user_id,
                'status' => $proposal->status?->name,
                'status_id' => $proposal->status_id,
                'tanggal_pengajuan' => $proposal->tanggal_pengajuan,
                'file_path' => $proposal->file_path,
                'created_at' => $proposal->created_at,
            ]
        ]);
    }

    /**
     * Create new proposal
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'ministry_id' => 'required|exists:ministries,id',
            'status_id' => 'nullable|exists:statuses,id',
            'keterangan' => 'nullable|string',
            'tanggal_pengajuan' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $proposal = Proposal::create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'ministry_id' => $request->ministry_id,
            'user_id' => auth()->id(),
            'status_id' => $request->status_id ?? 1,
            'keterangan' => $request->keterangan,
            'tanggal_pengajuan' => $request->tanggal_pengajuan ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proposal created successfully',
            'data' => $proposal
        ], 201);
    }

    /**
     * Update proposal
     */
    public function update(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string',
            'ministry_id' => 'sometimes|exists:ministries,id',
            'status_id' => 'sometimes|exists:statuses,id',
            'keterangan' => 'nullable|string',
            'tanggal_pengajuan' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $proposal->update($request->only([
            'judul', 'deskripsi', 'ministry_id', 'status_id', 'keterangan', 'tanggal_pengajuan'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Proposal updated successfully',
            'data' => $proposal
        ]);
    }

    /**
     * Delete proposal
     */
    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);
        $proposal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proposal deleted successfully'
        ]);
    }

    /**
     * Get all statuses
     */
    public function statuses()
    {
        $statuses = \App\Models\Status::all();
        
        return response()->json([
            'success' => true,
            'data' => $statuses->map(function($status) {
                return [
                    'id' => $status->id,
                    'name' => $status->name,
                    'label' => $this->getStatusLabel($status->name),
                ];
            })
        ]);
    }

    /**
     * Update proposal status only
     */
    public function updateStatus(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status_id' => 'required|exists:statuses,id',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $proposal->update([
            'status_id' => $request->status_id,
            'keterangan' => $request->keterangan ?? $proposal->keterangan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $proposal->load('status')
        ]);
    }

    private function getStatusLabel($name)
    {
        $labels = [
            'pending_menteri' => 'Review Menteri',
            'pending_sekretaris' => 'Review Sekretaris',
            'pending_bendahara' => 'Review Bendahara',
            'pending_wakil_presiden' => 'Review Wakil Presiden',
            'pending_presiden' => 'Review Presiden',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'revisi' => 'Revisi',
        ];
        return $labels[$name] ?? $name;
    }
}
