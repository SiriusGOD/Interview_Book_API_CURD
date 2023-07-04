<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Book;
use App\Models\BookImage;
use Illuminate\Support\Facades\Log;

class BookService
{
    public function getList($page, $size, $uuid = null)
    {
        if(!empty($uuid)){
            $books = Book::where('uuid', '=', $uuid)->selectRaw('uuid, title, author, date(publication_date), category, price, quantity');
        }else{
            $books = Book::selectRaw('uuid, title, author, date(publication_date), category, price, quantity');
            if(!is_null($page) && !is_null($size)){
                $books = $books->offset(($page-1) * $size)->limit($size);
            }
        }
        $books = $books->get();
        foreach ($books as $key => $book) {
            $images = BookImage::where('book_uuid', '=', $book->uuid)->selectRaw('name, url as path')->get();
            $books[$key]->images = $images;
        }
        return $books;
    }

    public function getListFromId($uuid)
    {
        $books = Book::where('uuid', '=', $uuid)->selectRaw('uuid, title, author, date(publication_date), category, price, quantity')->get();
        if(count($books) > 0){
            foreach ($books as $key => $book) {
                $images = BookImage::where('book_uuid', '=', $book->uuid)->selectRaw('name, url as path')->get();
                $books[$key]->images = $images;
            }
            return $books;
        }else{
            return false;
        }  
    }

    public function createBook(array $data)
    {
        $book = new Book;
        $book->user_id = $data['user_id'];
        $book->title = $data['title'];
        $book->author = $data['author'];
        $book->publication_date = $data['publication_date'];
        $book->category = $data['category'];
        $book->price = $data['price'];
        $book->quantity = $data['quantity'];
        $book->save();
        if($book){
            $this->storeBookImage($data['images'], $book->uuid);
            $this->apiLog('api', 'info', '建立書籍',  [
                'user_id' => $data['user_id'],
                'data' => $data
            ]);
        }

        return $book;
    }

    public function storeBookImage(array $images, $book_uuid)
    {
        try {
            if(count($images) > 0){
                foreach ($images as $key => $image) {
                    $book_image = new BookImage;
                    $book_image->book_uuid = $book_uuid;
                    $book_image->name = $image['name'];
                    $book_image->url = $image['path'];
                    $book_image->save();
                }
            }
            return true;
        } catch (\Throwable $th) {
            //throw $th;
            return false;
        }
    }

    public function getCount()
    {
        $count = Book::all()->count();
        return $count;
    }

    public function getPerPage($size)
    {
        $count = $this->getCount();
        $per_page = ceil($count / $size);
        return $per_page;
    }

    public function update($uuid, array $data)
    {
        $book = Book::find($uuid);
        if(!$book){
            return 0;
        }else if($book->user_id == $data['user_id']){
            $book->title = $data['title'];
            $book->author = $data['author'];
            $book->publication_date = $data['publication_date'];
            $book->category = $data['category'];
            $book->price = $data['price'];
            $book->quantity = $data['quantity'];
            $book->save();
            if($book){
                $book->images()->delete();
                $this->storeBookImage($data['images'], $uuid);
                $this->apiLog('api', 'info', '更新書籍',  [
                    'user_id' => $data['user_id'],
                    'book_uuid' => $uuid,
                    'data' => $data
                ]);
            }
            return 1;
        }else{
            return 2;
        }
    }

    public function destroy($uuid, $user_id)
    {
        if (Book::where('uuid', $uuid)->exists()) 
        {
            $book = Book::findOrFail($uuid);
            if($book -> user_id == $user_id){
                $book->images()->delete();
                $book->delete();
                $this->apiLog('api', 'info', '刪除書籍',  [
                    'user_id' => $user_id,
                    'book_uuid' => $uuid
                ]);
                $flage = 1;
            }else{
                $flage = 2;
            }
        }else{
            $flage = 0;
        }
        return $flage;
    }

    public function apiLog($channel, $method, $message, $data)
    {
        switch ($method) {
            case 'info':
                Log::channel($channel)->info($message, $data);
                break;
            case 'warning':
                Log::channel($channel)->warning($message, $data);
                break;
            case 'error':
                Log::channel($channel)->error($message, $data);
                break;
            default:
                Log::channel($channel)->info($message, $data);
                break;
        }
    }
}