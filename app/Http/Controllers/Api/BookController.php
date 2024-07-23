<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Exception;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class BookController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/books",
     *     tags={"Books"},
     *     summary="List all books",
     *     description="List all books",
     *     security={
     *          {"jwt": {}}
     *      },
     *     @OA\Response(
     *         response=200,
     *         description="Books found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Books found"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Book")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No books found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No books found"
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $books = Book::all();
        if ($books->isEmpty()) {
            return response()->json(['message' => 'No books found'], 404);
        }
        return response()->json([
            'message' => 'Books found',
            'data' => BookResource::collection($books), # Usado Resource para formatar a resposta e evitar repetição e evitar repetição
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/books",
     *     tags={"Books"},
     *     summary="Create a book",
     *     description="Create a book",
     *     security={
     *          {"jwt": {}}
     *      },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Book")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Book created"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Book"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Internal Error"
     *             )
     *         )
     *     )
     * )
     */
    public function store(BookRequest $request)
    {
        try {
            # Transação em conjunto com o try-catch para garantir a integridade dos dados
            DB::beginTransaction();
            # Passa os dados validados para o método create, caso os dados não sejam válidos, uma exceção será lançada
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


    /**
     * @OA\Get(
     *     path="/api/v1/books/{id}",
     *     tags={"Books"},
     *     summary="Show a book",
     *     description="Show a book",
     *     security={
     *          {"jwt": {}}
     *      },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Book found"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Book"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Book not found"
     *             )
     *         )
     *     )
     * )
     */
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


    /**
     * @OA\Put(
     *     path="/api/v1/books/{id}",
     *     tags={"Books"},
     *     summary="Update a book",
     *     description="Update a book",
     *     security={
     *          {"jwt": {}}
     *      },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Book")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Book updated"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Book"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Book not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Internal Error"
     *             )
     *         )
     *     )
     * )
     */
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


    /**
     * @OA\Delete(
     *     path="/api/v1/books/{id}",
     *     tags={"Books"},
     *     summary="Delete a book",
     *     description="Delete a book",
     *     security={
     *          {"jwt": {}}
     *      },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Book deleted"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Book not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Internal Error"
     *             )
     *         )
     *     )
     * )
     */
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
