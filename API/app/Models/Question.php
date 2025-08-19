<?php

namespace App\Models;

class Question extends BaseModel
{
    protected $table = 'questions';

    protected $fillable = [
        'test_id',
        'type_id',
        'difficulty_id',
        'question_data',
        'is_ai_generated',
        'explanation',
        'time_limit',
        'max_score',
        'base_lang',
        'active',
        'created_by'
    ];

    public function type()
    {
        return $this->belongsTo(QuestionType::class, 'type_id');
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function difficulty()
    {
        return $this->belongsTo(DifficultyLevel::class, 'difficulty_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'question_tags', 'question_id', 'tag_id');
    }

    public function testQuestions()
    {
        return $this->hasMany(TestQuestion::class, 'question_id');
    }

    public function responses()
    {
        return $this->hasMany(QuestionResponse::class, 'question_id');
    }

    public function aiGeneratedContent()
    {
        return $this->hasMany(AiGeneratedContent::class, 'question_id');
    }
}
