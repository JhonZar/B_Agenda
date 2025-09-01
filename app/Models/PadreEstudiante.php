<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class PadreEstudiante extends Model
{
    protected $table = 'padre_estudiante';

    protected $fillable = [
        'padre_id',
        'estudiante_id',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'padre_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }
}
