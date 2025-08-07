<?php

namespace App\Models;

class AiGeneratedContent extends BaseModel
{
    protected $table = 'ai_generated_content';

    protected $casts = [
        'metadata' => 'array',
        'is_used' => 'boolean',
    ];

    public function request()
    {
        return $this->belongsTo(AiGenerationRequest::class, 'request_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
