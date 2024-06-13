<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="icon" href="images/favicon.ico" />
        <link
                rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
                integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
                crossorigin="anonymous"
                referrerpolicy="no-referrer"
        />
        <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
        <script src="//unpkg.com/alpinejs" defer></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            laravel: "#ef3b2d",
                        },
                    },
                },
            };
        </script>

        @stack('scripts')

        <title>Books Listings</title>
    </head>
    <body id="content"  class="mb-48 bg-white text-gray-900">
        <nav class="flex justify-between items-center mb-4">
            <a href="/">
                <img class="w-24 logo" src="{{ asset('images/TruvyReading.png') }}" alt=""/>
            </a>
            <div>
                <span class="text-3xl">Book Search - Find a book by author</span>
            </div>
            <button id="dark-mode-toggle" class="bg-gray-800 text-white px-4 py-2 rounded">
                Dark Mode
            </button>
            <ul class="flex space-x-6 mr-6 text-lg">
                <li>
                    <a href="register.html" class="hover:text-laravel">
                        <i class="fa-solid fa-user-plus"></i> Register
                    </a>
                </li>
                <li>
                    <a href="login.html" class="hover:text-laravel">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>Login
                    </a>
                </li>
            </ul>
        </nav>

        {{-- VIEW OUTPUT --}}
        <main>
            @yield('content')
        </main>

        <footer class="fixed bottom-0 left-0 w-full flex items-center justify-start font-bold bg-laravel
                        text-white h-12 mt-24 opacity-90 md:justify-center">
            <p class="ml-2">Copyright &copy; 2024, All Rights reserved</p>
        </footer>
    </body>
</html>
