<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;



class Agenda extends Model
{
    //
    protected $fillable = [
        'paralelo_id',
        'grade',
        'title',
        'description',
        'scheduled_at',
    ];

    /**
     * Relación con Paralelo (opcional).
     */
    public function paralelo(): BelongsTo
    {
        return $this->belongsTo(Paralelo::class);
    }

    /**
     * Scope: sólo eventos globales (ni grado ni paralelo).
     */
    public function scopeGlobal(Builder $q): Builder
    {
        return $q->whereNull('paralelo_id')
            ->whereNull('grade');
    }

    /**
     * Scope: eventos por grado/curso.
     */
    public function scopeForGrade(Builder $q, string $grade): Builder
    {
        return $q->whereNull('paralelo_id')
            ->where('grade', $grade);
    }

    /**
     * Scope: eventos para un paralelo concreto.
     */
    public function scopeForParalelo(Builder $q, int $paraleloId): Builder
    {
        return $q->where('paralelo_id', $paraleloId);
    }
}
