<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Getwashed Loyalty' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('scripts')
</head>
<body class="{{ $bgClass ?? 'bg-gradient-to-br from-blue-50 to-indigo-100' }} min-h-screen">
    {{ $slot }}
</body>
</html>
