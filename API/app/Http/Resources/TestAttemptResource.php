<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TestAttemptResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'test' => new TestResource($this->whenLoaded('test')),
            'user' => new UserResource($this->whenLoaded('user')),
            'score' => $this->score,
            'max_score' => $this->max_score,
            'percentage' => $this->percentage,
            'is_passed' => $this->is_passed,
            'time_spent_seconds' => $this->time_spent_seconds,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'responses' => QuestionResponseResource::collection($this->whenLoaded('responses')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
