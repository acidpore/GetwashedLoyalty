<x-layout.layout title="Check-In - Getwashed Loyalty">
    <div class="min-h-screen flex flex-col p-4 md:p-8">
        
        <!-- Header / Back -->
        <header class="w-full max-w-4xl mx-auto mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                <span class="font-bold text-sm">Kembali</span>
            </a>
        </header>

        <main class="flex-grow flex items-center justify-center">
            <div class="w-full max-w-md bg-card-dark border border-white/5 rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden backdrop-blur-xl">
                <!-- Background Decoration -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

                <div class="relative z-10 text-center">
                    
                    <!-- Icon -->
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary via-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mb-8 shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-white text-4xl">check_circle</span>
                    </div>

                    <h1 class="text-2xl font-extrabold text-white mb-2">Check-In Sekarang</h1>
                    
                    <div class="mt-4 mb-8 flex flex-wrap gap-2 justify-center">
                        @foreach($loyaltyTypes as $type)
                            @if($type === 'carwash')
                                <span class="px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-lg text-xs font-bold uppercase tracking-wider">
                                    🚗 Cuci Mobil
                                </span>
                            @elseif($type === 'motorwash')
                                <span class="px-3 py-1 bg-orange-500/10 border border-orange-500/20 text-orange-400 rounded-lg text-xs font-bold uppercase tracking-wider">
                                    🏍️ Cuci Motor
                                </span>
                            @elseif($type === 'coffeeshop')
                                <span class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-xs font-bold uppercase tracking-wider">
                                    ☕ Coffee Shop
                                </span>
                            @endif
                        @endforeach
                    </div>

                    @if(count($loyaltyTypes) > 1)
                        <div class="mb-8 inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 border border-yellow-500/30 text-yellow-500 rounded-xl text-sm font-bold animate-pulse">
                            <span class="material-symbols-outlined text-lg">stars</span>
                            {{ count($loyaltyTypes) }}x Poin!
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl text-green-400 text-sm font-medium">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm font-medium">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('checkin.store') }}" class="space-y-5 text-left">
                        @csrf
                        @foreach($loyaltyTypes as $type)
                            <input type="hidden" name="loyalty_types[]" value="{{ $type }}">
                        @endforeach
                        @if($qrCode)
                            <input type="hidden" name="qr_code" value="{{ $qrCode->code }}">
                        @endif

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1" for="full-name">Nama Lengkap</label>
                            <div class="relative group">
                                <span class="absolute left-4 top-3.5 material-symbols-outlined text-slate-500 group-focus-within:text-primary transition-colors">badge</span>
                                <input 
                                    class="w-full pl-12 pr-4 py-3.5 bg-background-dark/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-white placeholder-slate-500 transition-all font-medium" 
                                    id="full-name" 
                                    name="name" 
                                    placeholder="Masukkan nama Anda" 
                                    type="text"
                                    required
                                >
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1" for="whatsapp-number">Nomor WhatsApp</label>
                            <div class="relative group">
                                <span class="absolute left-4 top-3.5 material-symbols-outlined text-slate-500 group-focus-within:text-primary transition-colors">chat</span>
                                <input 
                                    class="w-full pl-12 pr-4 py-3.5 bg-background-dark/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-white placeholder-slate-500 transition-all font-medium" 
                                    id="whatsapp-number" 
                                    name="phone" 
                                    placeholder="08123456789" 
                                    type="tel"
                                    required
                                >
                            </div>
                            <p class="text-[10px] text-slate-500 font-medium ml-1">Format: 08xxx atau 628xxx</p>
                        </div>

                        <button 
                            class="w-full group mt-4 flex items-center justify-center gap-2 bg-gradient-to-r from-primary to-blue-600 hover:to-blue-500 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-primary/25 hover:shadow-primary/40 transition-all transform hover:-translate-y-0.5 active:scale-[0.98]" 
                            type="submit"
                        >
                            <span class="material-symbols-outlined group-hover:animate-bounce">auto_awesome</span>
                            Dapatkan Poin Sekarang!
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-white/5">
                        <div class="flex items-start gap-3 text-left">
                            <div class="min-w-[20px] pt-0.5">
                                <span class="material-symbols-outlined text-primary text-sm">info</span>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">Poin akan langsung masuk. Notifikasi & link dashboard dikirim ke WhatsApp Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="py-6 text-center">
            <p class="text-[10px] text-slate-600 font-bold tracking-widest uppercase">Getwashed Loyalty © 2026</p>
        </footer>
    </div>
</x-layout.layout>
