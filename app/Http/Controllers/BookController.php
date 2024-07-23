<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Exception;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::all();
        if($books->isEmpty()) {
            return response()->json(['message' => 'No books found'], 404);
        }
        return response()->json([
            'message' => 'Books found',
            'data' => BookResource::collection($books), # Usado Usado Resource para formatar a resposta e evitar repetição e evitar repetição
        ]);
    }

    public function store(BookRequest $request)
    {
        try {
            # Transação em conjunto com o try-catch para garantir a integridade dos dados
            DB::beginTransaction();
            $book = Book::create($request->validated());
            DB::commit();
            return response()->json([
                'message' => 'Book created',
                'data' => new BookResource($book), # Usado Resource para formatar a resposta e evitar repetição
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Internal Error'], 500);
        }
    }

    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        return response()->json([
            'message' => 'Book found',
            'data' => new BookResource($book), # Usado Resource para formatar a resposta e evitar repetição
        ]);
    }

    public function update(BookRequest $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        try {
            # Transação em conjunto com o try-catch para garantir a integridade dos dados# Transação em conjunto com o try-catch para garantir a integridade dos dados
            DB::beginTransaction();
            $book->update($request->validated());
            DB::commit();
            return response()->json([
                'message' => 'Book updated',
                'data' => new BookResource($book), # Usado Resource para formatar a resposta e evitar repetição
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Internal Error'], 500);

        }
    }

    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        try {
            # Transação em conjunto com o try-catch para garantir a integridade dos dados
            DB::beginTransaction();
            $book->delete();
            DB::commit();
            return response()->json([
                'message' => 'Book deleted',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Internal Error'], 500);
        }
    }
}
