<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;
        ActivityLog::log(
            activityType: 'login',
            description: "User {$user->name} ({$user->email}) melakukan login",
            model: $user,
            metadata: [
                'guard' => $event->guard,
            ]
        );
    }
}
