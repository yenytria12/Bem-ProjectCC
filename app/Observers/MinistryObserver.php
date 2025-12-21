<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Ministry;

class MinistryObserver
{
    /**
     * Handle the Ministry "created" event.
     */
    public function created(Ministry $ministry): void
    {
        ActivityLog::log(
            activityType: 'create',
            description: "Membuat kementerian baru: {$ministry->nama}",
            model: $ministry
        );
    }

    /**
     * Handle the Ministry "updated" event.
     */
    public function updated(Ministry $ministry): void
    {
        $changes = [];
        $original = $ministry->getOriginal();
        
        // Deteksi perubahan
        foreach ($ministry->getDirty() as $key => $value) {
            $changes[$key] = [
                'old' => $original[$key] ?? null,
                'new' => $value,
            ];
        }
        
        if (!empty($changes)) {
            ActivityLog::log(
                activityType: 'update',
                description: "Mengupdate kementerian: {$ministry->nama}",
                model: $ministry,
                changes: $changes
            );
        }
    }

    /**
     * Handle the Ministry "deleted" event.
     */
    public function deleted(Ministry $ministry): void
    {
        ActivityLog::log(
            activityType: 'delete',
            description: "Menghapus kementerian: {$ministry->nama}",
            model: $ministry
        );
    }
}
