<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FotoTas extends Model
{
    use HasFactory;

    protected $table = 'foto_tas';

    protected $fillable = [
        'tas_id',
        'foto'
    ];

    public function tas()
    {
        return $this->belongsTo(
            Tas::class,
            'tas_id'
        );
    }
}
