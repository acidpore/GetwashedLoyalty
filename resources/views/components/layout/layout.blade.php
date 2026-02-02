<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Getwashed Loyalty' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('Logo Get Washed Updated.png') }}">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-dark": "#101922",
                        "card-dark": "#16222e",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    }
                },
            },
        }
    </script>

    <style>
        body {
            font-family: 'Manrope', sans-serif;
        }
        
        .glass-panel {
            background: rgba(22, 34, 46, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #101922; 
        }
        ::-webkit-scrollbar-thumb {
            background: #2a3441; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #374151; 
        }
    </style>
    @stack('scripts')
</head>
<body class="bg-background-dark text-slate-200 min-h-screen">
    {{ $slot }}
</body>
</html>
