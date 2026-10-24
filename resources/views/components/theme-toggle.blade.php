<button type="button" onclick="toggleTheme()" aria-label="Ganti tema terang atau gelap"
    {{ $attributes->class('size-10 inline-flex items-center justify-center rounded-full text-ink-2 hover:bg-subtle hover:text-ink transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent') }}>
    <span class="material-symbols-outlined dark:hidden">dark_mode</span>
    <span class="material-symbols-outlined hidden dark:inline">light_mode</span>
</button>
