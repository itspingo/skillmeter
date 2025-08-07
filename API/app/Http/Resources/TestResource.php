<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'time_limit_minutes' => $this->time_limit_minutes,
            'pass_threshold' => $this->pass_threshold,
            'is_public' => $this->is_public,
            'randomize_questions' => $this->randomize_questions,
            'instructions' => $this->instructions,
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
