<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Getwashed Loyalty' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        @media (max-width: 768px) {
            body {
                background-image: url('{{ asset("Background berwarna biru motif abstrack modern.jpg") }}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-attachment: fixed;
            }
            
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(2px);
                z-index: 0;
                pointer-events: none;
            }
            
            body > * {
                position: relative;
                z-index: 1;
            }
        }

        .page-loader {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .page-loader.active {
            display: flex;
        }

        .jimu-primary-loading:before,
        .jimu-primary-loading:after {
            position: absolute;
            top: 0;
            content: '';
        }

        .jimu-primary-loading:before {
            left: -19.992px;
        }

        .jimu-primary-loading:after {
            left: 19.992px;
            -webkit-animation-delay: 0.32s !important;
            animation-delay: 0.32s !important;
        }

        .jimu-primary-loading:before,
        .jimu-primary-loading:after,
        .jimu-primary-loading {
            background: #076fe5;
            -webkit-animation: loading-keys-app-loading 0.8s infinite ease-in-out;
            animation: loading-keys-app-loading 0.8s infinite ease-in-out;
            width: 13.6px;
            height: 32px;
        }

        .jimu-primary-loading {
            text-indent: -9999em;
            margin: auto;
            position: absolute;
            right: calc(50% - 6.8px);
            top: calc(50% - 16px);
            -webkit-animation-delay: 0.16s !important;
            animation-delay: 0.16s !important;
        }

        @-webkit-keyframes loading-keys-app-loading {
            0%,
            80%,
            100% {
                opacity: .75;
                box-shadow: 0 0 #076fe5;
                height: 32px;
            }

            40% {
                opacity: 1;
                box-shadow: 0 -8px #076fe5;
                height: 40px;
            }
        }

        @keyframes loading-keys-app-loading {
            0%,
            80%,
            100% {
                opacity: .75;
                box-shadow: 0 0 #076fe5;
                height: 32px;
            }

            40% {
                opacity: 1;
                box-shadow: 0 -8px #076fe5;
                height: 40px;
            }
        }
    </style>
    @stack('scripts')
</head>
<body class="{{ $bgClass ?? 'bg-gradient-to-br from-blue-50 to-indigo-100' }} min-h-screen">
    <div class="page-loader" id="pageLoader">
        <div class="jimu-primary-loading"></div>
    </div>

    {{ $slot }}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('pageLoader');
            
            document.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    
                    if (href && 
                        !href.startsWith('#') && 
                        !href.startsWith('javascript:') &&
                        !this.hasAttribute('target') &&
                        !this.hasAttribute('download') &&
                        this.hostname === window.location.hostname) {
                        loader.classList.add('active');
                    }
                });
            });

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const method = (this.getAttribute('method') || 'get').toLowerCase();
                    if (method === 'post' || method === 'get') {
                        loader.classList.add('active');
                    }
                });
            });

            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    loader.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
