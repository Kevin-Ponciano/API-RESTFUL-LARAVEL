<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Trait\LoginForTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDestroyTest extends TestCase
{
    use RefreshDatabase;
    use LoginForTest; # Trait para autenticação do usuário

    private string $url = '/api/v1/books';

    /**
     * Testa o sucesso ao destruir um livro não existente
     * e valida a mensagem de erro retornada
     */
    public function test_successfully_destroy_book()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->create();

        $response = $this->deleteJson("{$this->url}/{$book->id}");

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
        $this->loginForTest(); # autentica o usuário

        Book::factory(10)->create();
        $nonExistentBookId = Book::all()->last()->id * 32 + 1;

        $response = $this->deleteJson("{$this->url}/{$nonExistentBookId}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Book not found'
            ]);
    }

    /**
     * Testa a falha ao destruir um livro sem autenticação
     * e valida a mensagem de erro retornada
     */
    public function test_fail_destroy_book_without_authentication()
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("{$this->url}/{$book->id}");

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
