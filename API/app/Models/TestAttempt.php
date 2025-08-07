<?php

namespace App\Models;

class TestAttempt extends BaseModel
{
    protected $table = 'test_attempts';

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'float',
        'max_score' => 'float',
        'percentage' => 'float',
        'is_passed' => 'boolean',
        'proctoring_flags' => 'array',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invitation()
    {
        return $this->belongsTo(TestInvitation::class, 'invitation_id');
    }

    public function responses()
    {
        return $this->hasMany(QuestionResponse::class, 'attempt_id');
    }
}
