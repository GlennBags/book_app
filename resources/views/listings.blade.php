@extends('layout')

@section('content')
@include('partials._hero')

<div class="lg:grid lg:grid-cols-2 gap-4 space-y-4 md:space-y-0 mx-4">

@unless(empty($listings))

<?php /** @var \App\Models\Listing $listing */ ?>
@foreach($listings as $listing)
    <!-- Item 1 -->
    <div class="bg-gray-50 border border-gray-200 rounded p-6">
        <div class="flex items-start">
            <img class="hidden w-48 h-auto mr-6 md:block object-contain"
                 src="{{ $listing->imageLinks_thumbnail }}"
            />
            <div>
                <h3 class="text-2xl">
                    <span>{{$listing->title}}</span>
                </h3>
                <div class="text-xl font-bold mb-4">{{ $listing->authors }}</div>
                <div class="text-xl {{ $listing->subtitle ? 'font-bold' : '' }} mb-4">
                    <span><i>{{ $listing->subtitle ?: 'no subtitle' }}</i></span>
                </div>
                <div class="text-xl text-blue-700 mb-4">
                    <i class="fa-solid fa-diamond-turn-right"></i>
                    <a href="{{ $listing->previewLink }}" target="_blank">Preview Link</a>
                </div>
                <div class="text-xl font-normal mb-4">Description: {{ $listing->description }}</div>
                <div class="text-xl  mb-4">Published {{ $listing->publishedDate }}</div>
                <div class="text-lg mt-4">
                    <i class="fa-solid fa-book"></i> {{ $listing->categories }}
                </div>
            </div>
        </div>
    </div>
@endforeach

@else
    <p>No Listings</p>
@endunless

</div>
@endsection
