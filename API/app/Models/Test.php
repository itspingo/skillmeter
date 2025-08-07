<?php

namespace App\Models;

class Test extends BaseModel
{
    protected $table = 'tests';

    public static function validationRules($context = 'create')
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'status' => 'in:draft,published,archived',
            'difficulty_level_id' => 'nullable|exists:difficulty_levels,id',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'test_questions', 'test_id', 'question_id')
            ->withPivot('question_order', 'section_name', 'weight')
            ->orderBy('test_questions.question_order');
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
