<x-filament-panels::page class="pos-page">
    <script>
        // Plain script before the markup, so it exists when Alpine boots the page.
        window.posApp = (products, categories, promos, shift) => ({
            products, categories, promos, shift,
            search: '', category: null,
            cart: [], customer: null, redeem: false,
            phone: '', results: [], searching: false, newName: '',
            method: 'cash', paid: 0,
            promoId: '', manual: 0,
            busy: false, error: '', receipt: null, sheet: false, waSent: null,
            picker: null, opening: 0, closing: null, counted: 0, closeNote: '', closed: null,
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
            get subtotal() { return this.cart.reduce((sum, l) => sum + l.price * l.qty, 0); },
            // Mirrors Promo::discountFor; the server recomputes it anyway.
            get discount() {
                const promo = this.promos.find(p => p.id == this.promoId);
                const d = promo ? (promo.type === 'percent' ? Math.floor(this.subtotal * Math.min(100, promo.value) / 100) : promo.value) : this.manual;
                return Math.min(this.subtotal, d || 0);
            },
            get total() { return this.subtotal - this.discount; },
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

            qty(id) { return this.cart.filter(l => l.id === id).reduce((s, l) => s + l.qty, 0); },
            tap(product) {
                if (product.options.length) {
                    this.picker = { product, picks: product.options.map(g => g.multiple ? [] : (g.choices.length ? 0 : null)), note: '' };
                    return;
                }
                this.push(product, [], '');
            },
            toggle(g, c) {
                const picks = this.picker.picks;
                if (this.picker.product.options[g].multiple) {
                    picks[g] = picks[g].includes(c) ? picks[g].filter(x => x !== c) : [...picks[g], c];
                } else {
                    picks[g] = c;
                }
            },
            picked(g, c) {
                const p = this.picker.picks[g];
                return Array.isArray(p) ? p.includes(c) : p === c;
            },
            confirmPick() {
                const { product, picks, note } = this.picker;
                const pairs = picks.flatMap((p, g) => (Array.isArray(p) ? p : (p === null ? [] : [p])).map(c => [g, c]));
                this.push(product, pairs, note.trim());
                this.picker = null;
            },
            push(product, options, note) {
                const key = product.id + '|' + JSON.stringify(options) + '|' + note;
                const extra = options.reduce((s, [g, c]) => s + (+product.options[g].choices[c].price || 0), 0);
                const label = options.map(([g, c]) => product.options[g].choices[c].name).join(', ');
                const line = this.cart.find(l => l.key === key);
                line ? line.qty++ : this.cart.push({ key, id: product.id, name: product.name, price: product.price + extra, options, label, note, qty: 1 });
                this.error = '';
            },
            step(line, by) {
                line.qty += by;
                if (line.qty <= 0) this.cart = this.cart.filter(l => l !== line);
            },
            clear() { this.cart = []; this.paid = 0; this.redeem = false; this.promoId = ''; this.manual = 0; },

            async lookup() {
                if (this.phone.replace(/\D/g, '').length < 6) { this.results = []; return; }
                this.searching = true;
                this.results = await this.$wire.findCustomer(this.phone);
                this.searching = false;
                if (this.results.length === 1 && this.phone.replace(/\D/g, '').length >= 10) this.pick(this.results[0]);
            },
            pick(c) { this.customer = c; this.results = []; this.phone = ''; this.newName = ''; this.redeem = false; },
            async register() {
                this.busy = true;
                const result = await this.$wire.registerCustomer(this.newName, this.phone);
                this.busy = false;
                result.error ? this.error = result.error : this.pick(result);
            },

            async pay() {
                this.busy = true; this.error = '';
                const result = await this.$wire.checkout(
                    this.cart.map(l => ({ id: l.id, qty: l.qty, options: l.options, note: l.note })),
                    this.method, this.paid, this.customer?.id ?? null, this.redeem,
                    this.promoId ? { promo_id: +this.promoId } : { amount: this.manual },
                );
                this.busy = false;
                if (result.error) { this.error = result.error; return; }
                this.receipt = result;
                this.waSent = null;
                this.sheet = false;
                this.$nextTick(() => this.$refs.next?.focus());
            },
            async sendWa() {
                this.waSent = 'sending';
                this.waSent = await this.$wire.sendReceipt(this.receipt.id) ? 'ok' : 'fail';
            },
            reset() {
                this.receipt = null; this.customer = null; this.method = 'cash';
                this.clear();
            },
            print() { window.print(); },

            async openShift() {
                this.busy = true;
                this.shift = await this.$wire.openShift(this.opening);
                this.busy = false;
            },
            async startClose() {
                this.closing = await this.$wire.shiftSummary();
                this.counted = 0; this.closeNote = ''; this.closed = null;
            },
            async closeShift() {
                this.busy = true;
                this.closed = await this.$wire.closeShift(this.counted, this.closeNote);
                this.busy = false;
            },
            finishClose() { this.closing = null; this.closed = null; this.shift = null; this.opening = 0; this.reset(); },

            rp(n) { return 'Rp' + (n || 0).toLocaleString('id-ID'); },
            num(n) { return (n || 0).toLocaleString('id-ID'); },
            money(e) { return parseInt(e.target.value.replace(/\D/g, '')) || 0; },
        });
    </script>
    <div
        class="pos"
        x-data="posApp(@js($products), @js($categories), @js($promos), @js($shift))"
        x-on:keydown.escape.window="sheet = false; picker = null"
    >
        <div class="lt-hero pos-hero">
            <span class="lt-hero-icon"><x-filament::icon icon="heroicon-o-shopping-cart" /></span>
            <div>
                <h1>Kasir</h1>
                <p>{{ auth()->user()->name }} · <span x-text="clock"></span> WIB<span x-show="shift" x-text="' · shift sejak ' + shift?.opened"></span></p>
            </div>
            <button type="button" class="pos-secondary pos-hero-btn" x-show="shift" x-on:click="startClose()">
                <x-filament::icon icon="heroicon-m-lock-closed" /> Tutup shift
            </button>
        </div>

        {{-- Shift gate --}}
        <div class="pos-gate" x-show="! shift" x-cloak>
            <div class="pos-gate-card">
                <div class="pos-gate-top">
                    <span class="pos-gate-badge"><i></i> Kasir tutup</span>
                    <p class="pos-gate-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
                    <h2>Siap jualan, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}?</h2>
                    <p>Hitung uang di laci, masukkan modal awal, lalu buka kasir.</p>
                </div>
                <div class="pos-gate-main">
                    <label class="pos-gate-label" for="pos-opening">Modal awal di laci</label>
                    <div class="pos-gate-amount">
                        <span>Rp</span>
                        <input id="pos-opening" type="text" inputmode="numeric" placeholder="0" :value="opening ? num(opening) : ''" x-on:input="opening = money($event)" x-on:keydown.enter="openShift()" autofocus>
                    </div>
                    <div class="pos-gate-quick">
                        @foreach ([100000, 200000, 300000, 500000] as $amount)
                            <button type="button" x-on:click="opening = {{ $amount }}" :class="{ 'is-on': opening === {{ $amount }} }">{{ $amount / 1000 }}rb</button>
                        @endforeach
                    </div>
                    <ul class="pos-gate-check">
                        <li><x-filament::icon icon="heroicon-m-check-circle" /> Uang dihitung dua kali</li>
                        <li><x-filament::icon icon="heroicon-m-check-circle" /> Selisih dicek saat tutup shift</li>
                    </ul>
                    <button type="button" class="pos-gate-go" :disabled="busy" x-on:click="openShift()">
                        <x-filament::icon icon="heroicon-m-lock-open" /> Buka kasir sekarang
                    </button>
                </div>
            </div>
        </div>

        <div class="pos-body" x-show="shift">
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
                        <button type="button" class="pos-product" :class="{ 'is-in': qty(product.id) }" x-on:click="tap(product)">
                            <template x-if="product.image"><img class="pos-product-img" :src="product.image" alt="" loading="lazy"></template>
                            <template x-if="! product.image"><span class="pos-product-icon" :class="'is-' + product.category" x-text="product.name.charAt(0)"></span></template>
                            <span class="pos-product-cat" x-text="(categories[product.category] ?? '') + (product.options.length ? ' · varian' : '')"></span>
                            <span class="pos-product-name" x-text="product.name"></span>
                            <span class="pos-product-price" x-text="rp(product.price)"></span>
                            <span class="pos-product-qty" x-show="qty(product.id)" x-text="qty(product.id)"></span>
                        </button>
                    </template>
                    <p class="pos-empty" x-show="! visible.length" x-cloak>
                        @if ($products->isEmpty())
                            Belum ada menu. Tambahkan dulu di Produk → Menu.
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
                            <div class="pos-register" x-show="phone.replace(/\D/g, '').length >= 10 && ! searching && ! results.length" x-cloak>
                                <p class="pos-hint">Belum terdaftar. Daftarkan sebagai member?</p>
                                <div class="pos-register-row">
                                    <input type="text" class="pos-input" placeholder="Nama customer" x-model="newName" x-on:keydown.enter="newName.trim() && register()" aria-label="Nama customer baru">
                                    <button type="button" class="pos-secondary" :disabled="! newName.trim() || busy" x-on:click="register()">Daftar</button>
                                </div>
                            </div>
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
                    <template x-for="line in cart" :key="line.key">
                        <li>
                            <div class="pos-line-info">
                                <span x-text="line.name"></span>
                                <small x-text="[line.label, line.note && '“' + line.note + '”', rp(line.price)].filter(Boolean).join(' · ')"></small>
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
                    <div class="pos-discount" x-show="cart.length">
                        <select class="pos-input" x-model="promoId" aria-label="Promo">
                            <option value="">Tanpa promo</option>
                            <template x-for="p in promos" :key="p.id"><option :value="p.id" x-text="p.name + ' (' + p.label + ')'"></option></template>
                        </select>
                        <input type="text" inputmode="numeric" class="pos-input" placeholder="Diskon Rp" x-show="! promoId" :value="manual ? num(manual) : ''" x-on:input="manual = money($event)" aria-label="Diskon manual">
                    </div>
                    <div class="pos-subline" x-show="discount"><span>Subtotal</span><span x-text="rp(subtotal)"></span></div>
                    <div class="pos-subline" x-show="discount"><span>Diskon</span><span x-text="'-' + rp(discount)"></span></div>
                    <div class="pos-total"><span>Total</span><strong x-text="rp(total)"></strong></div>

                    <div class="pos-methods" role="radiogroup" aria-label="Metode bayar">
                        @foreach ($methods as $key => $label)
                            <button type="button" role="radio" :aria-checked="method === '{{ $key }}'" :class="{ 'is-on': method === '{{ $key }}' }" x-on:click="method = '{{ $key }}'">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="pos-cash" x-show="method === 'cash'">
                        <label class="pos-cash-input">
                            <span>Dibayar</span>
                            <input type="text" inputmode="numeric" placeholder="0" :value="paid ? num(paid) : ''" x-on:input="paid = money($event)" aria-label="Uang dibayar">
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
        <button type="button" class="pos-bar" x-show="shift && ! sheet" x-on:click="sheet = true">
            <span><b x-text="count"></b> item</span>
            <strong x-text="rp(total)"></strong>
            <x-filament::icon icon="heroicon-m-chevron-up" />
        </button>

        {{-- Variant picker --}}
        <div class="pos-modal" x-show="picker" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="pos-pick-title">
            <template x-if="picker">
                <div class="pos-modal-card pos-picker" x-on:click.outside="picker = null">
                    <h3 id="pos-pick-title" x-text="picker.product.name"></h3>
                    <template x-for="(group, g) in picker.product.options" :key="g">
                        <fieldset class="pos-option-group">
                            <legend x-text="group.name + (group.multiple ? ' (boleh lebih dari satu)' : '')"></legend>
                            <div class="pos-chips">
                                <template x-for="(choice, c) in group.choices" :key="c">
                                    <button type="button" :class="{ 'is-on': picked(g, c) }" :aria-pressed="picked(g, c)" x-on:click="toggle(g, c)"
                                        x-text="choice.name + (+choice.price ? ' +' + num(+choice.price) : '')"></button>
                                </template>
                            </div>
                        </fieldset>
                    </template>
                    <input type="text" class="pos-input" placeholder="Catatan (mis. less sugar)" maxlength="80" x-model="picker.note" aria-label="Catatan">
                    <div class="pos-modal-actions">
                        <button type="button" class="pos-secondary" x-on:click="picker = null">Batal</button>
                        <button type="button" class="pos-submit" x-on:click="confirmPick()">Tambah</button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Close shift --}}
        <div class="pos-modal" x-show="closing" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="pos-close-title">
            <div class="pos-modal-card">
                <h3 id="pos-close-title">Tutup shift</h3>
                <div x-show="! closed">
                    <div class="pos-close">
                        <p class="pos-hint" x-text="`${closing?.orders ?? 0} transaksi · penjualan ${rp(closing?.sales)}`"></p>
                        <div class="pos-subline"><span>Modal awal</span><span x-text="rp(shift?.opening)"></span></div>
                        <div class="pos-subline"><span>Seharusnya di laci</span><b x-text="rp(closing?.expected)"></b></div>
                        <label class="pos-cash-input">
                            <span>Uang dihitung</span>
                            <input type="text" inputmode="numeric" placeholder="0" :value="counted ? num(counted) : ''" x-on:input="counted = money($event)" aria-label="Uang dihitung">
                        </label>
                        <input type="text" class="pos-input" placeholder="Catatan (opsional)" maxlength="200" x-model="closeNote" aria-label="Catatan">
                        <div class="pos-modal-actions">
                            <button type="button" class="pos-secondary" x-on:click="closing = null">Batal</button>
                            <button type="button" class="pos-submit" :disabled="busy" x-on:click="closeShift()">Tutup shift</button>
                        </div>
                    </div>
                </div>
                <div x-show="closed">
                    <div class="pos-close">
                        <div class="pos-done-change" :class="{ 'is-short': closed?.difference }">
                            <span x-text="closed?.difference ? 'Selisih' : 'Kas pas'"></span>
                            <strong x-text="(closed?.difference > 0 ? '+' : closed?.difference < 0 ? '-' : '') + rp(Math.abs(closed?.difference))"></strong>
                        </div>
                        <p class="pos-hint" x-text="`Seharusnya ${rp(closed?.expected)}, dihitung ${rp(closed?.counted)}.`"></p>
                        <button type="button" class="pos-submit" x-on:click="finishClose()">Selesai</button>
                    </div>
                </div>
            </div>
        </div>

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
                        <x-filament::icon icon="heroicon-m-printer" /> Cetak
                    </button>
                    <button type="button" class="pos-secondary" x-show="receipt?.customer" :disabled="waSent === 'sending' || waSent === 'ok'" x-on:click="sendWa()">
                        <x-filament::icon icon="heroicon-m-chat-bubble-left-ellipsis" />
                        <span x-text="{ sending: 'Mengirim…', ok: 'Terkirim', fail: 'Gagal, ulangi' }[waSent] ?? 'Struk WA'"></span>
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
                            <p x-show="item.options || item.note" x-text="'  ' + [item.options, item.note].filter(Boolean).join(' · ')"></p>
                            <p class="r-row"><span x-text="item.qty + ' x ' + num(item.price)"></span><span x-text="item.price ? num(item.price * item.qty) : 'GRATIS'"></span></p>
                        </div>
                    </template>
                    <hr>
                    <template x-if="receipt.discount">
                        <div>
                            <p class="r-row"><span>Subtotal</span><span x-text="num(receipt.subtotal)"></span></p>
                            <p class="r-row"><span x-text="receipt.promo || 'Diskon'"></span><span x-text="'-' + num(receipt.discount)"></span></p>
                        </div>
                    </template>
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
