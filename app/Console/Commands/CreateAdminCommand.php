<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds one admin account. There is no registration screen — the admin panel
 * has exactly the accounts this command creates.
 */
final class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {username} {--password=}';

    protected $description = 'Create an admin panel account';

    public function handle(): int
    {
        $username = (string) $this->argument('username');

        if (DB::table('admins')->where('username', $username)->exists()) {
            $this->error("Admin \"{$username}\" already exists.");

            return self::FAILURE;
        }

        // --password lets a caller pin it (e.g. a test fixture); interactive
        // use gets a fresh random one printed once, never stored anywhere but
        // the hash.
        $password = (string) ($this->option('password') ?: Str::password(16));

        DB::table('admins')->insert([
            'username' => $username,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("Admin \"{$username}\" created.");

        if (! $this->option('password')) {
            $this->warn("Password (shown once): {$password}");
        }

        return self::SUCCESS;
    }
}
