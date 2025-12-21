<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\ProgramKerja;

class ProgramKerjaObserver
{
    /**
     * Handle the ProgramKerja "created" event.
     */
    public function created(ProgramKerja $programKerja): void
    {
        ActivityLog::log(
            activityType: 'create',
            description: "Membuat program kerja baru: {$programKerja->nama}",
            model: $programKerja,
            metadata: [
                'ministry' => $programKerja->ministry?->nama,
                'status' => $programKerja->status,
            ]
        );
    }

    /**
     * Handle the ProgramKerja "updated" event.
     */
    public function updated(ProgramKerja $programKerja): void
    {
        $changes = [];
        $original = $programKerja->getOriginal();
        
        // Deteksi perubahan
        foreach ($programKerja->getDirty() as $key => $value) {
            $changes[$key] = [
                'old' => $original[$key] ?? null,
                'new' => $value,
            ];
        }
        
        if (!empty($changes)) {
            ActivityLog::log(
                activityType: 'update',
                description: "Mengupdate program kerja: {$programKerja->nama}",
                model: $programKerja,
                changes: $changes,
                metadata: [
                    'ministry' => $programKerja->ministry?->nama,
                    'status' => $programKerja->status,
                ]
            );
        }
    }

    /**
     * Handle the ProgramKerja "deleted" event.
     */
    public function deleted(ProgramKerja $programKerja): void
    {
        ActivityLog::log(
            activityType: 'delete',
            description: "Menghapus program kerja: {$programKerja->nama}",
            model: $programKerja,
            metadata: [
                'ministry' => $programKerja->ministry?->nama,
                'status' => $programKerja->status,
            ]
        );
    }
}
