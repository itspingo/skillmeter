<?php

namespace App\Models;

class QuestionResponse extends BaseModel
{
    protected $table = 'question_responses';

    protected $casts = [
        'response_options' => 'array',
        'is_correct' => 'boolean',
        'score' => 'float',
        'max_score' => 'float',
    ];

    public function attempt()
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function questionType()
    {
        return $this->belongsTo(QuestionType::class, 'question_type_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function selectedOptions()
    {
        return $this->hasMany(ResponseSelectedOption::class, 'response_id');
    }
}
