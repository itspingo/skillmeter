<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheatingEvent extends Model
{
    use SoftDeletes;

    protected $table = 'cheating_events';

    protected $fillable = [
        'client_id', 'user_id', 'created_by', 'timestamp', 'event_type', 'confidence', 'duration', 'face_detected', 'attention_state', 'voice_detected', 'forbidden_app', 'app_name', 'screenshot_path', 'base_lang', 'active', 'created_at', 'updated_at', 'deleted_at'
    ];


}