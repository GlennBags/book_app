<h1>{{ $heading }}</h1>
@if (empty($listings))
    <p>No Listings</p>
@endif
@foreach($listings as $listing)
    <h2>{{ $listing['title'] }}</h2>
    <p>{{ $listing['description'] }}</p>
@endforeach
