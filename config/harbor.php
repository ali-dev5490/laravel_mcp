<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Local stdio user
    |--------------------------------------------------------------------------
    |
    | Claude Desktop and Cursor start the server as an Artisan process. There
    | is no HTTP session, so the process acts as this workspace member. HTTP
    | requests never fall back to this user; they require a Sanctum token.
    |
    */

    'local_user' => env('HARBOR_LOCAL_USER', 'owner@harborboard.test'),

    /*
    |--------------------------------------------------------------------------
    | Demo accounts
    |--------------------------------------------------------------------------
    |
    | Used only by the database seeder. These are local fixtures, not secrets.
    |
    */

    'demo_password' => env('HARBOR_DEMO_PASSWORD', 'password'),

    'users' => [
        'owner' => 'owner@harborboard.test',
        'agent' => 'agent@harborboard.test',
        'viewer' => 'viewer@harborboard.test',
    ],

    'rate_limit_per_minute' => (int) env('HARBOR_MCP_RATE_LIMIT', 60),

];
