<?php

namespace App\Http\Controllers;

use App\Models\MmqttKedalaman;
use App\Models\MqttMessage;
use App\Models\Watertank;
use Illuminate\Http\Request;
use PhpMqtt\Client\Facades\MQTT;
use Illuminate\Support\Facades\Log;


class MqttController extends Controller
{
    //publis
    public function publishMessage()
    {
        // Publish pesan ke topik 'your-topic'
        MQTT::publish('UTS1818ee', 90);

        return "Pesan berhasil dipublikasikan ke topik 'UTS1818ee'.";
    }


}
