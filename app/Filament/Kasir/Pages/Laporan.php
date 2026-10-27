<?php

namespace App\Filament\Kasir\Pages;

use App\Services\LatteReport;
use App\Services\LatteReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Laporan extends Page
{
    protected static ?string $slug = 'laporan';

    protected static ?string $title = 'Laporan';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Utama';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.kasir.laporan';

    public string $from = '';

    public string $to = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function preset(string $range): void
    {
        [$from, $to] = match ($range) {
            'today' => [today(), today()],
            '7d' => [today()->subDays(6), today()],
            '30d' => [today()->subDays(29), today()],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            default => [today()->startOfMonth(), today()],
        };
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    /** Swaps a reversed range and caps it at a year so one click can't scan the whole table. */
    private function range(): array
    {
        $from = CarbonImmutable::parse($this->from ?: today());
        $to = CarbonImmutable::parse($this->to ?: today());
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, min($to, $from->addYear()->subDay())];
    }

    private function report(): array
    {
        return LatteReport::build(...$this->range());
    }

    private function fileName(string $ext): string
    {
        [$from, $to] = $this->range();

        return "laporan-latte-{$from->format('Ymd')}-{$to->format('Ymd')}.{$ext}";
    }

    public function downloadPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('reports.latte-pdf', ['r' => $this->report()])->setPaper('a4');

        return response()->streamDownload(fn () => print($pdf->output()), $this->fileName('pdf'));
    }

    public function downloadXlsx(): StreamedResponse
    {
        $path = LatteReportExport::xlsx($this->report());

        return response()->streamDownload(function () use ($path) {
            readfile($path);
            @unlink($path);
        }, $this->fileName('xlsx'));
    }

    protected function getViewData(): array
    {
        return ['r' => $this->report()];
    }
}
