<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('cms:reset-password {email} {password}')]
#[Description('Reset password for a user. Usage: php artisan cms:reset-password admin@example.com newpassword')]
class ResetPassword extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User '{$email}' not found.");

            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);

        $this->info("Password for '{$email}' has been reset.");

        return self::SUCCESS;
    }
}
