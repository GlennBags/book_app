<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\GoogleBooksService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param string|null $author
     * @return Application|Factory|View
     * @throws GuzzleException
     */
    public function index(Request $request)
    {
        $listings = Listing::orderBy('publishedDate');

        if ($request->search) {
            GoogleBooksService::getByAuthor($request->search);
            $listings = $listings->where('authors', 'LIKE', "%{$request->search}%");
        }

        return view('book.listings', [
            'heading' => "Latest",
            'listings' => $listings->orderBy('publishedDate', 'desc')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Listing $listing
     * @return Response
     */
    public function show(Listing $listing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Listing $listing
     * @return Response
     */
    public function edit(Listing $listing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Listing $listing
     * @return Response
     */
    public function update(Request $request, Listing $listing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Listing $listing
     * @return Response
     */
    public function destroy(Listing $listing)
    {
        //
    }
}
