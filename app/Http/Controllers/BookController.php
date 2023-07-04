<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\GetBookListRequest;
use App\Http\Requests\CreateBookRequest;
use App\Services\BookService;

class BookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['list', 'show']]);
    }

    public function list(GetBookListRequest $request, BookService $service)
    {
        $page = $request->page ?? 1;
        $size = $request->size ?? 10;
        $books = $service->getList($page, $size);
        $total = $service->getCount();
        $per_page = $service->getPerPage($size);
        return response()->json([
            'status' => 'success',
            'data' => $books,
            'tatal' => $total,
            "per_page" => $per_page,
            "current_page" => $page
        ]);
    }

    public function show($id, BookService $service)
    {
        $books = $service->getListFromId($id);
        if($books){
            return response()->json([
                'status' => 'success',
                'data' => $books,
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'The book does not exist',
        ], 404);
    }

    public function store(CreateBookRequest $request, BookService $service)
    {
        $book = $service -> createBook(
            array(
                'user_id' => auth()->user()->id,
                'title' => $request->title,
                'author' => $request->author,
                'publication_date' => $request->publicationDate,
                'category' => $request->category,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'images' => $request->images
            )
        );

        return response()->json([
            'status' => 'success',
            'message' => 'The book was created successfully'
        ], 201);
    }

    public function update($id, CreateBookRequest $request, BookService $service)
    {
        $book_status = $service->update($id, 
        array(
            'user_id' => auth()->user()->id,
            'title' => $request->title,
            'author' => $request->author,
            'publication_date' => $request->publicationDate,
            'category' => $request->category,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'images' => $request->images
        ));

        switch ($book_status) {
            case 0:
                return response()->json([
                    'status' => 'error',
                    'message' => 'The book does not exist',
                ], 404);
                break;
            case 1:
                return response()->json([
                    'status' => 'success',
                    'message' => 'The book was updated successfully',
                ]);
                break;
            case 2:
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid book data',
                ], 400);
                break;
        }
    }

    public function delete($id, BookService $service)
    {
        $book_status = $service -> destroy($id, auth()->user()->id);
        switch ($book_status) {
            case 0:
                return response()->json([
                    'status' => 'error',
                    'message' => 'The book does not exist',
                ], 404);
                break;
            case 1:
                return response()->json([
                    'status' => 'success',
                    'message' => 'The book was deleted successfully',
                ], 204);
                break;
            case 2:
                return response()->json([
                    'status' => 'error',
                    'message' => '權限不足',
                ], 404);
                break;
        }
    }
}
