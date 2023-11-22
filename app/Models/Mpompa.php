<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpompa extends Model
{
    use HasFactory;
    protected $table = 'tb_pompa';

    protected $fillable = [
        'nama',
        'status',
        'created_at',
        'updated_at',
    ];

    // Define relationship with Watertank model
    public function watertank()
    {
        return $this->hasOne(Watertank::class, 'id_pompa');
    }
}
