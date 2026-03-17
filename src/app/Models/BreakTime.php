<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'in_at',
        'out_at',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
