<?php

return [
    'name' => env('ADMIN_NAME', 'Admin'),
    'email' => env('ADMIN_EMAIL', 'admin@example.com'),
    'password' => env('ADMIN_PASSWORD'),
    'prefill_login' => env('ADMIN_PREFILL_LOGIN', env('APP_ENV') === 'local'),
];
