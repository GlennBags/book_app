@props(['listing'])

<x-card>
    <div class="flex items-start">
        <img class="hidden w-48 h-auto mr-6 md:block object-contain"
             @if($listing->imageLinks_thumbnail)
                 src="{{ $listing->imageLinks_thumbnail }}"
             @else
                 src="{{ asset('/images/No-Image-Placeholder.svg') }}"
                @endif
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
</x-card>
