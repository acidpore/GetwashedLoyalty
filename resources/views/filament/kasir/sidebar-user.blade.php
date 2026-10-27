@php($user = auth()->user())
<div class="lt-user" x-bind:class="{ 'is-collapsed': ! $store.sidebar.isOpen }">
    <span class="lt-avatar">{{ \Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</span>
    <div class="lt-user-info" x-show="$store.sidebar.isOpen">
        <strong>{{ $user->name }}</strong>
        <span>{{ $user->isAdmin() ? 'Owner' : 'Kasir' }}</span>
    </div>
    <form method="POST" action="{{ filament()->getLogoutUrl() }}" x-show="$store.sidebar.isOpen">
        @csrf
        <button type="submit" class="lt-user-logout" title="Keluar" aria-label="Keluar">
            <x-filament::icon icon="heroicon-o-arrow-right-start-on-rectangle" />
        </button>
    </form>
</div>
