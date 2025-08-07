<?php

namespace App\Models;

class Tag extends BaseModel
{
    protected $table = 'tags';

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'question_tags', 'tag_id', 'question_id');
    }
}
