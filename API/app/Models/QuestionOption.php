<?php

namespace App\Models;

class QuestionOption extends BaseModel
{
    protected $table = 'question_options';

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function selectedOptions()
    {
        return $this->hasMany(ResponseSelectedOption::class, 'option_id');
    }
}
