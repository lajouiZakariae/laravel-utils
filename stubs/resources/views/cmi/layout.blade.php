<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script>
        (function () {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-background font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-16">

        {{-- Page card --}}
        <div
            class="w-full max-w-md bg-card text-card-foreground rounded-2xl shadow-xl border border-border overflow-hidden">
            @yield('content')
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-sm text-muted-foreground">
            &copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}. Tous droits réservés.
        </p>
    </div>
</body>

</html>