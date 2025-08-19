<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheatingData extends Model
{
    use SoftDeletes;

    protected $table = 'cheating_data';

    protected $fillable = [
        'client_id', 'user_id', 'created_by', 'test_id', 'process_time', 'event_type', 'confidence', 'duration_seconds', 'face_detected', 'Screenshot_Path', 'base_lang', 'active', 'created_at', 'updated_at', 'deleted_at'
    ];


}