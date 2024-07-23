<?php

namespace App\Virtual\Models;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     title="Book",
 *     description="Book model",
 *     @OA\Xml(
 *         name="Book"
 *     )
 * )
 */
class Book
{
    /**
     * @OA\Property(
     *     title="id",
     *     description="ID",
     *     format="int64",
     *     example=1
     * )
     *
     * @var integer
     */
    private $id;

    /**
     * @OA\Property(
     *     title="title",
     *     description="Title of the book",
     *     example="The Great Gatsby"
     * )
     *
     * @var string
     */
    private $title;

    /**
     * @OA\Property(
     *     title="description",
     *     description="Description of the book",
     *     example="The story of the mysteriously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan."
     * )
     *
     * @var string
     */
    private $description;

    /**
     * @OA\Property(
     *     title="author",
     *     description="Author of the book",
     *     example="F. Scott Fitzgerald"
     * )
     *
     * @var string
     */
    private $author;

    /**
     * @OA\Property(
     *     title="genre",
     *     description="Genre of the book",
     *     example="Novel"
     * )
     *
     * @var string
     */
    private $genre;

    /**
     * @OA\Property(
     *     title="publication_year",
     *     description="Publication year of the book",
     *     example=1925
     * )
     *
     * @var integer
     */
    private $publication_year;

    /**
     * @OA\Property(
     *     title="pages",
     *     description="Number of pages in the book",
     *     example=218
     * )
     *
     * @var integer
     */
    private $pages;

    /**
     * @OA\Property(
     *     title="publisher",
     *     description="Publisher of the book",
     *     example="Charles Scribner's Sons"
     * )
     *
     * @var string
     */
    private $publisher;
}
