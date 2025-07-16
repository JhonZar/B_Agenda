<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Materia extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
    ];

     public function paralelos()
    {
        return $this->belongsToMany(
            Paralelo::class,
            'paralelo_curso_materia',   // misma tabla pivote
            'materias_id',              // FK en la pivote apuntando a Materia
            'paralelos_id'              // FK en la pivote apuntando a Paralelo
        )->withTimestamps();
    }
}
