@extends('layout')

@section('content')
<h1>{{ $heading }}</h1>
@unless(empty($listings))

@foreach($listings as $listing)
    <h2>
        <a href="/listings/{{$listing['id']}}">
            {{ $listing['title'] }}
        </a>
    </h2>
    <p>{{ $listing['description'] }}</p>
@endforeach

@else
    <p>No Listings</p>
@endunless

@endsection
