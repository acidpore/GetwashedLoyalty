<x-layout.layout title="Check-In Berhasil - Getwashed Loyalty">
    <div class="min-h-screen flex flex-col p-4 md:p-8">
        <main class="flex-grow flex items-center justify-center">
            <div class="w-full max-w-md space-y-6">
                @php
                    $showReward = $carwashReward || $motorwashReward || $coffeeshopReward;
                    // Ensure carwashThreshold, etc. are passed from controller. If not, default to 5 (fallback).
                    $carwashThreshold = $carwashThreshold ?? 5;
                    $motorwashThreshold = $motorwashThreshold ?? 5;
                    $coffeeshopThreshold = $coffeeshopThreshold ?? 5;

                    $loyaltyTypesArray = explode(',', request('loyalty_types', ''));
                @endphp

                @if($showReward)
                    <!-- Reward Card -->
                    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl p-8 sm:p-10 shadow-2xl text-center text-white">
                        <!-- BG Decoration -->
                        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 brightness-100"></div>
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white/20 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

                        <div class="relative z-10">
                            <div class="text-6xl mb-4 animate-bounce">🎉</div>
                            <div class="uppercase tracking-widest text-xs font-black text-green-100 mb-2">Selamat!</div>
                            <h1 class="text-3xl font-extrabold mb-1">{{ $name }}</h1>
                            <p class="text-lg font-medium text-green-50 mb-8">Kamu mendapatkan reward!</p>
                            
                            <div class="space-y-3 text-left">
                                @if($carwashReward)
                                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 flex items-center gap-3">
                                        <div class="size-10 bg-white text-green-600 rounded-lg flex items-center justify-center shadow-lg">
                                            <span class="material-symbols-outlined">directions_car</span>
                                        </div>
                                        <div>
                                            <p class="text-xs text-green-100 font-bold uppercase tracking-wider">Cuci Mobil</p>
                                            <p class="font-bold text-white">DISKON SPESIAL</p>
                                        </div>
                                    </div>
                                @endif
                                @if($motorwashReward)
                                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 flex items-center gap-3">
                                        <div class="size-10 bg-white text-green-600 rounded-lg flex items-center justify-center shadow-lg">
                                            <span class="material-symbols-outlined">two_wheeler</span>
                                        </div>
                                        <div>
                                            <p class="text-xs text-green-100 font-bold uppercase tracking-wider">Cuci Motor</p>
                                            <p class="font-bold text-white">DISKON SPESIAL</p>
                                        </div>
                                    </div>
                                @endif
                                @if($coffeeshopReward)
                                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 flex items-center gap-3">
                                        <div class="size-10 bg-white text-green-600 rounded-lg flex items-center justify-center shadow-lg">
                                            <span class="material-symbols-outlined">coffee</span>
                                        </div>
                                        <div>
                                            <p class="text-xs text-green-100 font-bold uppercase tracking-wider">Coffee Shop</p>
                                            <p class="font-bold text-white">GRATIS KOPI</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-8 pt-6 border-t border-white/20">
                                <p class="text-xs font-medium text-green-50 opacity-90 flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-sm">qr_code_scanner</span>
                                    Tunjukkan pesan WhatsApp ke kasir
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Success Check Card -->
                    <div class="bg-card-dark border border-white/5 rounded-3xl p-8 text-center shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary via-blue-400 to-purple-500"></div>
                        
                        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6 ring-1 ring-primary/30">
                            <span class="material-symbols-outlined text-primary text-4xl">check</span>
                        </div>
                        
                        <h1 class="text-2xl font-extrabold text-white mb-2">Check-In Berhasil!</h1>
                        <p class="text-slate-400 font-medium">Terima kasih, <span class="text-white">{{ $name }}</span></p>
                    </div>
                @endif

                <!-- Progress Cards -->
                <div class="space-y-4">
                    @if(in_array('carwash', $loyaltyTypesArray) || count($loyaltyTypesArray) === 0)
                        <div class="bg-card-dark border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-colors">
                            <div class="flex justify-between items-end mb-4">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cuci Mobil</div>
                                    <h3 class="font-bold text-white flex items-center gap-2">
                                        <span class="material-symbols-outlined text-blue-400">directions_car</span>
                                        Car Wash
                                    </h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-black text-blue-400">{{ $carwashPoints }}</span>
                                    <span class="text-sm font-bold text-slate-600">/ {{ $carwashThreshold }}</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-background-dark rounded-full h-2 mb-3 overflow-hidden border border-white/5">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-400 h-full rounded-full transition-all duration-1000" 
                                     style="width: {{ min(100, ($carwashPoints / $carwashThreshold) * 100) }}%"></div>
                            </div>
                            
                            @if(!$carwashReward)
                                <p class="text-xs text-slate-500 font-medium">
                                    Kurang <span class="text-slate-300">{{ max(0, $carwashThreshold - $carwashPoints) }} poin</span> lagi untuk reward
                                </p>
                            @endif
                        </div>
                    @endif

                    @if(in_array('motorwash', $loyaltyTypesArray))
                        <div class="bg-card-dark border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-colors">
                            <div class="flex justify-between items-end mb-4">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cuci Motor</div>
                                    <h3 class="font-bold text-white flex items-center gap-2">
                                        <span class="material-symbols-outlined text-orange-400">two_wheeler</span>
                                        Motor Wash
                                    </h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-black text-orange-400">{{ $motorwashPoints }}</span>
                                    <span class="text-sm font-bold text-slate-600">/ {{ $motorwashThreshold }}</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-background-dark rounded-full h-2 mb-3 overflow-hidden border border-white/5">
                                <div class="bg-gradient-to-r from-orange-500 to-orange-400 h-full rounded-full transition-all duration-1000" 
                                     style="width: {{ min(100, ($motorwashPoints / $motorwashThreshold) * 100) }}%"></div>
                            </div>
                            
                            @if(!$motorwashReward)
                                <p class="text-xs text-slate-500 font-medium">
                                    Kurang <span class="text-slate-300">{{ max(0, $motorwashThreshold - $motorwashPoints) }} poin</span> lagi untuk reward
                                </p>
                            @endif
                        </div>
                    @endif

                    @if(in_array('coffeeshop', $loyaltyTypesArray) || count($loyaltyTypesArray) === 0)
                        <div class="bg-card-dark border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-colors">
                            <div class="flex justify-between items-end mb-4">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Coffee Shop</div>
                                    <h3 class="font-bold text-white flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-400">coffee</span>
                                        Coffee Shop
                                    </h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-black text-emerald-400">{{ $coffeeshopPoints }}</span>
                                    <span class="text-sm font-bold text-slate-600">/ {{ $coffeeshopThreshold }}</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-background-dark rounded-full h-2 mb-3 overflow-hidden border border-white/5">
                                <div class="bg-gradient-to-r from-emerald-500 to-emerald-400 h-full rounded-full transition-all duration-1000" 
                                     style="width: {{ min(100, ($coffeeshopPoints / $coffeeshopThreshold) * 100) }}%"></div>
                            </div>
                            
                            @if(!$coffeeshopReward)
                                <p class="text-xs text-slate-500 font-medium">
                                    Kurang <span class="text-slate-300">{{ max(0, $coffeeshopThreshold - $coffeeshopPoints) }} poin</span> lagi untuk reward
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="pt-4">
                    <a href="{{ route('home') }}" class="block w-full bg-card-dark hover:bg-white/5 text-slate-400 hover:text-white font-bold py-4 rounded-xl text-center transition-all border border-white/5 hover:border-white/10 active:scale-[0.98]">
                        Selesai
                    </a>
                </div>
            </div>
        </main>
    </div>
</x-layout.layout>
