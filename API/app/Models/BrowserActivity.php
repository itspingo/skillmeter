<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrowserActivity extends Model
{
    use SoftDeletes;

    protected $table = 'browser_activity';

    protected $fillable = [
        'client_id', 'user_id', 'created_by', 'test_id', 'timestamp', 'browser_title', 'duration', 'base_lang', 'active', 'created_at', 'updated_at', 'deleted_at'
    ];

}