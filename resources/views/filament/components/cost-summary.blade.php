<div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
    <div class="flex items-baseline justify-between">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Recipients</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($count) }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-600 dark:text-gray-400">Estimated Cost</p>
            <p class="text-2xl font-bold text-primary-600">Rp {{ number_format($cost, 0, ',', '.') }}</p>
        </div>
    </div>
    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        {{ number_format($count) }} customers × Rp 600
    </div>
</div>
