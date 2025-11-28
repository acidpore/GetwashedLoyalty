<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Column: QR Code Preview -->
        <x-filament::section>
            <x-slot name="heading">
                QR Code Preview
            </x-slot>
            
            <div class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-xl border border-gray-200">
                <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
                    {!! $this->getQrCodeSvg() !!}
                </div>
                
                <div class="flex gap-3">
                    <x-filament::button
                        icon="heroicon-o-printer"
                        color="gray"
                        tag="button"
                        onclick="window.print()"
                    >
                        Print QR Code
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ $this->getCheckinUrl() }}"
                        target="_blank"
                        icon="heroicon-o-arrow-top-right-on-square"
                    >
                        Test Link
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <!-- Right Column: Instructions & Details -->
        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">
                    Configuration Details
                </x-slot>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Target URL</label>
                        <div class="flex items-center gap-2 mt-1">
                            <code class="bg-gray-100 px-3 py-2 rounded-lg text-sm flex-1 border border-gray-200 block">
                                {{ $this->getCheckinUrl() }}
                            </code>
                            <x-filament::icon-button
                                icon="heroicon-o-clipboard"
                                color="gray"
                                x-on:click="window.navigator.clipboard.writeText('{{ $this->getCheckinUrl() }}'); $tooltip('Copied to clipboard!', { timeout: 1500 });"
                            />
                        </div>
                    </div>

                    <div class="text-sm text-gray-600">
                        <p class="mb-2"><strong>Status:</strong> <span class="text-success-600 font-bold">Active</span></p>
                        <p>This QR code points directly to the customer check-in page. It does not expire.</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Instructions for Cashier
                </x-slot>
                
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-2">
                    <li>Print this QR code and place it near the payment counter.</li>
                    <li>Ask customers to scan it to collect points.</li>
                    <li>Ensure the customer enters their correct WhatsApp number.</li>
                    <li>If a customer has 5 points, they are eligible for a discount.</li>
                </ul>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
