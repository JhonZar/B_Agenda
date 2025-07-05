<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CodigoOtp extends Model
{
    use HasFactory;

    protected $table = 'codigos_otp';

    protected $fillable = [
        'padre_id',
        'codigo',
        'expira_en',
    ];
    protected $casts = [
        'expira_en' => 'datetime',
    ];
    public function padre()
    {
        return $this->belongsTo(User::class, 'padre_id');
    }
}
