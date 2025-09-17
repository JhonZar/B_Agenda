<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRule extends Model
{
    protected $fillable = ['paralelo_id', 'entrada', 'tolerancia_min', 'activo'];
}
