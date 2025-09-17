<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaWhatsapp extends Model
{
    protected $table = 'plantillas_whatsapp';

    protected $fillable = [
        'name',
        'subject',
        'message',
        'content',
        'category',
        'status',
        'target_audience',
        'variables',
        'priority',
        'has_attachment',
        'is_schedulable',
        'usage_count',
        'last_used',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected $casts = [
        'variables' => 'array',
        'has_attachment' => 'boolean',
        'is_schedulable' => 'boolean',
        'last_used' => 'datetime',
        'usage_count' => 'integer',
    ];
}
