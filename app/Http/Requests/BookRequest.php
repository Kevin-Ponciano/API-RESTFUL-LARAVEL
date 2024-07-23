<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'author' => 'required|string',
            'genre' => 'required|string',
            'publication_year' => 'required|integer|max:' . date('Y'),
            'pages' => 'nullable|integer',
            'publisher' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title is required.',
            'title.string' => 'The title must be a string.',
            'description.string' => 'The description must be a string.',
            'author.required' => 'The author is required.',
            'author.string' => 'The author must be a string.',
            'genre.required' => 'The genre is required.',
            'genre.string' => 'The genre must be a string.',
            'publication_year.required' => 'The publication year is required.',
            'publication_year.integer' => 'The publication year must be an integer.',
            'publication_year.max' => 'The publication year must be less than or equal to the current year.',
            'pages.integer' => 'The pages must be an integer.',
            'publisher.string' => 'The publisher must be a string.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
