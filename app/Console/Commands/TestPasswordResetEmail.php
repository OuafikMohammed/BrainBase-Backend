<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

class TestPasswordResetEmail extends Command
{
    protected $signature = 'email:test-reset {email}';
    protected $description = 'Test password reset email by sending to a specified address';

    public function handle()
    {
        $email = $this->argument('email');
        $code = '123456'; // Test code

        try {
            Mail::to($email)->send(new PasswordResetMail($code));
            $this->info("Password reset test email sent successfully to {$email}");
            $this->info("Check storage/logs/laravel.log for the email content");
        } catch (\Exception $e) {
            $this->error("Failed to send test email: " . $e->getMessage());
        }
    }
}