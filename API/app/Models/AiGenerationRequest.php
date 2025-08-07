<?php

namespace App\Models;

class AiGenerationRequest extends BaseModel
{
    protected $table = 'ai_generation_requests';

    protected $casts = [
        'parameters' => 'array',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function generatedContent()
    {
        return $this->hasMany(AiGeneratedContent::class, 'request_id');
    }
}
