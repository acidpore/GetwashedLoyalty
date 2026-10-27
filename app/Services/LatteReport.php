<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RewardClaim;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Sales report for Getwashed Latte over a date range, compared with the
 * equally long period right before it. Feeds the Laporan page, the PDF and
 * the XLSX so all three always show the same numbers.
 */
class LatteReport
{
    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public static function build(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();
        $length = (int) $from->diffInDays($to) + 1;
        $prevFrom = $from->subDays($length);
        $prevTo = $from->subSecond();

        $orders = Order::with(['items', 'cashier:id,name'])->whereBetween('created_at', [$from, $to])->get();
        $paid = $orders->whereNull('voided_at');
        $voided = $orders->whereNotNull('voided_at');
        $prev = Order::paid()->whereBetween('created_at', [$prevFrom, $prevTo])->get(['id', 'total', 'discount', 'customer_id']);

        $summary = self::summary($paid, $voided);
        $previous = self::summary($prev, collect());

        $items = $paid->flatMap->items->where('price', '>', 0);
        $categories = Product::pluck('category', 'id');

        $products = $items->groupBy('name')->map(fn (Collection $rows, string $name) => [
            'name' => $name,
            'category' => Product::CATEGORIES[$categories[$rows->first()->product_id] ?? ''] ?? '-',
            'qty' => $rows->sum('qty'),
            'revenue' => $rows->sum(fn ($i) => $i->price * $i->qty),
        ])->sortByDesc('revenue')->values();
        $itemRevenue = max(1, $products->sum('revenue'));
        $running = 0;
        $products = $products->map(function ($row) use ($itemRevenue, &$running) {
            $running += $row['revenue'];

            return $row + ['share' => $row['revenue'] / $itemRevenue * 100, 'cumulative' => $running / $itemRevenue * 100];
        });

        $byCategory = $products->groupBy('category')->map(fn ($rows, $name) => [
            'name' => $name, 'qty' => $rows->sum('qty'), 'revenue' => $rows->sum('revenue'), 'share' => $rows->sum('revenue') / $itemRevenue * 100,
        ])->sortByDesc('revenue')->values();

        $daily = collect();
        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            $rows = $paid->filter(fn ($o) => $o->created_at->isSameDay($day));
            $daily->push([
                'date' => $day,
                'label' => $day->locale('id')->translatedFormat('D, d M'),
                'orders' => $rows->count(),
                'revenue' => $rows->sum('total'),
                'discount' => $rows->sum('discount'),
                'avg' => $rows->count() ? intdiv($rows->sum('total'), $rows->count()) : 0,
            ]);
        }

        $hours = collect(range(0, 23))->mapWithKeys(fn ($h) => [$h => ['hour' => $h, 'orders' => 0, 'revenue' => 0]]);
        $weekdays = collect(self::DAYS)->map(fn ($d) => ['day' => $d, 'orders' => 0, 'revenue' => 0, 'dates' => []])->all();
        foreach ($paid as $o) {
            $h = $hours[$o->created_at->hour];
            $hours[$o->created_at->hour] = ['hour' => $h['hour'], 'orders' => $h['orders'] + 1, 'revenue' => $h['revenue'] + $o->total];
            $w = $o->created_at->dayOfWeekIso - 1;
            $weekdays[$w]['orders']++;
            $weekdays[$w]['revenue'] += $o->total;
        }
        // Average per occurrence of that weekday in the range, so ranges that hold more Mondays don't skew it.
        $weekdays = collect($weekdays)->map(function ($row, $i) use ($from, $to) {
            $count = collect(range(0, (int) $from->diffInDays($to)))->filter(fn ($d) => $from->addDays($d)->dayOfWeekIso - 1 === $i)->count();

            return ['day' => $row['day'], 'orders' => $row['orders'], 'revenue' => $row['revenue'], 'avg_revenue' => $count ? intdiv($row['revenue'], $count) : 0];
        });
        $activeHours = $hours->where('orders', '>', 0)->values();

        $methods = collect(Order::METHODS)->map(fn ($label, $key) => [
            'name' => $label,
            'orders' => $paid->where('payment_method', $key)->count(),
            'revenue' => $paid->where('payment_method', $key)->sum('total'),
        ])->values();

        $cashiers = $paid->groupBy(fn ($o) => $o->cashier?->name ?? '-')->map(fn ($rows, $name) => [
            'name' => $name,
            'orders' => $rows->count(),
            'revenue' => $rows->sum('total'),
            'avg' => intdiv($rows->sum('total'), max(1, $rows->count())),
            'voids' => $voided->filter(fn ($o) => ($o->cashier?->name ?? '-') === $name)->count(),
        ])->sortByDesc('revenue')->values();

        $promos = $paid->where('discount', '>', 0)->groupBy('promo_name')->map(fn ($rows, $name) => [
            'name' => $name ?: 'Diskon', 'orders' => $rows->count(), 'discount' => $rows->sum('discount'), 'revenue' => $rows->sum('total'),
        ])->sortByDesc('discount')->values();

        $shifts = CashShift::with('user:id,name')->whereBetween('opened_at', [$from, $to])->whereNotNull('closed_at')->orderBy('opened_at')->get()
            ->map(fn (CashShift $s) => [
                'cashier' => $s->user?->name ?? '-',
                'opened' => $s->opened_at, 'closed' => $s->closed_at,
                'opening' => $s->opening_cash, 'expected' => (int) $s->expected_cash, 'counted' => (int) $s->closing_cash,
                'difference' => $s->difference(), 'note' => $s->note,
            ]);

        $members = $paid->whereNotNull('customer_id');
        $loyalty = [
            'member_orders' => $members->count(),
            'member_share' => $paid->count() ? $members->count() / $paid->count() * 100 : 0,
            'member_avg' => $members->count() ? intdiv($members->sum('total'), $members->count()) : 0,
            'guest_avg' => ($g = $paid->whereNull('customer_id'))->count() ? intdiv($g->sum('total'), $g->count()) : 0,
            'unique_members' => $members->pluck('customer_id')->unique()->count(),
            'repeat_members' => $members->countBy('customer_id')->filter(fn ($n) => $n > 1)->count(),
            'points' => $paid->sum('points_earned'),
            'rewards' => RewardClaim::where('loyalty_type', 'coffeeshop')->whereBetween('claimed_at', [$from, $to])->count(),
        ];

        $report = [
            'from' => $from, 'to' => $to, 'days' => $length,
            'prev_from' => $prevFrom, 'prev_to' => $prevTo,
            'generated_at' => now(),
            'summary' => $summary,
            'previous' => $previous,
            'daily' => $daily,
            'hours' => $activeHours,
            'weekdays' => $weekdays,
            'methods' => $methods,
            'products' => $products,
            'categories' => $byCategory,
            'cashiers' => $cashiers,
            'promos' => $promos,
            'shifts' => $shifts,
            'loyalty' => $loyalty,
            'voids' => $voided->map(fn ($o) => ['number' => $o->number, 'at' => $o->created_at, 'total' => $o->total, 'reason' => $o->void_reason, 'cashier' => $o->cashier?->name])->values(),
            'low_stock' => Ingredient::low()->orderBy('name')->get(),
            'unsold' => Product::where('is_active', true)->whereNotIn('name', $products->pluck('name'))->orderBy('name')->pluck('name'),
        ];

        return $report + ['insights' => self::insights($report)];
    }

    private static function summary(Collection $paid, Collection $voided): array
    {
        $count = $paid->count();
        $net = (int) $paid->sum('total');
        $discount = (int) $paid->sum('discount');

        return [
            'gross' => $net + $discount,
            'discount' => $discount,
            'net' => $net,
            'orders' => $count,
            'avg' => $count ? intdiv($net, $count) : 0,
            'items' => $paid->sum(fn ($o) => $o->relationLoaded('items') ? $o->items->where('price', '>', 0)->sum('qty') : 0),
            'voids' => $voided->count(),
            'void_value' => (int) $voided->sum('total'),
        ];
    }

    public static function change(int|float $now, int|float $before): ?float
    {
        return $before ? ($now - $before) / $before * 100 : null;
    }

    private const LEVELS = ['critical' => 0, 'warning' => 1, 'good' => 2, 'info' => 3];

    /**
     * Manager-style briefing: a verdict, the number behind it, and what to do.
     * Sorted critical first. Each: level, title, metric, text, action.
     */
    private static function insights(array $r): array
    {
        $s = $r['summary'];
        $p = $r['previous'];
        $rp = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');
        $pct = fn ($n) => number_format(abs($n), 1, ',', '.') . '%';
        $out = [];
        $add = function (string $level, string $title, string $metric, string $text, string $action) use (&$out) {
            $out[] = compact('level', 'title', 'metric', 'text', 'action');
        };

        if (! $s['orders']) {
            $add('critical', 'Tidak ada penjualan', 'Rp0', 'Tidak ada satu pun transaksi tercatat di periode ini.', 'Pastikan kasir mencatat setiap penjualan lewat POS.');

            return $out;
        }

        if (($c = self::change($s['net'], $p['net'])) !== null) {
            $o = self::change($s['orders'], $p['orders']) ?? 0;
            $a = self::change($s['avg'], $p['avg']) ?? 0;
            $driver = abs($o) >= abs($a)
                ? 'jumlah transaksi (' . ($o >= 0 ? '+' : '-') . $pct($o) . ')'
                : 'nilai per transaksi (' . ($a >= 0 ? '+' : '-') . $pct($a) . ')';
            if ($c <= -10) {
                $add('critical', 'Omzet turun ' . $pct($c), '-' . $rp($p['net'] - $s['net']),
                    "Omzet {$rp($s['net'])} vs {$rp($p['net'])} periode sebelumnya. Penyebab utama: {$driver}.",
                    $o < $a ? 'Traffic turun. Dorong kunjungan: promo jam sepi, broadcast WA ke member.' : 'Belanja per orang turun. Latih kasir upsell: extra shot, makanan pendamping.');
            } elseif ($c < 0) {
                $add('warning', 'Omzet melemah ' . $pct($c), '-' . $rp($p['net'] - $s['net']),
                    "Omzet {$rp($s['net'])} vs {$rp($p['net'])}. Penggerak: {$driver}.", 'Pantau minggu depan. Jika berlanjut, jalankan promo terarah.');
            } else {
                $add('good', 'Omzet tumbuh ' . $pct($c), '+' . $rp($s['net'] - $p['net']),
                    "Omzet {$rp($s['net'])} vs {$rp($p['net'])}. Penggerak: {$driver}.", 'Pertahankan. Cek menu dan jam yang mendorong kenaikan ini.');
            }
        }

        if (($bad = $r['shifts']->filter(fn ($sh) => $sh['difference']))->isNotEmpty()) {
            $short = $bad->where('difference', '<', 0)->sum('difference');
            $add($short < -20000 ? 'critical' : 'warning', "Selisih kas di {$bad->count()} shift", ($bad->sum('difference') < 0 ? '-' : '+') . $rp(abs($bad->sum('difference'))),
                'Kas kurang total ' . $rp(abs($short)) . ', lebih ' . $rp($bad->where('difference', '>', 0)->sum('difference'))
                . '. Kasir terbanyak selisih: ' . $bad->countBy('cashier')->sortDesc()->keys()->first() . '.',
                'Audit shift bermasalah. Wajibkan hitung uang bersama saat serah terima.');
        }

        if ($s['voids']) {
            $rate = $s['voids'] / ($s['orders'] + $s['voids']) * 100;
            $top = $r['cashiers']->sortByDesc('voids')->first();
            $add($rate > 5 ? 'critical' : ($rate > 2 ? 'warning' : 'info'), "{$s['voids']} transaksi dibatalkan", $pct($rate),
                "Nilai batal {$rp($s['void_value'])}." . ($top && $top['voids'] ? " Terbanyak oleh {$top['name']} ({$top['voids']})." : ''),
                $rate > 2 ? 'Di atas batas wajar 2%. Telusuri alasan pembatalan per kasir.' : 'Masih wajar. Tetap pantau.');
        }

        if ($r['low_stock']->isNotEmpty()) {
            $add('critical', $r['low_stock']->count() . ' bahan di bawah minimum', $r['low_stock']->count() . ' item',
                'Stok kritis: ' . $r['low_stock']->pluck('name')->implode(', ') . '.', 'Belanja hari ini sebelum menu terkait tidak bisa dijual.');
        }

        if ($r['products']->count() >= 3) {
            $top = $r['products']->first();
            $core = $r['products']->takeWhile(fn ($row) => $row['cumulative'] <= 80)->count() + 1;
            $add('info', "{$core} dari {$r['products']->count()} menu = 80% pendapatan", $pct($top['share']),
                "Menu nomor satu: {$top['name']} ({$pct($top['share'])} pendapatan, {$top['qty']} terjual).",
                'Prioritaskan stok dan kualitas menu inti ini. Jangan sampai kosong.');
        }

        if ($r['unsold']->isNotEmpty()) {
            $add('warning', $r['unsold']->count() . ' menu tidak terjual', '0 terjual',
                'Nol penjualan: ' . $r['unsold']->take(6)->implode(', ') . ($r['unsold']->count() > 6 ? ', dll' : '') . '.',
                'Evaluasi: promosikan, ubah resep, atau hapus dari menu.');
        }

        $l = $r['loyalty'];
        if ($l['member_share'] < 30) {
            $add('warning', 'Member hanya ' . $pct($l['member_share']) . ' transaksi', $pct($l['member_share']),
                "{$l['member_orders']} dari {$s['orders']} transaksi tercatat member. Sisanya pembeli anonim yang tidak bisa dihubungi lagi.",
                'Target 40%. Kasir wajib menawarkan daftar member di setiap transaksi.');
        } else {
            $add('good', 'Member ' . $pct($l['member_share']) . ' transaksi', $pct($l['member_share']),
                "{$l['repeat_members']} dari {$l['unique_members']} member kembali lebih dari sekali. Belanja member {$rp($l['member_avg'])} vs non-member {$rp($l['guest_avg'])}.",
                'Jaga retensi: kirim pengingat poin ke member yang lama tidak datang.');
        }

        $busiest = $r['weekdays']->sortByDesc('avg_revenue')->first();
        $quiet = $r['weekdays']->filter(fn ($d) => $d['orders'] > 0)->sortBy('avg_revenue')->first();
        if ($r['days'] >= 7 && $quiet && $busiest['day'] !== $quiet['day']) {
            $gap = self::change($quiet['avg_revenue'], $busiest['avg_revenue']);
            $add('info', "{$quiet['day']} hari terlemah", $rp($quiet['avg_revenue']),
                "Rata-rata omzet {$quiet['day']} {$pct($gap)} di bawah {$busiest['day']} ({$rp($busiest['avg_revenue'])}).",
                "Jalankan promo khusus hari {$quiet['day']}.");
        }

        if ($r['hours']->isNotEmpty()) {
            $peak = $r['hours']->sortByDesc('orders')->first();
            $add('info', sprintf('Jam puncak %02d.00–%02d.00', $peak['hour'], $peak['hour'] + 1), $pct($peak['orders'] / $s['orders'] * 100),
                "{$peak['orders']} transaksi terjadi di jam ini.", 'Barista lengkap dan bahan siap 30 menit sebelumnya.');
        }

        if ($s['discount'] && ($share = $s['discount'] / max(1, $s['gross']) * 100) > 5) {
            $add('warning', 'Diskon memakan ' . $pct($share) . ' omzet', $rp($s['discount']),
                'Total potongan harga melewati 5% omzet kotor.', 'Batasi diskon manual. Ukur apakah promo benar menambah transaksi.');
        }

        usort($out, fn ($a, $b) => self::LEVELS[$a['level']] <=> self::LEVELS[$b['level']]);

        return $out;
    }
}
