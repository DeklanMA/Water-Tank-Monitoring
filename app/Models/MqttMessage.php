<?php

// In MqttMessage.php model file
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqttMessage extends Model
{
    use HasFactory;
    protected $table = 'mqtt_messages';
    protected $fillable = ['topic', 'message'];

    // You can define additional properties or methods here as needed
}
