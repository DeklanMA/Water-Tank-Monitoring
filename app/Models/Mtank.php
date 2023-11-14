<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mtank extends Model
{
    use HasFactory;
    //model tabel
    protected $table = 'sensor';
    protected $primaryKey = 'id';
    protected $filllable = ['suhu,kelembaban,lux'];


    



}
