<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'author' => $this->faker->name,
            'genre' => $this->faker->word,
            'publication_year' => $this->faker->year,
            'pages' => $this->faker->numberBetween(100, 1000),
            'publisher' => $this->faker->company,
        ];
    }
}
