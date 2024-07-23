<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa o sucesso ao destruir um livro não existente
     * e valida a mensagem de erro retornada
     */
    public function test_successfully_destroy_book()
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Book deleted'
            ]);
    }

    /**
     * Testa a falha ao destruir um livro não existente
     * e valida a mensagem de erro retornada
     */
    public function test_fail_destroy_non_existent_book()
    {
        Book::factory(10)->create();
        $nonExistentBookId = Book::all()->last()->id * 32 + 1;

        $response = $this->deleteJson("/api/v1/books/{$nonExistentBookId}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Book not found'
            ]);
    }
}
