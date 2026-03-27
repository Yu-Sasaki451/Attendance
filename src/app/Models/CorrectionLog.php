<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'admin_id',
        'attendance_id',
        'status',
        'note',
        'approved_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
