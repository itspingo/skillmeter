<?php

namespace App\Models;

class TestResult extends BaseModel
{
    protected $table = 'test_questions';

    protected $fillable = [
        'client_id', 'user_id', 'created_by', 'test_id', 'score', 'total_questions', 'answers', 'duration', 'completed_at', 'base_lang', 'active', 'created_at'
    ];
}
