<?php

namespace App\Models;

class Test extends BaseModel
{
    protected $table = 'tests';

    protected $fillable = [
       'name', 'description', 'created_by', 'time_limit', 'difficulty_id', 'is_public', 'is_active', 'pass_threshold', 'show_score', 'show_answers', 'randomize_questions', 'allow_backtracking', 'instructions', 'base_lang', 'active', 'created_at', 'updated_at', 'deleted_at'
    ];

    

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'test_id');
    }

    public function testQuestions()
    {
        return $this->hasMany(TestQuestion::class, 'test_id');
    }

    public function invitations()
    {
        return $this->hasMany(TestInvitation::class, 'test_id');
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class, 'test_id');
    }
}
