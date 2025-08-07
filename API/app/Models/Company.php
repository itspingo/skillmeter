<?php

namespace App\Models;

class Company extends BaseModel
{
    protected $table = 'companies';

    public function recruiters()
    {
        return $this->hasMany(RecruiterProfile::class, 'company_id');
    }
}
