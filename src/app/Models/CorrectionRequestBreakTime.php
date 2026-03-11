<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequestBreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'break_index',
        'requested_in_at',
        'requested_out_at',
    ];

    public function correctionRequest(){
        return $this->belongsTo(CorrectionRequest::class);
    }
}
