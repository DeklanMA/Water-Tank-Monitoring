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
        MQTT::publish('UTS1818ee', 'Hello, MQTT!');

        return "Pesan berhasil dipublikasikan ke topik 'UTS1818ee'.";
    }

    //subscribe
    public function subscribeToTopic()
    {


        // Mendapatkan data terbaru sebelumnya
        $latestData = MqttMessage::latest()->first();

        MQTT::subscribe(
            'UTS1818ee',
            function (string $topic, string $message) use ($latestData) {
                Log::info("Received QoS level 1 message on topic [$topic]: $message");

                try {
                    // Handle the received message only if it is newer than the latest data
                    $receivedData = (float) $message;

                    try {
                        $latestData = MqttMessage::latest()->firstOrFail();
                    } catch (\Exception $e) {
                        Log::warning("Error retrieving latest data: " . $e->getMessage());
                        $latestData = null;
                    }

                    if (!$latestData || $receivedData > $latestData->kedalaman) {
                        // Save the data to the database using create method
                        MqttMessage::create([
                            'kedalaman' => $receivedData,
                        ]);

                        // Update the latestData variable (optional, depends on your needs)
                        $latestData = MqttMessage::latest()->first();
                    } else {
                        echo "Received data is not newer than the latest data. Discarding...";
                    }

                    Log::info("Processing complete.");
                } catch (\Exception $e) {
                    echo "Error handling message: " . $e->getMessage();
                    // Atau log pesan kesalahan ke file log
                    Log::error("Error handling message: " . $e->getMessage());
                }
            },
            1
        );


        // Loop berfungsi untuk menerima pesan secara terus menerus
        // $mqtt->loop(true);

        return "Successfully subscribed to topic 'UTS1818ee' with callback function.";
    }
}
