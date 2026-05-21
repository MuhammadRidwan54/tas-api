<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tas extends Model
{
    use HasFactory;

    protected $table = 'tas';

    protected $fillable = [
        'kode_tas',
        'nama_tas',
        'kategori'
    ];

    public function photos()
    {
        return $this->hasMany(
            FotoTas::class,
            'tas_id'
        );
    }
}