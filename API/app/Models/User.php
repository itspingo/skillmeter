<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token', 'deleted_at'];
    
    protected $fillable = [
        'client_id', 'created_by', 'user_type', 'email', 'password', 'first_name', 'last_name', 'company_name', 'is_active', 'last_login', 'base_lang', 'active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'last_login' => 'datetime',
    ];

    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }

    public function recruiterProfile()
    {
        return $this->hasOne(RecruiterProfile::class);
    }

    public function testsCreated()
    {
        return $this->hasMany(Test::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
