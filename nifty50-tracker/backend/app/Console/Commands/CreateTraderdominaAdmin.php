<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateTraderdominaAdmin extends Command
{
    protected $signature = 'traderdomina:create-admin
        {--name=Yogesh : Admin display name}
        {--email= : Admin email}
        {--password= : Admin password (min 12 chars)}';

    protected $description = 'Create the primary admin for Yogesh Traderdomina (full access, no subscription expiry)';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Admin email');
        $password = $this->option('password') ?: $this->secret('Admin password (min 12, mixed case + number)');

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) $this->option('name'),
                'password' => Hash::make((string) $password),
                'is_admin' => true,
                'is_active' => true,
                'subscription_expires_at' => null,
            ]
        );

        $this->info("Admin ready: {$user->email} (id {$user->id})");

        return self::SUCCESS;
    }
}