<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    /**
     * Akun owner pertama untuk login awal.
     * Owner lain ditambahkan lewat menu Pengaturan > Kelola User.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'Hosthagos@gmail.com'],
            [
                'name'     => 'Fikri Haekal',
                'password' => Hash::make('Tuyulads123'),
                'role'     => 'owner',
            ]
        );
    }
}
