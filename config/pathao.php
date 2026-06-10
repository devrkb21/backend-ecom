<?php

// config for devrkb21/PathaoLaravel
return [

    /*
    |--------------------------------------------------------------------------
    | Pathao DB Table Name
    |--------------------------------------------------------------------------
    |
    | The migration file uses this table name. If you provide the name
    | in .env it will use that name, otherwise it will use the default
    | name 'pathao-courier'. If you wish to change the name, remember
    | to update it before running the migration.
    |
    */
    'pathao_db_table_name' => env('PATHAO_DB_TABLE_NAME', 'pathao-courier'),

    /*
    |--------------------------------------------------------------------------
    | Pathao Client Id
    |--------------------------------------------------------------------------
    |
    | This is the Pathao Client Id. Please provide it in the .env file.
    | You can find it on the developers api -> Merchant API Credentials
    | section in Pathao Merchant (https://merchant.pathao.com/courier/developer-api).
    |
    */
    'pathao_client_id' => env('PATHAO_CLIENT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Pathao Client Secret
    |--------------------------------------------------------------------------
    |
    | This is the Pathao Client Secret. Please provide it in the .env file.
    | You can find it on the developers api -> Merchant API Credentials
    | section in Pathao Merchant (https://merchant.pathao.com/courier/developer-api).
    |
    */
    'pathao_client_secret' => env('PATHAO_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Pathao Secret Token
    |--------------------------------------------------------------------------
    |
    | After successfully setting up the token you will be provided a secret
    | token. Please keep it in the .env as PATHAO_SECRET_TOKEN.
    |
    */
    'pathao_secret_token' => env('PATHAO_SECRET_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Pathao Webhook Integration Secret
    |--------------------------------------------------------------------------
    |
    | Provide the exact UUID provided by Pathao Dashboard for webhook integration.
    | You can find it on the developer api page during webhook setup.
    |
    */
    'webhook_integration_secret' => env('PATHAO_WEBHOOK_INTEGRATION_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Pathao Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | Set this to true to connect to Pathao Sandbox/Staging API instead of
    | the production API.
    |
    */
    'sandbox' => env('PATHAO_SANDBOX', false),
];
