<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        ActivityLog::log(
            activityType: 'create',
            description: "Membuat user baru: {$user->name} ({$user->email})",
            model: $user,
            metadata: [
                'ministry' => $user->ministry?->nama,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]
        );
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changes = [];
        $original = $user->getOriginal();
        
        // Deteksi perubahan
        foreach ($user->getDirty() as $key => $value) {
            // Skip password changes untuk security
            if ($key === 'password') {
                continue;
            }
            $changes[$key] = [
                'old' => $original[$key] ?? null,
                'new' => $value,
            ];
        }
        
        if (!empty($changes)) {
            ActivityLog::log(
                activityType: 'update',
                description: "Mengupdate user: {$user->name} ({$user->email})",
                model: $user,
                changes: $changes,
                metadata: [
                    'ministry' => $user->ministry?->nama,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ]
            );
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        ActivityLog::log(
            activityType: 'delete',
            description: "Menghapus user: {$user->name} ({$user->email})",
            model: $user,
            metadata: [
                'ministry' => $user->ministry?->nama,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]
        );
    }
}
