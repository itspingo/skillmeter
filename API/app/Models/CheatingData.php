<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheatingData extends Model
{
    use SoftDeletes;

    protected $table = 'cheating_data';

    protected $fillable = [
        'user_id',
        'test_id',
        'process_time',
        'event_type',
        'confidence',
        'duration_seconds',
        'face_detected',
        'screenshot_path',
        'base_lang',
        'active'
    ];

    protected $casts = [
        'process_time' => 'datetime',
        'face_detected' => 'boolean',
        'active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}