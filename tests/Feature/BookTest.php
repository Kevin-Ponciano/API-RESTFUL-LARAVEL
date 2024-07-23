<?php

namespace Tests\Feature;

use Tests\TestCase;
class BookTest extends TestCase
{
    private string $url = 'api/v1/books';
    public function testBasic()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testIndex()
    {
        $response = $this->JSON('GET', $this->url);

        $response->assertStatus(200);
    }
}
