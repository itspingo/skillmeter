<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'type' => new QuestionTypeResource($this->whenLoaded('type')),
            'category' => new QuestionCategoryResource($this->whenLoaded('category')),
            'difficulty' => new DifficultyLevelResource($this->whenLoaded('difficulty')),
            'explanation' => $this->explanation,
            'time_limit_seconds' => $this->time_limit_seconds,
            'max_score' => $this->max_score,
            'is_public' => $this->is_public,
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
