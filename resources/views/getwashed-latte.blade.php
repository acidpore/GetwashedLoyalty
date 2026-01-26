<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Getwashed x Latte - Premium Coffee & Car Care</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        .clip-path-diagonal {
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0% 100%);
        }
        .clip-path-diagonal-reverse {
            clip-path: polygon(0 10%, 100% 0, 100% 100%, 0 100%);
        }
        
        .mobile-menu {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: rgba(16, 25, 34, 0.98);
            backdrop-filter: blur(10px);
            z-index: 40;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .mobile-menu.active {
            display: block;
        }
        
        .hamburger {
            display: flex;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
        }
        
        .hamburger span {
            display: block;
            width: 25px;
            height: 2px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(7px, 7px);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }
        
        @media (max-width: 768px) {
            .clip-path-diagonal,
            .clip-path-diagonal-reverse {
                clip-path: none;
            }
            
            section {
                margin-top: 0 !important;
            }
            
            .aspect-video {
                order: -1;
            }
        }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-[#0d141b] dark:text-slate-200">
    <div class="relative w-full overflow-x-hidden">
        <header class="fixed top-0 left-0 right-0 z-50 transition-colors duration-300 bg-transparent">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex items-center justify-between whitespace-nowrap border-b border-solid border-white/20 h-20">
                    <div class="flex items-center gap-2 md:gap-3 text-white">
                        <img src="{{ asset('Logo Get Washed Updated.png') }}" alt="Getwashed Logo" class="h-8 md:h-10 w-auto">
                        <span class="text-lg md:text-xl font-bold text-white/80">×</span>
                        <img src="{{ asset('Logo Latte 3.png') }}" alt="Latte Logo" class="h-8 md:h-10 w-auto">
                    </div>
                    <nav class="hidden md:flex items-center gap-9 text-white">
                        <a class="text-sm font-medium leading-normal hover:text-primary transition-colors" href="#coffee">Coffee</a>
                        <a class="text-sm font-medium leading-normal hover:text-primary transition-colors" href="#carwash">Carwash</a>
                        <a class="text-sm font-medium leading-normal hover:text-primary transition-colors" href="#motorwash">Motorwash</a>
                        <a class="text-sm font-medium leading-normal hover:text-primary transition-colors" href="#contact">Contact</a>
                    </nav>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ route('customer.dashboard') }}" class="hidden md:flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-opacity-90 transition-colors">
                                <span class="truncate">Dashboard</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden md:flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-opacity-90 transition-colors">
                                <span class="truncate">Login</span>
                            </a>
                        @endauth
                        <button class="md:hidden hamburger text-white" id="hamburger" onclick="toggleMobileMenu()">
                            <span></span>
                            <span></span>
                            <span></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mobile-menu" id="mobileMenu">
                <nav class="flex flex-col gap-4 text-white">
                    <a class="text-base font-medium leading-normal hover:text-primary transition-colors py-2" href="#coffee" onclick="toggleMobileMenu()">Coffee</a>
                    <a class="text-base font-medium leading-normal hover:text-primary transition-colors py-2" href="#carwash" onclick="toggleMobileMenu()">Carwash</a>
                    <a class="text-base font-medium leading-normal hover:text-primary transition-colors py-2" href="#motorwash" onclick="toggleMobileMenu()">Motorwash</a>
                    <a class="text-base font-medium leading-normal hover:text-primary transition-colors py-2" href="#contact" onclick="toggleMobileMenu()">Contact</a>
                    @auth
                        <a href="{{ route('customer.dashboard') }}" class="mt-4 flex items-center justify-center rounded-lg h-12 px-4 bg-primary text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-opacity-90 transition-colors">
                            <span class="truncate">Dashboard</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="mt-4 flex items-center justify-center rounded-lg h-12 px-4 bg-primary text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-opacity-90 transition-colors">
                            <span class="truncate">Login</span>
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="relative h-screen w-full flex items-center justify-center text-center text-white bg-cover bg-center px-4" style='background-image: linear-gradient(rgba(16, 25, 34, 0.6) 0%, rgba(16, 25, 34, 0.8) 100%), url("https://lh3.googleusercontent.com/aida-public/AB6AXuAfFN4EoCQFwDCt57D57wkXs0tzCyQo46MQVGeegeRjEmbssWVAGgZW6iXTUEcRAvhf6AqnP3VJkCvKNpk-Iav50HJdj-YtsZHDiYTb6xdNxE1SOeHyrSakSLv4kTypEliCGbrbhJqk5P0C9VzA_AkEL1xNCwrz-RBM2Nhf8JlptluCvC9fx84xCuzANajJU-ZKtQb42gNqTD9CVxAgikG0-1Rcaeu10db4LHLxIC3LqCYKcihcDuM_QBFCHJIaWngs72ENTy3TNvU");'>
                <div class="flex flex-col gap-4 items-center">
                    <h1 class="text-4xl sm:text-5xl md:text-7xl font-black leading-tight tracking-tighter">Getwashed x Latte</h1>
                    <p class="text-base sm:text-lg md:text-xl font-normal leading-normal">Your Premium Stop for Coffee & Care</p>
                    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2">
                        <span class="text-xs sm:text-sm font-medium">Scroll Down</span>
                        <span class="material-symbols-outlined animate-bounce text-lg sm:text-2xl">south</span>
                    </div>
                </div>
            </section>

            <section class="relative min-h-screen w-full flex items-center bg-[#4d3b32] clip-path-diagonal py-16 md:py-0" id="coffee">
                <div class="absolute inset-0 bg-cover bg-center opacity-20" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCqgFMLYn0OSYc-fWyD4QQQQbvocMKxPIrnflkhN_XBt7llf4zUK_Rh9MN6kzJffz1fgnRt4a88yfslTCRVXIyFi03XLsh0EE_aeW-d64Nr1JhehdnRf7PsV47Yhj2KHeXspfqGKwvxym-BO5IrDFVYj6BHqbvwA53yOD5P4NLiBrlFZ29YCksQhwiaw1tiifHJ-CKEutYv4bq62eKyPWJWBI9ZJyGwTbFTmot2QaHoX7mDBiaaMpsrUGiZgXylNTTuFv2eCHT-X2g")'></div>
                <div class="relative max-w-7xl mx-auto px-6 lg:px-8 grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                    <div class="flex flex-col gap-6 text-white">
                        <div class="flex flex-col gap-4">
                            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight">Latte</h2>
                            <p class="text-sm sm:text-base font-normal leading-relaxed max-w-lg">Experience our artisanal beans and cozy atmosphere, the perfect place to relax and recharge while your vehicle gets the care it deserves.</p>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 text-xs sm:text-sm">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">coffee</span>Specialty Coffee
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">bakery_dining</span>Fresh Pastries
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">wifi</span>Free Wi-Fi
                            </div>
                        </div>

                    </div>
                    <div class="aspect-video w-full rounded-xl bg-cover bg-center shadow-2xl" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDXDYxnhtqWYjpYsr7LUyYSrHk8cZ6k4d4XN4cRdeoxgbf3SK8YKArV39qwBgeTHthc2hSonfA0fJQXJPFoeoPvJZxjbzpCCL6RP6onv-LyiN2EYLL_TcnmhGAUeFXMC7SUJTepNIPguFBptCFebRZDZ15eeJuYeWKDDkeot5-ls9W45IbpWWwh2SEmSn5os_leF8T1-wlrWkV5pv7pwshtfciCgfVWc5LmDuqT6E_EisXjQykt8gcF16TzFS02u2gCR-dPqBFeE2g")'></div>
                </div>
            </section>

            <section class="relative min-h-screen w-full flex items-center bg-[#a8d5e5] clip-path-diagonal-reverse -mt-[10vh] py-16 md:py-0" id="carwash">
                <div class="absolute inset-0 bg-cover bg-center opacity-10" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBtPyeCIuFGq335ChZwziGHw_A3JvhKgCie_21lWdg94IZUlfiDlQMy0geHt8YA-ddNjcYf1znNvQuY7vwyctVTYGz_RSmiankTt2vxXgId_-ueVFs9gnMZ6v1jixzyf-E3NoLKc0yyw4kNKbKq_fW-CWDhL8MjqJBYW8bnngJm3qAe7lrBBWojBuAOSgmeq7Yv_7lNHS7s6FfybxRQDNEKEBXR70aNxDQolIsDVRqNAD511gNrPzbcHmOunzJEUuziZOZ54ZT8ErQ")'></div>
                <div class="relative max-w-7xl mx-auto px-6 lg:px-8 grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                    <div class="aspect-video w-full rounded-xl bg-cover bg-center shadow-2xl md:order-2" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBXusNiKDw1Y1GC2uch59jfui5D_RQZR4_DzETuqQlWQt7ZOupSkeLQ6Ls6fn3xpAxLPyr3ekjLqtTBpRT1X_LFptXrlyK_HABFlNvd1rUjeMLouAeWQ-9qkTc3it8R7XHtd5zgjHj1xdz-mkE5nJfPaKI-2DCGOvIgWu2aA_WSPJMcTpjBtnWisn5mBKP4F_6Fk_YJzKyRxCEeFNCCSL4N5gFc5GrU1tR9A_1JF-dUOZSy8siTaSMO_IhW-vNzOzsL0JvuEpuMUgY")'></div>
                    <div class="flex flex-col gap-6 text-[#0d141b] md:order-1">
                        <div class="flex flex-col gap-4">
                            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight">Getwashed - Carwash</h2>
                            <p class="text-sm sm:text-base font-normal leading-relaxed max-w-lg">Our state-of-the-art touchless technology and eco-friendly soaps ensure a brilliant, scratch-free shine for your car every time.</p>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 text-xs sm:text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">eco</span>Eco-Friendly
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">shield</span>Scratch-Free Wash
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">cleaning_services</span>Interior Detailing
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <section class="relative min-h-screen w-full flex items-center bg-[#101922] clip-path-diagonal-reverse -mt-[10vh] py-16 md:py-0" id="motorwash">
                <div class="absolute inset-0 bg-cover bg-center opacity-15" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuALxVa8y5Z4K0QqNY3eJYgQcwLUm9KC-Hm-9X59DyXJEG-xx-YkjqMkbxhS5V-9GRmnk8OxIf0cebp7nQ8qlTCLhnV5xGyv9SmJJ2ZjY__D6Y4AAVNjNNtq72g5YfiSqw0mHMULkZqz5cNWjNWdqgyGTBLnBJacFCXuZ0r8U9pwxTC4iDZG9jSLCUtJQ4vuWsEv2MEV-sPEf-fGDb8CNxC2lwCpzh88VLQ8H3Su_Y3RbwE9ICypEpMxeiSdU5nORkamKbdaa0RPlPs")'></div>
                <div class="relative max-w-7xl mx-auto px-6 lg:px-8 grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                    <div class="flex flex-col gap-6 text-white">
                        <div class="flex flex-col gap-4">
                            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight">Getwashed - Motorwash</h2>
                            <p class="text-sm sm:text-base font-normal leading-relaxed max-w-lg">Specialized, hands-on care for your motorcycle. From chrome polishing to chain cleaning, we treat your ride like it's our own.</p>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 text-xs sm:text-sm">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">wash</span>Hand Wash &amp; Dry
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">link</span>Chain Cleaning
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base sm:text-lg">auto_awesome</span>Detailing Services
                            </div>
                        </div>

                    </div>
                    <div class="aspect-video w-full rounded-xl bg-cover bg-center shadow-2xl" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBhK-iqbznmv7N32Am4VcZv2rDB_0dFRzDSKYNfBONmk5arvrkf2rdGU0LyMrH17v0rB8uYpXkP2EBcJ7Z1ZjzadDvO5CxLC2Szha7KqjzEqKZlN7R2YRSN8PbUjGGLIDRxOabTlP24bxj7ECgvgxXPTG0t6jWBZWXW5S9wEfKZ2f0It8rseuSUnF2z_f6_eDc_6FaKxAl8-6OmEG0lb_qJzzu_C8l5R5atBb5wCHiNPpEBPmgkcdvunPz2eUqiPWNN2AFxiTOET3o")'></div>
                </div>
            </section>

            <footer class="relative bg-background-dark text-slate-300 -mt-[10vh] pt-[15vh] pb-8" id="contact">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 grid md:grid-cols-3 gap-8">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <img src="{{ asset('Logo Get Washed Updated.png') }}" alt="Getwashed Logo" class="h-8 w-auto">
                <span class="text-lg font-bold text-white/70">×</span>
                <img src="{{ asset('Logo Latte 3.png') }}" alt="Latte Logo" class="h-8 w-auto">
            </div>
            <p class="text-sm">Your premium one-stop destination for exceptional coffee and meticulous vehicle care. Relax with us while we make your ride shine.</p>
            <p class="text-sm font-semibold text-white">PT Mitra Anak Cawang</p>
        </div>
        <div class="flex flex-col gap-4">
            <h3 class="font-bold text-white">Contact Us</h3>
            <a class="flex items-center gap-2 text-sm hover:text-primary transition-colors" href="tel:+6285883814652">
                <span class="material-symbols-outlined text-lg">call</span>+62 858-8381-4652
            </a>
            <a class="flex items-center gap-2 text-sm hover:text-primary transition-colors" href="mailto:admin@getwashedxlatte.com">
                <span class="material-symbols-outlined text-lg">mail</span>admin@getwashedxlatte.com
            </a>
            <p class="flex items-start gap-2 text-sm">
                <span class="material-symbols-outlined text-lg mt-0.5">location_on</span>
                <span>
                    Jl. Dewi Sartika No. 184,<br>
                    Kelurahan Cawang, Kecamatan Kramatjati,<br>
                    Kota Administrasi Jakarta Timur,<br>
                    DKI Jakarta 13630, Indonesia
                </span>
            </p>
        </div>
        <div class="flex flex-col gap-4">
            <h3 class="font-bold text-white">Follow Us</h3>
            <div class="flex items-center gap-4">
                <a class="hover:text-primary transition-colors" href="#">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v2.385z"></path>
                    </svg>
                </a>
                <a class="hover:text-primary transition-colors" target="_blank" href="https://www.instagram.com/getwashed_id">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.85s-.011 3.584-.069 4.85c-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07s-3.584-.012-4.85-.07c-3.252-.148-4.771-1.691-4.919-4.919-.058-1.265-.069-1.645-.069-4.85s.011-3.584.069-4.85c.149-3.225 1.664-4.771 4.919-4.919 1.266-.058 1.644-.07 4.85-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948s.014 3.667.072 4.947c.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072s3.667-.014 4.947-.072c4.358-.2 6.78-2.618 6.98-6.98.059-1.281.073-1.689.073-4.948s-.014-3.667-.072-4.947c-.2-4.358-2.618-6.78-6.98-6.98-1.281-.059-1.689-.073-4.948-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.162 6.162 6.162 6.162-2.759 6.162-6.162-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4s1.791-4 4-4 4 1.79 4 4-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.441 1.441 1.441 1.441-.645 1.441-1.441-.645-1.44-1.441-1.44z"></path>
                    </svg>
                </a>
                <a class="hover:text-primary transition-colors" href="#">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616v.064c0 2.298 1.634 4.212 3.793 4.649-.65.177-1.339.239-2.05.188.606 1.882 2.364 3.256 4.456 3.294-1.763 1.383-3.991 2.208-6.417 2.208-.417 0-.829-.024-1.234-.072 2.278 1.465 4.993 2.317 7.91 2.317 9.49 0 14.689-7.864 14.689-14.689 0-.223-.005-.446-.014-.668.998-.724 1.864-1.626 2.557-2.648z"></path>
                    </svg>
                </a>
            </div>
            <a href="https://getwashedxlatte.com" target="_blank" class="text-sm hover:text-primary transition-colors">getwashedxlatte.com</a>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-6 lg:px-8 mt-8 border-t border-slate-700 pt-6 text-center text-sm">
        <p>© 2025 Getwashed x Latte. All Rights Reserved.</p>
    </div>
</footer>
        </main>
    </div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const hamburger = document.getElementById('hamburger');
            menu.classList.toggle('active');
            hamburger.classList.toggle('active');
        }
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        let lastScrollY = window.scrollY;
        const header = document.querySelector('header');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.classList.add('bg-background-dark/90', 'backdrop-blur-md');
            } else {
                header.classList.remove('bg-background-dark/90', 'backdrop-blur-md');
            }
            
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu.classList.contains('active')) {
                toggleMobileMenu();
            }
        });
    </script>
</body>
</html>
