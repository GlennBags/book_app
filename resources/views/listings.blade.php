@php use App\Models\Listing; @endphp
@extends('layout')

@section('content')
    @include('partials._hero')
    @include('partials._search')

    <div class="lg:grid lg:grid-cols-2 gap-4 space-y-4 md:space-y-0 mx-4">

        @unless(empty($listings))

                <?php
                /** @var Listing $listing */ ?>
            @foreach($listings as $listing)
                @php($listing = (object)$listing)
                <x-listing-card :listing="$listing" />
            @endforeach

        @else
            <p>No Listings</p>
        @endunless

    </div>
@endsection
