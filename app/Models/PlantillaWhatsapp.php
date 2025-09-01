<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaWhatsapp extends Model
{
    protected $table = 'plantillas_whatsapp';

    protected $fillable = [
        'name',
        'content',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
