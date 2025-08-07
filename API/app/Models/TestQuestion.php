<?php

namespace App\Models;

class TestQuestion extends BaseModel
{
    protected $table = 'test_questions';

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
