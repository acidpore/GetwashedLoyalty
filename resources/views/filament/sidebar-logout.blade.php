{{-- Classes mirror Filament's own sidebar item so they exist in its compiled CSS --}}
<form method="POST" action="{{ filament()->getLogoutUrl() }}" class="border-t border-gray-200 px-6 py-4 dark:border-white/10">
    @csrf
    <button
        type="submit"
        x-data="{ tooltip: false }"
        x-effect="tooltip = $store.sidebar.isOpen ? false : { content: 'Keluar', placement: 'right', theme: $store.theme }"
        x-tooltip.html="tooltip"
        class="fi-sidebar-item-button flex w-full items-center justify-center gap-x-3 rounded-lg px-2 py-2 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
    >
        <x-filament::icon icon="heroicon-o-arrow-left-on-rectangle" class="fi-sidebar-item-icon h-6 w-6 text-gray-400 dark:text-gray-500" />
        <span x-show="$store.sidebar.isOpen" class="fi-sidebar-item-label flex-1 truncate text-start text-sm font-medium text-gray-700 dark:text-gray-200">
            Keluar
        </span>
    </button>
</form>
