<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/** Writes a LatteReport as a multi-sheet workbook. Numbers stay numeric so owners can pivot them. */
class LatteReportExport
{
    public static function xlsx(array $r): string
    {
        $path = tempnam(sys_get_temp_dir(), 'latte') . '.xlsx';
        $head = (new Style())->setFontBold()->setBackgroundColor('FBBF24');
        $money = (new Style())->setFormat('#,##0');
        $pct = (new Style())->setFormat('0.0"%"');

        $first = true;
        $writer = new Writer();
        $writer->openToFile($path);

        $sheet = function (string $name, array $header, iterable $rows, array $styles = [], float $width = 16) use ($writer, $head, &$first) {
            $first ? $first = false : $writer->addNewSheetAndMakeItCurrent();
            $writer->getCurrentSheet()->setName($name);
            $writer->getCurrentSheet()->setColumnWidthForRange($width, 1, max(1, count($header)));
            $writer->addRow(Row::fromValues($header, $head));
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValuesWithStyles(array_values($row), null, $styles));
            }
        };

        $s = $r['summary'];
        $p = $r['previous'];
        $change = fn ($a, $b) => ($c = LatteReport::change($a, $b)) === null ? '-' : round($c, 1);

        $sheet('Ringkasan', ['Metrik', 'Periode ini', 'Periode sebelumnya', 'Perubahan %'], [
            ['Periode', $r['from']->format('d/m/Y') . ' - ' . $r['to']->format('d/m/Y'), $r['prev_from']->format('d/m/Y') . ' - ' . $r['prev_to']->format('d/m/Y'), ''],
            ['Omzet kotor', $s['gross'], $p['gross'], $change($s['gross'], $p['gross'])],
            ['Diskon', $s['discount'], $p['discount'], $change($s['discount'], $p['discount'])],
            ['Omzet bersih', $s['net'], $p['net'], $change($s['net'], $p['net'])],
            ['Transaksi', $s['orders'], $p['orders'], $change($s['orders'], $p['orders'])],
            ['Rata-rata / transaksi', $s['avg'], $p['avg'], $change($s['avg'], $p['avg'])],
            ['Item terjual', $s['items'], '', ''],
            ['Transaksi dibatalkan', $s['voids'], '', ''],
            ['Nilai dibatalkan', $s['void_value'], '', ''],
            ['Transaksi member', $r['loyalty']['member_orders'], '', ''],
            ['Member unik', $r['loyalty']['unique_members'], '', ''],
            ['Member kembali (>1x)', $r['loyalty']['repeat_members'], '', ''],
            ['Hadiah diklaim', $r['loyalty']['rewards'], '', ''],
        ], [1 => $money, 2 => $money], 26);

        $sheet('Insight', ['Temuan', 'Penjelasan'], collect($r['insights'])->map(fn ($i) => [$i[1], $i[2]]), [], 60);

        $sheet('Harian', ['Tanggal', 'Transaksi', 'Omzet bersih', 'Diskon', 'Rata-rata'],
            $r['daily']->map(fn ($d) => [$d['date']->format('Y-m-d'), $d['orders'], $d['revenue'], $d['discount'], $d['avg']]),
            [2 => $money, 3 => $money, 4 => $money]);

        $sheet('Menu', ['Menu', 'Kategori', 'Terjual', 'Pendapatan', 'Kontribusi %', 'Kumulatif %'],
            $r['products']->map(fn ($x) => [$x['name'], $x['category'], $x['qty'], $x['revenue'], round($x['share'], 1), round($x['cumulative'], 1)]),
            [3 => $money, 4 => $pct, 5 => $pct], 18);

        $sheet('Kategori', ['Kategori', 'Terjual', 'Pendapatan', 'Kontribusi %'],
            $r['categories']->map(fn ($x) => [$x['name'], $x['qty'], $x['revenue'], round($x['share'], 1)]), [2 => $money, 3 => $pct]);

        $sheet('Jam & Hari', ['Jam / Hari', 'Transaksi', 'Omzet', 'Rata-rata omzet per hari'],
            $r['hours']->map(fn ($h) => [sprintf('%02d.00', $h['hour']), $h['orders'], $h['revenue'], ''])
                ->concat($r['weekdays']->map(fn ($d) => [$d['day'], $d['orders'], $d['revenue'], $d['avg_revenue']])),
            [2 => $money, 3 => $money], 20);

        $sheet('Pembayaran', ['Metode', 'Transaksi', 'Omzet'], $r['methods'], [2 => $money]);

        $sheet('Kasir', ['Kasir', 'Transaksi', 'Omzet', 'Rata-rata', 'Dibatalkan'], $r['cashiers'], [2 => $money, 3 => $money]);

        $sheet('Promo', ['Promo', 'Transaksi', 'Total diskon', 'Omzet'], $r['promos'], [2 => $money, 3 => $money]);

        $sheet('Shift', ['Kasir', 'Buka', 'Tutup', 'Modal', 'Seharusnya', 'Dihitung', 'Selisih', 'Catatan'],
            $r['shifts']->map(fn ($x) => [$x['cashier'], $x['opened']->format('Y-m-d H:i'), $x['closed']->format('Y-m-d H:i'), $x['opening'], $x['expected'], $x['counted'], $x['difference'], $x['note'] ?? '']),
            [3 => $money, 4 => $money, 5 => $money, 6 => $money]);

        $sheet('Pembatalan', ['No.', 'Waktu', 'Kasir', 'Nilai', 'Alasan'],
            $r['voids']->map(fn ($v) => [$v['number'], $v['at']->format('Y-m-d H:i'), $v['cashier'] ?? '-', $v['total'], $v['reason']]), [3 => $money], 20);

        $sheet('Stok Menipis', ['Bahan', 'Stok', 'Minimum', 'Satuan'],
            $r['low_stock']->map(fn ($i) => [$i->name, (float) $i->stock, (float) $i->min_stock, $i->unit]));

        $writer->close();

        return $path;
    }
}
