<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Trait\LoginForTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;
    use LoginForTest; # Trait para autenticação do usuário

    private string $url = '/api/v1/books';

    /**
     * Testa o sucesso ao exibir um livro
     * e valida a estrutura do JSON retornado
     */
    public function test_successfully_show_book()
    {
        $this->loginForTest(); # autentica o usuário

        $books = Book::factory(10)->create();
        $book = $books->random();

        $response = $this->getJson("{$this->url}/$book->id");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data'
            ])
            ->assertJson([
                'message' => 'Book found',
                'data' => Arr::except($book->toArray(), ['created_at', 'updated_at'])
                # A linha acima garante que os campos created_at e updated_at não sejam comparados,
                # pois não são retornados na resposta da API, mas são retornados no modelo do Eloquent
            ]);
    }

    /**
     * Testa a falha ao exibir um livro inexistente
     * e valida a mensagem de erro retornada
     */
    public function test_fail_to_show_non_existent_book()
    {
        $this->loginForTest(); # autentica o usuário

        $books = Book::factory(10)->create();
        $book = $books->last();
        $nonExistentBookId = $book->id * 32 + 1; # para garantir que o ID não exista

        $response = $this->getJson("{$this->url}/$nonExistentBookId");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Book not found'
            ]);
    }

    /**
     * Testa a falha ao exibir um livro sem autenticação
     * e valida a mensagem de erro retornada
     */
    public function test_fail_to_show_book_without_authentication()
    {
        $books = Book::factory(10)->create();
        $book = $books->random();

        $response = $this->getJson("{$this->url}/$book->id");

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
