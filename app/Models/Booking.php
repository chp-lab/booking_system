<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'one_email',
        'room_num',
        'meeting_start',
        'meeting_end',
        'agenda'
    ];

    public $timestamps = false;
}
