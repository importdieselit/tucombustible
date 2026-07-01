<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    "mailgun" => [
        "domain" => env("MAILGUN_DOMAIN"),
        "secret" => env("MAILGUN_SECRET"),
        "endpoint" => env("MAILGUN_ENDPOINT", "api.mailgun.net"),
    ],

    "postmark" => [
        "token" => env("POSTMARK_TOKEN"),
    ],

    "ses" => [
        "key" => env("AWS_ACCESS_KEY_ID"),
        "secret" => env("AWS_SECRET_ACCESS_KEY"),
        "region" => env("AWS_DEFAULT_REGION", "us-east-1"),
    ],

    "fcm" => [
        "project_id" => env("FIREBASE_PROJECT_ID"),
        "server_key" => env("FCM_SERVER_KEY"),
    ],
    'whatsapp' => [
        'url' => env('WHATSAPP_API_URL'),
        'key' => env('WHATSAPP_API_KEY'),
        'group_id' => env('WHATSAPP_GROUP_ID'),
        'operaciones_group_id' => env('WS_OPERACIONES_GROUP_ID'),
        'dev_group_id' => env('WHATSAPP_DEV_GROUP_ID'),
    ],
    's24' => [
        'apikey'   => env('S24_APIKEY'),
        'username' => env('S24_USERNAME'),
        'password' => env('S24_PASSWORD'),
        'url'      => env('S24_URL'),
        'token'    => env('S24_TOKEN')
    ],
    'reporte' => [
        'internal_token' => env('REPORTE_INTERNAL_TOKEN'),
    ],

];