<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SessionService
{
    
  public function getUserSessions($userId)
{
    return DB::table('sessions')
        ->join('users', 'sessions.user_id', '=', 'users.id')
        ->where('sessions.user_id', $userId)
        ->orderByDesc('sessions.last_activity')
        ->select(
            'sessions.*',
            'users.name as user_name'
        )
        ->get();
}

    public function deleteSession($sessionId, $userId)
    {
        return DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function deleteOtherSessions($userId, $currentSessionId)
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}