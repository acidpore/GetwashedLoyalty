<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            
            // Header Row
            fputcsv($handle, ['Name', 'Phone', 'Current Points', 'Total Visits', 'Last Visit', 'Joined Date']);

            // Data Rows (Chunked for memory efficiency)
            Customer::with('user')->chunk(100, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->user->name ?? '-',
                        $customer->user->phone ?? '-',
                        $customer->current_points,
                        $customer->total_visits,
                        $customer->last_visit_at?->format('Y-m-d H:i:s') ?? '-',
                        $customer->created_at->format('Y-m-d'),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
