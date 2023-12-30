<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Watertank extends Model
{

    use HasFactory;
    protected $table = 'tb_watertank';


    protected $primaryKey = 'id_watertank';

    protected $fillable = [
        'kedalaman',
        'suhu',
        'id_pompa',
        'created_at',
        'updated_at',
    ];

    // Define relationship with Pompa model
    public function pompa()
    {
        return $this->belongsTo(Mpompa::class, 'id_pompa');
    }
}
