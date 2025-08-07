<?php

namespace App\Models;

class QuestionType extends BaseModel
{
    protected $table = 'question_types';

    public function questions()
    {
        return $this->hasMany(Question::class, 'type_id');
    }
}
