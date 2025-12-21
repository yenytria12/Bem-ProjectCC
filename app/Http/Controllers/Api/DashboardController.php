<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ministry;
use App\Models\User;
use App\Models\Proposal;
use App\Models\ProgramKerja;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for all roles
     */
    public function getStats()
    {
        // Total kementerian
        $totalKementerian = Ministry::count();
        
        // Total anggota (exclude Super Admin/Admin roles)
        $totalAnggota = User::whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['Super Admin', 'Admin']);
        })->count();

        // Total Proposals
        $totalProposal = Proposal::count();
        
        // Total Program Kerja
        $totalProgramKerja = ProgramKerja::count();

        // Proposal by status
        $proposalPending = Proposal::whereHas('status', function($q) {
            $q->where('name', 'like', 'pending%');
        })->count();
        
        $proposalApproved = Proposal::whereHas('status', function($q) {
            $q->where('name', 'approved');
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_kementerian' => $totalKementerian,
                'total_anggota' => $totalAnggota,
                'total_proposal' => $totalProposal,
                'total_program_kerja' => $totalProgramKerja,
                'proposal_pending' => $proposalPending,
                'proposal_approved' => $proposalApproved,
            ]
        ]);
    }
}
