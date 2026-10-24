{{-- Inline colors use Filament's palette variables; arbitrary Tailwind classes are not in its compiled CSS --}}
<div class="space-y-4">
    @foreach($groups as $group)
        <div class="rounded-xl p-4" style="background: rgba(var(--warning-500), 0.1); box-shadow: inset 0 0 0 1px rgba(var(--warning-500), 0.3);">
            <p class="text-sm font-semibold" style="color: rgb(var(--warning-600));">{{ $group['title'] }}</p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $group['body'] }}</p>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="py-1.5 pe-4 text-start font-medium">Nama</th>
                            <th class="py-1.5 pe-4 text-start font-medium">No. HP</th>
                            <th class="py-1.5 text-end font-medium">Poin</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-950 dark:text-white">
                        @foreach($group['customers'] as $customer)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="py-1.5 pe-4">{{ $customer->user?->name ?? '-' }}</td>
                                <td class="py-1.5 pe-4">{{ $customer->user?->phone ?? '-' }}</td>
                                <td class="py-1.5 text-end font-semibold">{{ $customer->getPoints($type) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($group['customers']->count() >= 100)
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Menampilkan 100 teratas.</p>
            @endif
        </div>
    @endforeach
</div>
