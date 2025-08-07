<?php

namespace App\Models;

class ActivityLog extends BaseModel
{
    protected $table = 'activity_logs';

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
