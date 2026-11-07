<?php

return [

    'default' => env('BROADCAST_CONNECTION', env('BROADCAST_DRIVER', 'log')),

    'connections' => [

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                // Internal address the Laravel app uses to publish events.
                'host' => env('REVERB_APP_HOST', '127.0.0.1'),
                'port' => env('REVERB_APP_PORT', 8080),
                'scheme' => env('REVERB_APP_SCHEME', 'http'),
                'useTLS' => env('REVERB_APP_SCHEME', 'http') === 'https',
                'path' => env('REVERB_PATH', ''),
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Browser client settings (rendered into meta tags, not the bundle)
    |--------------------------------------------------------------------------
    |
    | Vite assets are built inside Docker without a .env file, so the WebSocket
    | endpoint cannot come from import.meta.env. The Blade layout renders these
    | values instead, which also keeps them runtime-configurable.
    |
    */

    'client' => [
        'host' => env('REVERB_HOST', 'localhost'),
        'port' => (int) env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],

];
