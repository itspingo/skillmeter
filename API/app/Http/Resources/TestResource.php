<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'time_limit' => $this->time_limit,
            'difficulty_id' => $this->difficulty_id,
            //'difficulty' => $this->whenLoaded('difficulty'), // Assuming you have a difficulty relationship
            'is_public' => (bool)$this->is_public,
            'is_active' => (bool)$this->is_active,
            'pass_threshold' => $this->pass_threshold,
            'show_score' => (bool)$this->show_score,
            'show_answers' => (bool)$this->show_answers,
            'randomize_questions' => (bool)$this->randomize_questions,
            'allow_backtracking' => (bool)$this->allow_backtracking,
            'instructions' => $this->instructions,
            'base_lang' => $this->base_lang,
            'active' => (bool)$this->active,
            //'created_by' => new UserResource($this->whenLoaded('creator')),
            //'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->whenNotNull($this->deleted_at),
        ];
    }
}