<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Ensures the "admin" panel account exists and resets its password.
 * Safe to re-run — it upserts by username, so it doubles as a password
 * reset when the current one is lost.
 */
final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = 'admin';
        $password = 'password';
        $hash = Hash::make($password);

        $admin = DB::table('admins')->where('username', $username)->first();

        if ($admin === null) {
            DB::table('admins')->insert([
                'username' => $username,
                'password' => $hash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('admins')->where('id', $admin->id)->update([
                'password' => $hash,
                'updated_at' => now(),
            ]);
        }

        $this->command?->warn("Admin \"{$username}\" password (shown once): {$password}");
    }
}
