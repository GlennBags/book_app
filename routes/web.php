<?php

use App\Http\Controllers\ListingController;
use App\Models\Listing;
use App\Services\GoogleBooksService;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// all listings
Route::get('/', function () {
    return view('book.listings', [
        'heading' => "Latest",
        'listings' => Listing::all(),
    ]);
});

// all listings
Route::get('/listings/{id}', function ($id) {
    return view('book.listing', [
        'listing' => Listing::find($id),
    ]);
});

Route::get('/books', [ListingController::class, 'index']);

Route::get('/books/{author}', [ListingController::class, 'index']);
