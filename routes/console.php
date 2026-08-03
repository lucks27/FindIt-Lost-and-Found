<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:reset-password {email} {password}', function (string $email, string $password) {
    $admin = Admin::where('email', $email)->first();

    if (!$admin) {
        $this->error("Admin not found for email: {$email}");

        return self::FAILURE;
    }

    $admin->update([
        'password' => Hash::make($password),
    ]);

    $this->info("Password reset successfully for {$email}");

    return self::SUCCESS;
})->purpose('Reset an admin password using a properly hashed value');
