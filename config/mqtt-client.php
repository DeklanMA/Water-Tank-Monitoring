<?php

declare(strict_types=1);

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Repositories\MemoryRepository;

return [

    /*
    |--------------------------------------------------------------------------
    | Default MQTT Connection
    |--------------------------------------------------------------------------
    |
    | This setting defines the default MQTT connection returned when requesting
    | a connection without name from the facade.
    |
    */

    'default_connection' => 'default',

    /*
    |--------------------------------------------------------------------------
    | MQTT Connections
    |--------------------------------------------------------------------------
    |
    | These are the MQTT connections used by the application. You can also open
    | an individual connection from the application itself, but all connections
    | defined here can be accessed via name conveniently.
    |
    */

    'connections' => [

        'default' => [

            // The host and port to which the client shall connect.
            'host' => env('MQTT_HOST', "broker.hivemq.com"),
            'port' => env('MQTT_PORT', 1883),

            // The MQTT protocol version used for the connection.
            'protocol' => MqttClient::MQTT_3_1,

            // A specific client id to be used for the connection. If omitted,
            // a random client id will be generated for each new connection.
            'client_id' => env('MQTT_CLIENT_ID'),

            // Whether a clean session shall be used and requested by the client.
            // A clean session will let the broker forget about subscriptions and
            // queued messages when the client disconnects. Also, if available,
            // data of a previous session will be deleted when connecting.
            'use_clean_session' => env('MQTT_CLEAN_SESSION', true),

            // Whether logging shall be enabled. The default logger will be used
            // with the log level as configured.
            'enable_logging' => env('MQTT_ENABLE_LOGGING', true),

            // Which logging channel to use for logs produced by the MQTT client.
            // If left empty, the default log channel or stack is being used.
            'log_channel' => env('MQTT_LOG_CHANNEL', null),

            // Defines which repository implementation shall be used. Currently,
            // only a MemoryRepository is supported.
            'repository' => MemoryRepository::class,

            // Additional settings used for the connection to the broker.
            // All of these settings are entirely optional and have sane defaults.
            'connections' => [

                'default' => [
                    'host' => 'broker.hivemq.com',
                    'port' => 1883, // Gunakan port ini jika tidak menggunakan enkripsi (TLS)
                    'protocol' => MqttClient::MQTT_3_1,
                    'client_id' => env('MQTT_CLIENT_ID'),
                    'use_clean_session' => env('MQTT_CLEAN_SESSION', true),
                    'enable_logging' => env('MQTT_ENABLE_LOGGING', true),
                    'log_channel' => env('MQTT_LOG_CHANNEL', null),
                    'repository' => MemoryRepository::class,
                    'connection_settings' => [
                        'tls' => [
                            'enabled' => true, // Ganti menjadi true jika menggunakan enkripsi (TLS)
                            'allow_self_signed_certificate' => false,
                            'verify_peer' => true,
                            'verify_peer_name' => true,
                            'ca_file' => null,
                            'ca_path' => null,
                            'client_certificate_file' => null,
                            'client_certificate_key_file' => null,
                            'client_certificate_key_passphrase' => null,
                        ],
                        'auth' => [
                            'username' => env('MQTT_AUTH_USERNAME'),
                            'password' => env('MQTT_AUTH_PASSWORD'),
                        ],
                        'last_will' => [
                            'topic' => env('MQTT_LAST_WILL_TOPIC'),
                            'message' => env('MQTT_LAST_WILL_MESSAGE'),
                            'quality_of_service' => env('MQTT_LAST_WILL_QUALITY_OF_SERVICE', 0),
                            'retain' => env('MQTT_LAST_WILL_RETAIN', false),
                        ],
                        'socket_timeout' => env('MQTT_SOCKET_TIMEOUT', 5),
                        'resend_timeout' => env('MQTT_RESEND_TIMEOUT', 10),
                        'keep_alive_interval' => env('MQTT_KEEP_ALIVE_INTERVAL', 10),
                        'auto_reconnect' => [
                            'enabled' => env('MQTT_AUTO_RECONNECT_ENABLED', false),
                            'max_reconnect_attempts' => env('MQTT_AUTO_RECONNECT_MAX_RECONNECT_ATTEMPTS', 3),
                            'delay_between_reconnect_attempts' => env('MQTT_AUTO_RECONNECT_DELAY_BETWEEN_RECONNECT_ATTEMPTS', 0),
                        ],
                    ],
                ],

            ],


        ],

    ],

];
