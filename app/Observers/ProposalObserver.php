<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Proposal;

class ProposalObserver
{
    /**
     * Handle the Proposal "created" event.
     */
    public function created(Proposal $proposal): void
    {
        ActivityLog::log(
            activityType: 'create',
            description: "Membuat proposal baru: {$proposal->judul}",
            model: $proposal,
            metadata: [
                'ministry' => $proposal->ministry?->nama,
                'status' => $proposal->status?->name,
            ]
        );
    }

    /**
     * Handle the Proposal "updated" event.
     */
    public function updated(Proposal $proposal): void
    {
        $changes = [];
        $original = $proposal->getOriginal();
        
        // Deteksi perubahan
        foreach ($proposal->getDirty() as $key => $value) {
            $changes[$key] = [
                'old' => $original[$key] ?? null,
                'new' => $value,
            ];
        }
        
        if (!empty($changes)) {
            ActivityLog::log(
                activityType: 'update',
                description: "Mengupdate proposal: {$proposal->judul}",
                model: $proposal,
                changes: $changes,
                metadata: [
                    'ministry' => $proposal->ministry?->nama,
                    'status' => $proposal->status?->name,
                ]
            );
        }
    }

    /**
     * Handle the Proposal "deleted" event.
     */
    public function deleted(Proposal $proposal): void
    {
        ActivityLog::log(
            activityType: 'delete',
            description: "Menghapus proposal: {$proposal->judul}",
            model: $proposal,
            metadata: [
                'ministry' => $proposal->ministry?->nama,
                'status' => $proposal->status?->name,
            ]
        );
    }
}
