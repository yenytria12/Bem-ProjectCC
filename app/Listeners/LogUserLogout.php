<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        /** @var User $user */
        $user = $event->user;
        ActivityLog::log(
            activityType: 'logout',
            description: "User {$user->name} ({$user->email}) melakukan logout",
            model: $user,
            metadata: [
                'guard' => $event->guard,
            ]
        );
    }
}
