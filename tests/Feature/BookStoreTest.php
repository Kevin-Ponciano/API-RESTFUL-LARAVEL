<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Trait\LoginForTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;
    use LoginForTest; # Trait para autenticação do usuário

    private string $url = '/api/v1/books';

    /**
     * Testa o sucesso ao armazenar um livro
     * e valida a estrutura do JSON retornado
     */
    public function test_successfully_store_book()
    {
        $this->loginForTest(); # autentica o usuário

        # Instancia do book, mas sem persistir no banco
        $book = Book::factory()->make()->toArray();

        $response = $this->postJson($this->url, $book);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data'
            ])
            ->assertJson([
                'message' => 'Book created',
                'data' => $book
            ]);
    }

    public function test_fail_to_store_book_title_is_null()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->make()->toArray();
        $book['title'] = "";
        $response = $this->postJson($this->url, $book);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('title')
            ->assertJsonFragment([
                'title' => ['The title is required.']
            ]);
    }

    /**
     *  Testa a falha ao armazenar um livro sem autor
     * e valida a mensagem de erro retornada
     */
    public function test_fail_to_store_book_without_author()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->make()->toArray();
        unset($book['author']);
        $response = $this->postJson($this->url, $book);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('author')
            ->assertJsonFragment([
                'author' => ['The author is required.']
            ]);
    }

    /**
     * Testa a falha ao armazenar um livro com ano de publicação no futuro
     * e valida a mensagem de erro retornada
     */
    public function test_fail_to_store_book_published_in_the_future()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->make(['publication_year' => '2025'])->toArray();
        $response = $this->postJson($this->url, $book);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('publication_year')
            ->assertJsonFragment([
                'publication_year' => ['The publication year must be less than or equal to the current year.']
            ]);
    }

    /**
     * Testa a falha ao armazenar um livro sem autenticação
     * e valida a mensagem de erro retornada
     */

    public function test_fail_to_store_book_without_authentication()
    {
        $book = Book::factory()->make()->toArray();
        $response = $this->postJson($this->url, $book);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
