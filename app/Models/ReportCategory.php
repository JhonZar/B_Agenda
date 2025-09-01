<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCategory extends Model
{
    protected $table = 'categorias_reportes';

    protected $fillable = [
        'name',
        'description',
    ];
}
