<?php

namespace App\Console\Commands;

use App\Models\MqttMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

class MqttSubscriberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe To MQTT topic';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $mqtt = MQTT::connection();
            $mqtt->subscribe('UTS1818ee', function (string $topic, string $message) {
                $this->info("Received message - Topic: $topic, Message: $message");
                $this->saveMessage($topic, (float)$message);
            });


            $mqtt->loop(true);
        } catch (\Exception $e) {
            Log::error("Error in MQTT subscription: " . $e->getMessage());
            $this->error("Error in MQTT subscription. Check the logs for details.");
        }

        return Command::SUCCESS;
    }
    private function saveMessage(string $topic, float $message)
    {
        try {
            MqttMessage::create([
                'topic' => $topic,
                'message' => $message,
            ]);

            $this->info("Message saved to the database - Topic: $topic, Message: $message");
        } catch (\Exception $e) {
            Log::error("Error saving message to the database: " . $e->getMessage());
            $this->error("Error saving message to the database. Check the logs for details.");
        }
    }
}
