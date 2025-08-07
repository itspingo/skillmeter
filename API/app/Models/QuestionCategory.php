
<?php

namespace App\Models;

class QuestionCategory extends BaseModel
{
    protected $table = 'question_categories';

    public function parent()
    {
        return $this->belongsTo(QuestionCategory::class, 'parent_category_id');
    }

    public function children()
    {
        return $this->hasMany(QuestionCategory::class, 'parent_category_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
