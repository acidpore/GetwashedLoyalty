<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\RewardClaim;
use App\Models\VisitHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Numbers behind the Analytics page. Every widget asks for the same report,
 * so it is computed once per filter combination and cached for a few minutes.
 *
 * ponytail: loads every visit row and aggregates in PHP. Fine into the low
 * hundreds of thousands of visits; past that, move the aggregates to SQL.
 */
class BusinessAnalytics
{
    public const PROGRAMS = [
        'carwash' => 'Cuci Mobil',
        'motorwash' => 'Cuci Motor',
        'coffeeshop' => 'Coffee Shop',
    ];

    public const COLORS = [
        'carwash' => '#3b82f6',
        'motorwash' => '#ea580c',
        'coffeeshop' => '#16a34a',
    ];

    public const PERIODS = [
        7 => '7 hari terakhir',
        30 => '30 hari terakhir',
        90 => '3 bulan terakhir',
        180 => '6 bulan terakhir',
        365 => '12 bulan terakhir',
    ];

    public const DAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    private CarbonImmutable $now;

    private CarbonImmutable $from;

    private CarbonImmutable $prevFrom;

    /** @var Collection<int, array{c:int, t:array, at:CarbonImmutable}> */
    private Collection $visits;

    public function __construct(private int $days, private ?string $program)
    {
        $this->now = CarbonImmutable::now()->locale('id');
        $this->from = $this->now->subDays($days - 1)->startOfDay();
        $this->prevFrom = $this->from->subDays($days);
    }

    public static function report(?array $filters): array
    {
        $days = (int) ($filters['period'] ?? 30);
        $days = array_key_exists($days, self::PERIODS) ? $days : 30;
        $program = array_key_exists($filters['program'] ?? '', self::PROGRAMS) ? $filters['program'] : null;

        return Cache::remember(
            "analytics:{$days}:" . ($program ?? 'all'),
            600,
            fn () => (new self($days, $program))->build(),
        );
    }

    private function build(): array
    {
        $this->visits = VisitHistory::query()
            ->orderBy('visited_at')
            ->get(['customer_id', 'loyalty_types', 'visited_at'])
            ->map(fn (VisitHistory $v) => [
                'c' => $v->customer_id,
                't' => (array) $v->loyalty_types,
                'at' => CarbonImmutable::instance($v->visited_at),
            ])
            ->filter(fn ($v) => ! $this->program || in_array($this->program, $v['t'], true))
            ->values();

        $current = $this->window($this->from, $this->now);
        $previous = $this->window($this->prevFrom, $this->from);

        $report = [
            'days' => $this->days,
            'program' => $this->program,
            'range' => $this->from->translatedFormat('d M Y') . ' – ' . $this->now->translatedFormat('d M Y'),
            'prev_range' => $this->prevFrom->translatedFormat('d M Y') . ' – ' . $this->from->subDay()->translatedFormat('d M Y'),
            'generated_at' => $this->now->format('d/m/Y H:i'),
            'total_visits' => $this->visits->count(),
            'has_previous' => $this->visits->isNotEmpty() && $this->visits->first()['at'] < $this->from,
            'kpi' => $this->kpis($current, $previous),
            'trend' => $this->trend(),
            'heatmap' => $this->heatmap($current['visits']),
            'cohorts' => $this->cohorts(),
            'segments' => $this->segments(),
            'frequency' => $this->frequency(),
            'mix' => $this->mix($current['visits']),
            'rewards' => $this->rewards(),
            'top' => $this->topCustomers($current['visits']),
        ];
        $report['insights'] = $this->insights($report);

        return $report;
    }

    /** Visits in [from, to) plus who came and who came for the first time. */
    private function window(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visits = $this->visits->filter(fn ($v) => $v['at'] >= $from && $v['at'] < $to);
        $customers = $visits->pluck('c')->unique();
        $new = $customers->filter(fn ($c) => $this->firstVisit()[$c] >= $from);
        $claims = RewardClaim::query()
            ->when($this->program, fn ($q) => $q->where('loyalty_type', $this->program))
            ->whereBetween('claimed_at', [$from, $to])
            ->count();

        return [
            'visits' => $visits,
            'count' => $visits->count(),
            'active' => $customers->count(),
            'new' => $new->count(),
            'returning' => $customers->count() - $new->count(),
            'claims' => $claims,
        ];
    }

    private function firstVisit(): array
    {
        return $this->firstVisit ??= $this->visits
            ->groupBy('c')
            ->map(fn ($v) => $v->first()['at'])
            ->all();
    }

    private ?array $firstVisit = null;

    private function kpis(array $cur, array $prev): array
    {
        $avg = fn ($w) => $w['active'] ? $w['count'] / $w['active'] : 0;
        $rate = fn ($w) => $w['active'] ? $w['returning'] / $w['active'] * 100 : 0;

        return [
            'visits' => [$cur['count'], $prev['count']],
            'active' => [$cur['active'], $prev['active']],
            'new' => [$cur['new'], $prev['new']],
            'returning_rate' => [$rate($cur), $rate($prev)],
            'avg_visits' => [$avg($cur), $avg($prev)],
            'claims' => [$cur['claims'], $prev['claims']],
        ];
    }

    /** Day buckets up to 31 days, weeks up to half a year, months beyond. */
    private function buckets(): array
    {
        $unit = $this->days <= 31 ? 'day' : ($this->days <= 186 ? 'week' : 'month');
        $start = match ($unit) {
            'day' => $this->from,
            'week' => $this->from->startOfWeek(),
            'month' => $this->from->startOfMonth(),
        };

        $buckets = [];
        for ($t = $start; $t <= $this->now; $t = $t->add(1, $unit)) {
            $buckets[] = [
                'from' => $t,
                'to' => $t->add(1, $unit),
                'label' => match ($unit) {
                    'day' => $t->translatedFormat('d M'),
                    'week' => $t->translatedFormat('d M'),
                    'month' => $t->translatedFormat('M Y'),
                },
            ];
        }

        return $buckets;
    }

    private function trend(): array
    {
        $buckets = $this->buckets();
        $programs = $this->program ? [$this->program] : array_keys(self::PROGRAMS);
        $claims = RewardClaim::query()
            ->when($this->program, fn ($q) => $q->where('loyalty_type', $this->program))
            ->where('claimed_at', '>=', $buckets[0]['from'])
            ->pluck('claimed_at');
        $rows = [];

        foreach ($buckets as $b) {
            $in = $this->visits->filter(fn ($v) => $v['at'] >= $b['from'] && $v['at'] < $b['to']);
            $customers = $in->pluck('c')->unique();
            $new = $customers->filter(fn ($c) => $this->firstVisit()[$c] >= $b['from'])->count();
            // Same-length slot one period earlier, for the comparison line.
            $prevFrom = $b['from']->subDays($this->days);
            $prevTo = $b['to']->subDays($this->days);

            $rows[] = [
                'label' => $b['label'],
                'total' => $in->count(),
                'previous' => $this->visits->filter(fn ($v) => $v['at'] >= $prevFrom && $v['at'] < $prevTo)->count(),
                'programs' => collect($programs)->mapWithKeys(fn ($p) => [
                    $p => $in->filter(fn ($v) => in_array($p, $v['t'], true))->count(),
                ])->all(),
                'new' => $new,
                'returning' => $customers->count() - $new,
                'claims' => $claims->filter(fn ($at) => $at >= $b['from'] && $at < $b['to'])->count(),
            ];
        }

        return [
            'unit' => $this->days <= 31 ? 'hari' : ($this->days <= 186 ? 'minggu' : 'bulan'),
            'programs' => $programs,
            'rows' => $rows,
        ];
    }

    /** Visits by weekday × hour, for staffing. */
    private function heatmap(Collection $visits): array
    {
        $grid = [];
        foreach ($visits as $v) {
            $grid[$v['at']->dayOfWeekIso - 1][$v['at']->hour] = ($grid[$v['at']->dayOfWeekIso - 1][$v['at']->hour] ?? 0) + 1;
        }

        $hours = $visits->map(fn ($v) => $v['at']->hour);
        $byDay = collect(range(0, 6))->map(fn ($d) => array_sum($grid[$d] ?? []));
        $byHour = collect(range(0, 23))->mapWithKeys(fn ($h) => [$h => collect($grid)->sum(fn ($row) => $row[$h] ?? 0)]);

        return [
            'grid' => $grid,
            'from_hour' => $hours->min() ?? 8,
            'to_hour' => $hours->max() ?? 20,
            'max' => collect($grid)->flatten()->max() ?? 0,
            'by_day' => $byDay->all(),
            'busiest_day' => $visits->isEmpty() ? null : $byDay->search($byDay->max()),
            'busiest_hour' => $visits->isEmpty() ? null : $byHour->search($byHour->max()),
            'quietest_day' => $visits->isEmpty() ? null : $byDay->search($byDay->min()),
        ];
    }

    /**
     * Monthly retention: of the customers whose first visit fell in a month,
     * the share that came back in each following month.
     */
    private function cohorts(): array
    {
        $months = $this->visits
            ->groupBy('c')
            ->map(fn ($v) => $v->map(fn ($x) => $x['at']->format('Y-m'))->unique()->values());

        $thisMonth = $this->now->startOfMonth();
        $rows = [];

        for ($i = 6; $i >= 1; $i--) {
            $cohort = $thisMonth->subMonths($i);
            $key = $cohort->format('Y-m');
            $members = $months->filter(fn ($m) => $m->first() === $key);

            if ($members->isEmpty()) {
                continue;
            }

            $cells = [];
            for ($k = 1; $k <= 5; $k++) {
                $target = $cohort->addMonths($k);
                $cells[] = $target >= $thisMonth
                    ? null // month not finished yet
                    : round($members->filter(fn ($m) => $m->contains($target->format('Y-m')))->count() / $members->count() * 100);
            }

            $rows[] = ['label' => $cohort->translatedFormat('M Y'), 'size' => $members->count(), 'cells' => $cells];
        }

        $firstMonth = collect($rows)->pluck('cells.0')->filter(fn ($c) => $c !== null);

        return ['rows' => $rows, 'avg_m1' => $firstMonth->isEmpty() ? null : round($firstMonth->avg())];
    }

    /** Where every customer stands today, by days since their last visit. */
    private function segments(): array
    {
        $last = $this->visits->groupBy('c')->map(fn ($v) => $v->last()['at']);
        $first = $this->firstVisit();

        $segments = [
            'new' => ['label' => 'Baru', 'hint' => 'Kunjungan pertama ≤ 30 hari lalu', 'color' => 'info', 'count' => 0],
            'active' => ['label' => 'Aktif', 'hint' => 'Datang lagi dalam 30 hari terakhir', 'color' => 'success', 'count' => 0],
            'cooling' => ['label' => 'Mulai jarang', 'hint' => '31–60 hari tidak datang', 'color' => 'warning', 'count' => 0],
            'at_risk' => ['label' => 'Berisiko hilang', 'hint' => '61–90 hari tidak datang', 'color' => 'danger', 'count' => 0],
            'lost' => ['label' => 'Tidak aktif', 'hint' => 'Lebih dari 90 hari tidak datang', 'color' => 'gray', 'count' => 0],
        ];

        foreach ($last as $customer => $at) {
            $gap = $at->diffInDays($this->now);
            $key = match (true) {
                $gap <= 30 && $first[$customer]->diffInDays($this->now) <= 30 => 'new',
                $gap <= 30 => 'active',
                $gap <= 60 => 'cooling',
                $gap <= 90 => 'at_risk',
                default => 'lost',
            };
            $segments[$key]['count']++;
        }

        return ['total' => $last->count(), 'items' => $segments];
    }

    /** How many times each customer has ever come. */
    private function frequency(): array
    {
        $counts = $this->visits->countBy('c');
        $buckets = ['1x' => [1, 1], '2x' => [2, 2], '3–5x' => [3, 5], '6–10x' => [6, 10], '11x+' => [11, PHP_INT_MAX]];

        return [
            'total' => $counts->count(),
            'buckets' => collect($buckets)->map(fn ($r) => $counts->filter(fn ($n) => $n >= $r[0] && $n <= $r[1])->count())->all(),
        ];
    }

    /** Program share of visits and how often a wash comes with a coffee. */
    private function mix(Collection $visits): array
    {
        $washes = $visits->filter(fn ($v) => array_intersect(['carwash', 'motorwash'], $v['t']));
        $withCoffee = $washes->filter(fn ($v) => in_array('coffeeshop', $v['t'], true));

        return [
            'programs' => collect(self::PROGRAMS)->map(fn ($label, $p) => $visits->filter(fn ($v) => in_array($p, $v['t'], true))->count())->all(),
            'washes' => $washes->count(),
            'cross_sell' => $washes->count() ? round($withCoffee->count() / $washes->count() * 100, 1) : null,
        ];
    }

    private function rewards(): array
    {
        $programs = $this->program ? [$this->program] : array_keys(self::PROGRAMS);

        return [
            'claimed' => RewardClaim::query()
                ->whereIn('loyalty_type', $programs)
                ->where('claimed_at', '>=', $this->from)
                ->selectRaw('loyalty_type, count(*) as total')
                ->groupBy('loyalty_type')
                ->pluck('total', 'loyalty_type')
                ->all(),
            'pending' => collect($programs)->mapWithKeys(fn ($p) => [$p => Customer::withPendingReward($p)->count()])->all(),
            'top' => RewardClaim::query()
                ->whereIn('loyalty_type', $programs)
                ->where('claimed_at', '>=', $this->from)
                ->selectRaw('reward, count(*) as total')
                ->groupBy('reward')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'reward')
                ->all(),
        ];
    }

    private function topCustomers(Collection $visits): array
    {
        $top = $visits->countBy('c')->sortDesc()->take(10);
        $people = Customer::with('user:id,name,phone')->whereIn('id', $top->keys())->get()->keyBy('id');
        $last = $this->visits->groupBy('c')->map(fn ($v) => $v->last()['at']);
        $total = $this->visits->countBy('c');

        return $top->map(fn ($n, $c) => [
            'name' => $people[$c]->user?->name ?? '-',
            'phone' => $people[$c]->user?->phone,
            'visits' => $n,
            'lifetime' => $total[$c],
            'last' => $last[$c]->locale('id')->diffForHumans(),
        ])->values()->all();
    }

    /** Plain-language takeaways for an owner who will not read every chart. */
    private function insights(array $r): array
    {
        $out = [];
        [$visits, $prevVisits] = $r['kpi']['visits'];

        if ($r['has_previous'] && $prevVisits > 0) {
            $change = ($visits - $prevVisits) / $prevVisits * 100;
            $out[] = [
                'tone' => $change >= 0 ? 'success' : 'danger',
                'icon' => $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'text' => sprintf('Kunjungan %s %s%% dibanding %d hari sebelumnya (%s → %s).',
                    $change >= 0 ? 'naik' : 'turun', number_format(abs($change), 1, ',', '.'), $this->days,
                    number_format($prevVisits, 0, ',', '.'), number_format($visits, 0, ',', '.')),
            ];
        }

        $h = $r['heatmap'];
        if ($h['busiest_day'] !== null) {
            $out[] = [
                'tone' => 'info',
                'icon' => 'heroicon-m-clock',
                'text' => sprintf('Paling ramai hari %s, sekitar jam %02d.00–%02d.00. Paling sepi hari %s, cocok untuk promo.',
                    $this->dayName($h['busiest_day']), $h['busiest_hour'], $h['busiest_hour'] + 1, $this->dayName($h['quietest_day'])),
            ];
        }

        $f = $r['frequency'];
        if ($f['total'] > 0) {
            $once = $f['buckets']['1x'] / $f['total'] * 100;
            $out[] = [
                'tone' => $once > 50 ? 'warning' : 'info',
                'icon' => 'heroicon-m-user-minus',
                'text' => sprintf('%s%% customer hanya datang sekali dan belum kembali (%s orang). Ini peluang terbesar untuk dijadikan langganan.',
                    number_format($once, 0, ',', '.'), number_format($f['buckets']['1x'], 0, ',', '.')),
            ];
        }

        $s = $r['segments']['items'];
        $risk = $s['cooling']['count'] + $s['at_risk']['count'];
        if ($risk > 0) {
            $out[] = [
                'tone' => 'warning',
                'icon' => 'heroicon-m-exclamation-triangle',
                'text' => sprintf('%s customer sudah 31–90 hari tidak datang. Kirim broadcast pengingat sebelum mereka benar-benar hilang.',
                    number_format($risk, 0, ',', '.')),
            ];
        }

        if ($r['cohorts']['avg_m1'] !== null) {
            $out[] = [
                'tone' => 'info',
                'icon' => 'heroicon-m-arrow-path',
                'text' => sprintf('Rata-rata %d%% customer baru datang lagi di bulan berikutnya.', $r['cohorts']['avg_m1']),
            ];
        }

        if ($r['mix']['cross_sell'] !== null && ! $this->program) {
            $out[] = [
                'tone' => 'info',
                'icon' => 'heroicon-m-shopping-bag',
                'text' => sprintf('%s%% kunjungan cuci juga membeli di Coffee Shop.', number_format($r['mix']['cross_sell'], 1, ',', '.')),
            ];
        }

        $pending = array_sum($r['rewards']['pending']);
        if ($pending > 0) {
            $out[] = [
                'tone' => 'success',
                'icon' => 'heroicon-m-gift',
                'text' => sprintf('%s customer sudah berhak hadiah tapi belum klaim. Ingatkan mereka, hadiah yang belum diklaim mengunci poin.',
                    number_format($pending, 0, ',', '.')),
            ];
        }

        return $out;
    }

    private function dayName(int $index): string
    {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][$index];
    }
}
