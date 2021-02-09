<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_num',
        'buiding',
    ];

    protected $primaryKey = 'one_id';
    public $timestamps = false;
    protected $keyType = 'string';
}
