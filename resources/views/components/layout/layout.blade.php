<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? 'Getwashed Loyalty' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('Logo Get Washed Updated.png') }}">

    {{-- Runs before paint so the page never flashes the wrong theme --}}
    <script>
        (function () {
            var saved;
            try { saved = localStorage.getItem('theme'); } catch (e) {}
            // Shares the 'theme' key with the Filament panel, which can also store 'system'.
            var dark = saved === 'dark' || (saved !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();

        function toggleTheme() {
            var dark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
        }
    </script>

    <script src="https://cdn.tailwindcss.com/3.4.16?plugins=forms"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..24,400..600,0..1&display=swap" rel="stylesheet">

    <script>
        const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        canvas: token('canvas'),
                        surface: token('surface'),
                        subtle: token('subtle'),
                        line: token('line'),
                        ink: token('ink'),
                        'ink-2': token('ink-2'),
                        'ink-3': token('ink-3'),
                        accent: token('accent'),
                        'accent-solid': token('accent-solid'),
                        positive: token('positive'),
                        'positive-soft': token('positive-soft'),
                        caution: token('caution'),
                        'caution-soft': token('caution-soft'),
                        danger: token('danger'),
                        'danger-soft': token('danger-soft'),
                        carwash: '#2563eb',
                        motorwash: '#ea580c',
                        coffeeshop: '#16a34a',
                    },
                    fontFamily: {
                        sans: ['Manrope', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <style>
        :root {
            --canvas: 245 246 248;
            --surface: 255 255 255;
            --subtle: 240 242 245;
            --line: 226 229 234;
            --ink: 17 24 39;
            --ink-2: 75 85 99;
            --ink-3: 107 114 128;
            --accent: 37 99 235;
            --accent-solid: 37 99 235;
            --positive: 21 128 61;
            --positive-soft: 220 252 231;
            --caution: 180 83 9;
            --caution-soft: 254 243 199;
            --danger: 185 28 28;
            --danger-soft: 254 226 226;
        }

        .dark {
            --canvas: 11 13 16;
            --surface: 22 25 30;
            --subtle: 31 35 42;
            --line: 42 47 56;
            --ink: 243 244 246;
            --ink-2: 209 213 219;
            --ink-3: 156 163 175;
            --accent: 96 165 250;
            --accent-solid: 37 99 235;
            --positive: 74 222 128;
            --positive-soft: 20 46 31;
            --caution: 251 191 36;
            --caution-soft: 51 38 12;
            --danger: 248 113 113;
            --danger-soft: 55 22 22;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -webkit-tap-highlight-color: transparent;
        }

        .material-symbols-outlined {
            font-variation-settings: 'opsz' 24, 'wght' 500, 'FILL' 0;
            font-size: 20px;
            line-height: 1;
        }

        .icon-fill {
            font-variation-settings: 'opsz' 24, 'wght' 500, 'FILL' 1;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('scripts')
</head>
<body class="bg-canvas text-ink font-sans min-h-screen antialiased transition-colors">
    {{ $slot }}
</body>
</html>
