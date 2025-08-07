<?php

namespace App\Models;

class TestInvitation extends BaseModel
{
    protected $table = 'test_invitations';

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function attempt()
    {
        return $this->hasOne(TestAttempt::class, 'invitation_id');
    }
}
