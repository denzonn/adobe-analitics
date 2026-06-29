<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed akun admin default.
     *
     * Username login mengikuti kolom email (Laravel default). Untuk
     * menghindari konflik, kita pakai email kanonik + address book-friendly.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'denson753@admin.com'],
            [
                'name'              => 'Denson Patibang',
                'password'          => Hash::make('admin'),
                'email_verified_at' => now(),
            ]
        );
    }
}
