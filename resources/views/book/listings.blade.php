@php use App\Models\Listing; @endphp
@extends('layout')

@section('content')
{{--    @include('partials._hero')--}}
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const content = document.getElementById('content');
            const inputs  = document.querySelectorAll('input');

            // Check if dark mode preference is set
            const darkModePreference = window.matchMedia('(prefers-color-scheme: dark)').matches;

            // Function to toggle dark mode
            function toggleDarkMode() {
                content.classList.toggle('dark');
                inputs.forEach(input => {
                    input.classList.toggle('dark');
                });
            }

            // Set initial dark mode state based on user preference
            if (darkModePreference) {
                toggleDarkMode();
            }

            // Event listener for dark mode toggle button
            darkModeToggle.addEventListener('click', function() {
                toggleDarkMode();
            });
        });
    </script>
@endpush
