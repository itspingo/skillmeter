<?php

namespace App\Models;

class DifficultyLevel extends BaseModel
{
    protected $table = 'difficulty_levels';

    public function questions()
    {
        return $this->hasMany(Question::class, 'difficulty_id');
    }
}
