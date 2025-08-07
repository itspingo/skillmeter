<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheatingDataResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => optional($this->user)->name,
            'test' => optional($this->test)->title,
            'event_type' => $this->event_type,
            'confidence' => $this->confidence,
            'duration' => $this->duration_seconds,
            'face_detected' => $this->face_detected,
            'screenshot' => $this->screenshot_path ? 
                asset("storage/{$this->screenshot_path}") : null,
            'process_time' => $this->process_time->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}