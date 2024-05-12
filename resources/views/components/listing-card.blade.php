@props(['listing'])

<x-card>
    <div class="flex items-start">
        <img class="hidden w-48 h-auto mr-6 md:block object-contain"
             <?php /** @var \App\Models\Listing $listing */ ?>
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
            <div class="text-xl text-blue-700 mb-4">
                <i class="fa-solid fa-diamond-turn-right"></i>
                <a href="{{ $listing->infoLink }}" target="_blank">Info Link</a>
            </div>
            <div class="text-xl font-normal mb-4">Description: {{ $listing->description }}</div>
            <div class="text-xl  mb-4">Published {{ $listing->publishedDate }}</div>
            <div class="text-lg mt-4 flex items-center">
                @if ($listing->isEbook)
                    <div class="relative">
                        <img class="hidden w-14 h-auto md:block object-contain"
                             src="{{ asset('/images/ebook.png') }}" alt="Ebook" />
                        <span class="opacity-0 hover:opacity-100 duration-300 absolute inset-0 z-10 flex justify-center
                                     items-center mt-9 text-dark-toggle">
                            Ebook
                        </span>
                    </div>
                @else
                    <i class="fa-solid fa-book mr-2"></i>
                @endif
                    {{ $listing->categories }}
            </div>
        </div>
    </div>
</x-card>
