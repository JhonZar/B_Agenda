<?php
// app/Models/Paralelo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paralelo extends Model
{
    use HasFactory;

    protected $table = 'paralelos';

    protected $fillable = [
        'grade',
        'section',
        'teacher_id',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(
            Materia::class,
            'paralelo_curso_materia',
            // en tu tabla pivote el FK hacia paralelos está en 'paralelos_id'
            'paralelos_id',
            // y el FK hacia materias está en 'materias_id'
            'materias_id'
        )->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'paralelo_estudiante',
            'paralelos_id',
            'student_id'
        )->withTimestamps();
    }
}
