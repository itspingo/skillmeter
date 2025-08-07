<?php

namespace App\Models;

class RecruiterProfile extends BaseModel
{
    protected $table = 'recruiter_profiles';

    public function user()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
