<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\PasswordGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'eliteboxindia@gmail.com';
        $existing = User::where('email', $email)->first();

        if ($existing) {
            $this->command?->warn("Admin user '{$email}' already exists — skipping. Use 'php artisan vault:reset-admin' to reset.");
            return;
        }

        $generator = app(PasswordGeneratorService::class);
        $password = $generator->generate([
            'length' => 24,
            'uppercase' => true,
            'lowercase' => true,
            'numbers' => true,
            'symbols' => true,
            'exclude_similar' => true,
        ]);

        User::create([
            'name' => 'SecureVault Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $line = str_repeat('=', 72);
        $this->command?->newLine();
        $this->command?->line("<bg=yellow;fg=black> SECUREVAULT — ADMIN CREDENTIALS </>");
        $this->command?->line($line);
        $this->command?->line("  Email:    <fg=cyan>{$email}</>");
        $this->command?->line("  Password: <fg=green>{$password}</>");
        $this->command?->line($line);
        $this->command?->warn('  Save this password NOW. It will not be shown again.');
        $this->command?->warn('  On first login you will be required to enable 2FA.');
        $this->command?->newLine();
    }
}
