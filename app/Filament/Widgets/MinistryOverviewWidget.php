<?php

namespace App\Filament\Widgets;

use App\Models\Ministry;
use App\Models\ProgramKerja;
use App\Models\Proposal;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MinistryOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $userRoles = $user->getRoleNames();
        
        // Super Admin atau Presiden BEM - lihat semua data
        if ($user->hasRole(['Super Admin', 'Presiden BEM'])) {
            return $this->getAdminStats();
        }
        
        // Menteri - lihat data kementeriannya saja
        if ($user->hasRole('Menteri')) {
            return $this->getMenteriStats($user);
        }
        
        // Anggota - lihat data proposal dan program kerjanya sendiri
        if ($user->hasRole('Anggota')) {
            return $this->getAnggotaStats($user);
        }
        
        // Role lain (Sekretaris, Bendahara, Wakil Presiden) - lihat data sesuai kebutuhan
        return $this->getAdminStats(); // Default ke admin stats
    }
    
    protected function getAdminStats(): array
    {
        $totalMinistries = Ministry::count();
        $totalMembers = User::whereHas('ministry')->count();
        $totalPrograms = ProgramKerja::count();
        $activePrograms = ProgramKerja::whereIn('status', ['disetujui', 'berjalan'])->count();
        
        return [
            Stat::make('Total Kementerian', $totalMinistries)
                ->description('Jumlah kementerian aktif')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),
            Stat::make('Total Anggota', $totalMembers)
                ->description('Anggota yang terdaftar di kementerian')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Total Program Kerja', $totalPrograms)
                ->description('Program yang telah dibuat')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('warning'),
            Stat::make('Program Aktif', $activePrograms)
                ->description('Program yang sedang berjalan')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('success'),
        ];
    }
    
    protected function getMenteriStats(User $user): array
    {
        $ministry = $user->ministry;
        
        if (!$ministry) {
            return [
                Stat::make('Kementerian', 'Tidak tersedia')
                    ->description('Anda belum terdaftar di kementerian')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),
            ];
        }
        
        $anggotaCount = $ministry->users()
            ->whereHas('roles', fn($q) => $q->where('name', 'Anggota'))
            ->count();
        
        $proposalsCount = Proposal::whereHas('ministry', fn($q) => $q->where('id', $ministry->id))->count();
        
        $programsCount = $ministry->programKerjas()->count();
        $activePrograms = $ministry->programKerjas()
            ->whereIn('status', ['disetujui', 'berjalan'])
            ->count();
        
        return [
            Stat::make('Kementerian', $ministry->nama)
                ->description('Kementerian Anda')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),
            Stat::make('Anggota Kementerian', $anggotaCount)
                ->description('Jumlah anggota di kementerian')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Proposal', $proposalsCount)
                ->description('Total proposal kementerian')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
            Stat::make('Program Kerja', $programsCount)
                ->description($activePrograms . ' program aktif')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($activePrograms > 0 ? 'success' : 'gray'),
        ];
    }
    
    protected function getAnggotaStats(User $user): array
    {
        $myProposals = $user->proposals()->count();
        $pendingProposals = $user->proposals()
            ->whereHas('status', fn($q) => $q->whereIn('name', ['pending_menteri', 'pending_sekretaris', 'pending_bendahara', 'pending_wakil_presiden', 'pending_presiden']))
            ->count();
        
        $approvedProposals = $user->proposals()
            ->whereHas('status', fn($q) => $q->where('name', 'approved'))
            ->count();
        
        $myPrograms = $user->programKerjas()->count();
        $activePrograms = $user->programKerjas()
            ->whereIn('status', ['disetujui', 'berjalan'])
            ->count();
        
        return [
            Stat::make('Proposal Saya', $myProposals)
                ->description($pendingProposals . ' menunggu persetujuan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
            Stat::make('Proposal Disetujui', $approvedProposals)
                ->description('Proposal yang sudah disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Program Kerja Saya', $myPrograms)
                ->description('Total program kerja')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('warning'),
            Stat::make('Program Aktif', $activePrograms)
                ->description('Program yang sedang berjalan')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('success'),
        ];
    }
}

