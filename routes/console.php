<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:ensure', function () {
    $email = env('ADMIN_EMAIL');
    $password = env('ADMIN_PASSWORD');

    if (! $email || ! $password) {
        $this->error('ADMIN_EMAIL and ADMIN_PASSWORD must be configured.');

        return 1;
    }

    User::updateOrCreate(
        ['email' => $email],
        [
            'name' => env('ADMIN_NAME', 'Admin'),
            'password' => Hash::make($password),
        ],
    );

    $this->info("Admin user ensured for {$email}.");

    return 0;
})->purpose('Create or update the local admin user from env variables');
