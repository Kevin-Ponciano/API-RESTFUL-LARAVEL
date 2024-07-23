<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Database\Factories\BookFactory;
use Hash;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'User Api',
            'email' => 'user.api@test.com',
            'password' => Hash::make('123'),
        ]);

        Book::factory(100)->create();
    }
}
