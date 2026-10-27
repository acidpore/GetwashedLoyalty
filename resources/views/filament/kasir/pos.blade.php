<x-filament-panels::page class="pos-page">
    <script>
        // Plain script before the markup, so it exists when Alpine boots the page.
        window.posApp = (products, categories) => ({
            products, categories,
            search: '', category: null,
            cart: [], customer: null, redeem: false,
            phone: '', results: [], searching: false,
            method: 'cash', paid: 0,
            busy: false, error: '', receipt: null, sheet: false,
            clock: '',

            init() {
                const tick = () => this.clock = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                tick();
                setInterval(tick, 15000);
            },

            get visible() {
                const q = this.search.trim().toLowerCase();
                return this.products.filter(p => (this.category === null || p.category === this.category) && (! q || p.name.toLowerCase().includes(q)));
            },
            get total() { return this.cart.reduce((sum, l) => sum + l.price * l.qty, 0); },
            get count() { return this.cart.reduce((sum, l) => sum + l.qty, 0); },
            get canPay() {
                if (! this.cart.length && ! this.redeem) return false;
                return this.method !== 'cash' || this.paid >= this.total;
            },
            // Exact amount, then the next round notes above the total.
            get quick() {
                const t = this.total;
                if (! t) return [];
                const ups = [5000, 10000, 20000, 50000, 100000].map(n => Math.ceil(t / n) * n).filter(n => n > t);
                return [t, ...new Set(ups)].slice(0, 4);
            },

            qty(id) { return this.cart.find(l => l.id === id)?.qty ?? 0; },
            add(product) {
                const line = this.cart.find(l => l.id === product.id);
                line ? line.qty++ : this.cart.push({ ...product, qty: 1 });
                this.error = '';
            },
            step(line, by) {
                line.qty += by;
                if (line.qty <= 0) this.cart = this.cart.filter(l => l !== line);
            },
            clear() { this.cart = []; this.paid = 0; this.redeem = false; },

            async lookup() {
                if (this.phone.replace(/\D/g, '').length < 6) { this.results = []; return; }
                this.searching = true;
                this.results = await this.$wire.findCustomer(this.phone);
                this.searching = false;
                if (this.results.length === 1 && this.phone.replace(/\D/g, '').length >= 10) this.pick(this.results[0]);
            },
            pick(c) { this.customer = c; this.results = []; this.phone = ''; this.redeem = false; },

            async pay() {
                this.busy = true; this.error = '';
                const result = await this.$wire.checkout(
                    this.cart.map(l => ({ id: l.id, qty: l.qty })),
                    this.method, this.paid, this.customer?.id ?? null, this.redeem,
                );
                this.busy = false;
                if (result.error) { this.error = result.error; return; }
                this.receipt = result;
                this.sheet = false;
                this.$nextTick(() => this.$refs.next?.focus());
            },
            reset() {
                this.receipt = null; this.customer = null; this.method = 'cash';
                this.clear();
            },
            print() { window.print(); },

            rp(n) { return 'Rp' + (n || 0).toLocaleString('id-ID'); },
            num(n) { return (n || 0).toLocaleString('id-ID'); },
        });
    </script>
    <div
        class="pos"
        x-data="posApp(@js($products), @js($categories))"
        x-on:keydown.escape.window="sheet = false"
    >
        <div class="lt-hero pos-hero">
            <span class="lt-hero-icon"><x-filament::icon icon="heroicon-o-shopping-cart" /></span>
            <div>
                <h1>Kasir</h1>
                <p>{{ auth()->user()->name }} · <span x-text="clock"></span> WIB</p>
            </div>
        </div>

        <div class="pos-body">
            {{-- Menu --}}
            <section class="pos-menu" aria-label="Menu">
                <div class="pos-tools">
                    <label class="pos-search">
                        <x-filament::icon icon="heroicon-m-magnifying-glass" />
                        <input type="search" placeholder="Cari menu…" x-model="search" aria-label="Cari menu">
                    </label>
                    <div class="pos-chips" role="tablist">
                        <button type="button" role="tab" :aria-selected="category === null" x-on:click="category = null" :class="{ 'is-on': category === null }">Semua</button>
                        <template x-for="(label, key) in categories" :key="key">
                            <button type="button" role="tab" x-show="products.some(p => p.category === key)" :aria-selected="category === key" x-on:click="category = key" :class="{ 'is-on': category === key }" x-text="label"></button>
                        </template>
                    </div>
                </div>

                <div class="pos-grid">
                    <template x-for="product in visible" :key="product.id">
                        <button type="button" class="pos-product" :class="{ 'is-in': qty(product.id) }" x-on:click="add(product)">
                            <span class="pos-product-icon" :class="'is-' + product.category" x-text="product.name.charAt(0)"></span>
                            <span class="pos-product-cat" x-text="categories[product.category] ?? ''"></span>
                            <span class="pos-product-name" x-text="product.name"></span>
                            <span class="pos-product-price" x-text="rp(product.price)"></span>
                            <span class="pos-product-qty" x-show="qty(product.id)" x-text="qty(product.id)"></span>
                        </button>
                    </template>
                    <p class="pos-empty" x-show="! visible.length" x-cloak>
                        @if ($products->isEmpty())
                            Belum ada menu. Tambahkan dulu di admin → Latte → Menu.
                        @else
                            Menu tidak ditemukan.
                        @endif
                    </p>
                </div>
            </section>

            {{-- Cart --}}
            <aside class="pos-cart" :class="{ 'is-open': sheet }" aria-label="Pesanan">
                <div class="pos-cart-top">
                    <h2>Pesanan</h2>
                    <button type="button" class="pos-link" x-show="cart.length" x-on:click="clear()">Kosongkan</button>
                    <button type="button" class="pos-icon-btn pos-sheet-close" x-on:click="sheet = false" aria-label="Tutup">
                        <x-filament::icon icon="heroicon-m-chevron-down" />
                    </button>
                </div>

                {{-- Customer --}}
                <div class="pos-customer">
                    <template x-if="! customer">
                        <div>
                            <label class="pos-search">
                                <x-filament::icon icon="heroicon-m-user" />
                                <input type="tel" inputmode="numeric" placeholder="No. HP customer (opsional)" x-model="phone" x-on:input.debounce.350ms="lookup()" aria-label="Nomor HP customer">
                            </label>
                            <ul class="pos-results" x-show="results.length">
                                <template x-for="c in results" :key="c.id">
                                    <li><button type="button" x-on:click="pick(c)">
                                        <strong x-text="c.name"></strong>
                                        <span x-text="c.phone"></span>
                                    </button></li>
                                </template>
                            </ul>
                            <p class="pos-hint" x-show="phone.replace(/\D/g, '').length >= 8 && ! searching && ! results.length" x-cloak>Customer tidak ditemukan. Transaksi tetap bisa tanpa customer.</p>
                        </div>
                    </template>
                    <template x-if="customer">
                        <div class="pos-member">
                            <div class="pos-member-row">
                                <div>
                                    <strong x-text="customer.name"></strong>
                                    <span x-text="customer.phone"></span>
                                </div>
                                <button type="button" class="pos-icon-btn" x-on:click="customer = null; redeem = false" aria-label="Lepas customer">
                                    <x-filament::icon icon="heroicon-m-x-mark" />
                                </button>
                            </div>
                            <div class="pos-progress" role="progressbar" :aria-valuenow="customer.points" :aria-valuemax="customer.target">
                                <i :style="`width:${Math.min(100, customer.points / customer.target * 100)}%`"></i>
                            </div>
                            <span class="pos-member-points"><b x-text="customer.points"></b>/<span x-text="customer.target"></span> poin · +1 poin dari transaksi ini</span>
                            <label class="pos-reward" x-show="customer.reward">
                                <input type="checkbox" x-model="redeem">
                                <span>Pakai hadiah: <b x-text="customer.reward"></b></span>
                            </label>
                        </div>
                    </template>
                </div>

                {{-- Lines --}}
                <ul class="pos-lines">
                    <template x-for="line in cart" :key="line.id">
                        <li>
                            <div class="pos-line-info">
                                <span x-text="line.name"></span>
                                <small x-text="rp(line.price)"></small>
                            </div>
                            <div class="pos-stepper">
                                <button type="button" x-on:click="step(line, -1)" :aria-label="'Kurangi ' + line.name">−</button>
                                <span x-text="line.qty"></span>
                                <button type="button" x-on:click="step(line, 1)" :aria-label="'Tambah ' + line.name">+</button>
                            </div>
                            <span class="pos-line-total" x-text="rp(line.price * line.qty)"></span>
                        </li>
                    </template>
                    <li class="pos-line-reward" x-show="redeem && customer?.reward">
                        <div class="pos-line-info"><span x-text="customer?.reward"></span><small>Hadiah loyalty</small></div>
                        <span class="pos-line-total">Gratis</span>
                    </li>
                    <li class="pos-empty" x-show="! cart.length && ! redeem">Ketuk menu untuk menambahkan.</li>
                </ul>

                {{-- Payment --}}
                <div class="pos-pay">
                    <div class="pos-total"><span>Total</span><strong x-text="rp(total)"></strong></div>

                    <div class="pos-methods" role="radiogroup" aria-label="Metode bayar">
                        @foreach ($methods as $key => $label)
                            <button type="button" role="radio" :aria-checked="method === '{{ $key }}'" :class="{ 'is-on': method === '{{ $key }}' }" x-on:click="method = '{{ $key }}'">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="pos-cash" x-show="method === 'cash'">
                        <label class="pos-cash-input">
                            <span>Dibayar</span>
                            <input type="text" inputmode="numeric" placeholder="0" :value="paid ? paid.toLocaleString('id-ID') : ''" x-on:input="paid = parseInt($event.target.value.replace(/\D/g, '')) || 0" aria-label="Uang dibayar">
                        </label>
                        <div class="pos-quick">
                            <template x-for="amount in quick" :key="amount">
                                <button type="button" x-on:click="paid = amount" x-text="amount === total ? 'Uang pas' : rp(amount)"></button>
                            </template>
                        </div>
                        <div class="pos-change" :class="{ 'is-short': paid && paid < total }">
                            <span x-text="paid && paid < total ? 'Kurang' : 'Kembalian'"></span>
                            <strong x-text="rp(Math.abs(paid - total) * (paid ? 1 : 0))"></strong>
                        </div>
                    </div>

                    <p class="pos-error" x-show="error" x-text="error" role="alert"></p>

                    <button type="button" class="pos-submit" :disabled="! canPay || busy" x-on:click="pay()">
                        <span x-show="! busy" x-text="'Bayar ' + rp(total)"></span>
                        <span x-show="busy">Memproses…</span>
                    </button>
                </div>
            </aside>
        </div>

        {{-- Phone: cart collapses into a bar --}}
        <button type="button" class="pos-bar" x-show="! sheet" x-on:click="sheet = true">
            <span><b x-text="count"></b> item</span>
            <strong x-text="rp(total)"></strong>
            <x-filament::icon icon="heroicon-m-chevron-up" />
        </button>

        {{-- Done --}}
        <div class="pos-modal" x-show="receipt" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="pos-done-title">
            <div class="pos-modal-card" x-on:click.outside="reset()">
                <div class="pos-done-icon"><x-filament::icon icon="heroicon-m-check" /></div>
                <h3 id="pos-done-title">Pembayaran berhasil</h3>
                <p class="pos-done-number" x-text="receipt?.number"></p>
                <div class="pos-done-change" x-show="receipt?.change">
                    <span>Kembalian</span>
                    <strong x-text="rp(receipt?.change)"></strong>
                </div>
                <p class="pos-done-points" x-show="receipt?.customer">
                    <span x-text="receipt?.customer?.name"></span>
                    <span x-text="receipt?.points_earned ? `+${receipt.points_earned} poin · sekarang ${receipt.customer.points}/${receipt.customer.target}` : 'Poin terkunci, klaim hadiah dulu'"></span>
                </p>
                <div class="pos-modal-actions">
                    <button type="button" class="pos-secondary" x-on:click="print()">
                        <x-filament::icon icon="heroicon-m-printer" /> Cetak struk
                    </button>
                    <button type="button" class="pos-submit" x-on:click="reset()" x-ref="next">Transaksi baru</button>
                </div>
            </div>
        </div>

        {{-- Receipt, only visible when printing --}}
        <div id="pos-receipt" aria-hidden="true">
            <template x-if="receipt">
                <div>
                    <p class="r-center r-bold">GETWASHED LATTE</p>
                    <p class="r-center" x-text="receipt.date"></p>
                    <p class="r-center" x-text="receipt.number + ' · ' + receipt.cashier"></p>
                    <hr>
                    <template x-for="(item, i) in receipt.items" :key="i">
                        <div>
                            <p x-text="item.name"></p>
                            <p class="r-row"><span x-text="item.qty + ' x ' + num(item.price)"></span><span x-text="item.price ? num(item.price * item.qty) : 'GRATIS'"></span></p>
                        </div>
                    </template>
                    <hr>
                    <p class="r-row r-bold"><span>TOTAL</span><span x-text="num(receipt.total)"></span></p>
                    <p class="r-row"><span x-text="receipt.method"></span><span x-text="num(receipt.paid)"></span></p>
                    <p class="r-row" x-show="receipt.change"><span>Kembali</span><span x-text="num(receipt.change)"></span></p>
                    <template x-if="receipt.customer">
                        <div>
                            <hr>
                            <p x-text="receipt.customer.name"></p>
                            <p x-text="`Poin coffee: ${receipt.customer.points}/${receipt.customer.target}`"></p>
                        </div>
                    </template>
                    <hr>
                    <p class="r-center">Terima kasih!</p>
                </div>
            </template>
        </div>
    </div>

</x-filament-panels::page>
