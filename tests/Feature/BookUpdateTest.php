<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Trait\LoginForTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;
    use LoginForTest; # Trait para autenticação do usuário

    private string $url = '/api/v1/books';

    /**
     * Testa o sucesso ao atualizar um livro
     * e valida a estrutura do JSON retornado
     */
    public function test_successfully_update_book()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->create();
        $bookUpdate = [
            'title' => $book->title,
            'description' => 'New Description',
            'author' => 'New Author',
            'genre' => 'New Genre',
            'publication_year' => $book->publication_year,
            'pages' => $book->pages,
            'publisher' => $book->publisher,
        ];

        $response = $this->putJson("$this->url/{$book->id}", $bookUpdate);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data'
            ])
            ->assertJson([
                'message' => 'Book updated',
                'data' => $bookUpdate
            ]);
    }

    /**
     * Testa a falha ao atualizar um livro não existente
     * e valida a mensagem de erro retornada
     */
    public function test_fail_update_book_non_existent_book()
    {
        $this->loginForTest(); # autentica o usuário

        Book::factory(10)->create();
        $nonExistentBookId = Book::all()->last()->id * 32 + 1;
        $book = Book::factory()->make()->toArray();

        $response = $this->putJson("$this->url/{$nonExistentBookId}", $book);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Book not found'
            ]);
    }

    /**
     * Testa a falha ao atualizar um livro com título nulo
     * e valida a mensagem de erro retornada
     */
    public function test_fail_update_book_title_is_null()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->create();
        $bookUpdate = [
            'title' => '',
            'description' => 'New Description',
            'author' => 'New Author',
            'genre' => 'New Genre',
            'publication_year' => $book->publication_year,
            'pages' => $book->pages,
            'publisher' => $book->publisher,
        ];

        $response = $this->putJson("$this->url/{$book->id}", $bookUpdate);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('title')
            ->assertJsonFragment([
                'title' => ['The title is required.']
            ]);
    }


    /**
     * Testa a falha ao atualizar um livro sem autor
     * e valida a mensagem de erro retornada
     */
    public function test_fail_update_book_without_author()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->create();
        $bookUpdate = [
            'title' => $book->title,
            'description' => 'New Description',
            'genre' => 'New Genre',
            'publication_year' => $book->publication_year,
            'pages' => $book->pages,
            'publisher' => $book->publisher,
        ];

        $response = $this->putJson("$this->url/{$book->id}", $bookUpdate);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('author')
            ->assertJsonFragment([
                'author' => ['The author is required.']
            ]);
    }

    /**
     * Testa a falha ao atualizar um livro com ano de publicação no futuro
     * e valida a mensagem de erro retornada
     */
    public function test_fail_update_book_published_in_the_future()
    {
        $this->loginForTest(); # autentica o usuário

        $book = Book::factory()->create();
        $bookUpdate = [
            'title' => $book->title,
            'description' => 'New Description',
            'author' => 'New Author',
            'genre' => 'New Genre',
            'publication_year' => '2025',
            'pages' => $book->pages,
            'publisher' => $book->publisher,
        ];

        $response = $this->putJson("$this->url/{$book->id}", $bookUpdate);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('publication_year')
            ->assertJsonFragment([
                'publication_year' => ['The publication year must be less than or equal to the current year.']
            ]);
    }

    /**
     * Testa a falha ao atualizar um livro sem autenticação
     * e valida a mensagem de erro retornada
     */

    public function test_fail_update_book_without_authentication()
    {
        $book = Book::factory()->create();
        $bookUpdate = [
            'title' => $book->title,
            'description' => 'New Description',
            'author' => 'New Author',
            'genre' => 'New Genre',
            'publication_year' => $book->publication_year,
            'pages' => $book->pages,
            'publisher' => $book->publisher,
        ];

        $response = $this->putJson("$this->url/{$book->id}", $bookUpdate);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
