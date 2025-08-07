<?php

namespace App\Models;

class ResponseSelectedOption extends BaseModel
{
    protected $table = 'response_selected_options';
    protected $primaryKey = null;
    public $incrementing = false;

    public function response()
    {
        return $this->belongsTo(QuestionResponse::class, 'response_id');
    }

    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'option_id');
    }
}
