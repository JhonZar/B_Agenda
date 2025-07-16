<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Paralelo extends Model
{
    use HasFactory;

    protected $table = 'paralelos';

    protected $fillable = [
        'grade',
        'section',
        'teacher_id',
    ];

    // Constructor personalizado (opcional)
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // Aquí puedes inicializar valores por defecto si lo necesitas
    }

    // Relación con el profesor encargado
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function materias()
    {
        return $this->belongsToMany(
            Materia::class,
            'paralelo_curso_materia',  
            'paralelos_id',            
            'materias_id'              
        )->withTimestamps();
    }
    public function students()
    {
        return $this
            ->belongsToMany(User::class, 'paralelo_estudiante', 'paralelos_id', 'student_id')
            ->withTimestamps();
    }
}
