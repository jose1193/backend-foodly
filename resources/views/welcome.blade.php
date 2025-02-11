<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Foodly - Under Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="antialiased">
    <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900">
        <div class="flex flex-col items-center justify-center min-h-screen p-4">
            <!-- Logo -->
            <div class="mb-8">
                <img src="{{ asset('img/logo.png') }}" alt="Foodly Logo" class="h-24">
            </div>

            <!-- Under Construction Message -->
            <h1 class="mb-4 text-4xl font-bold text-purple-800 dark:text-purple-400">
                Under Construction
            </h1>

            <p class="mb-8 text-lg text-gray-600 dark:text-gray-400 text-center">
                We're working hard to bring you something amazing. Stay tuned!
            </p>

            <!-- Footer Links -->
            <div class="flex gap-4 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('policy-privacy') }}" class="hover:text-purple-600 dark:hover:text-purple-400">
                    Privacy Policy
                </a>
                <a href="{{ route('terms-conditions') }}" class="hover:text-purple-600 dark:hover:text-purple-400">
                    Terms & Conditions
                </a>
                <a href="{{ route('policy-cookies') }}" class="hover:text-purple-600 dark:hover:text-purple-400">
                    Cookie Policy
                </a>
            </div>
        </div>
    </div>
</body>

</html>
