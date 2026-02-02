<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExportController extends Controller
{
    /**
     * Export all customers with full details (standard format)
     */
    public function exportStandard(): StreamedResponse
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
            
            // Header Row - All service-specific columns
            fputcsv($handle, [
                'Name', 'Phone',
                'CW Points', 'CS Points', 'MW Points',
                'CW Visits', 'CS Visits', 'MW Visits',
                'Last CW', 'Last CS', 'Last MW',
                'Joined Date'
            ]);

            // Data Rows (Chunked for memory efficiency)
            Customer::with('user')->chunk(100, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->user->name ?? '-',
                        $customer->user->phone ?? '-',
                        $customer->carwash_points,
                        $customer->coffeeshop_points,
                        $customer->motorwash_points,
                        $customer->carwash_total_visits,
                        $customer->coffeeshop_total_visits,
                        $customer->motorwash_total_visits,
                        $customer->carwash_last_visit_at?->format('Y-m-d') ?? '-',
                        $customer->coffeeshop_last_visit_at?->format('Y-m-d') ?? '-',
                        $customer->motorwash_last_visit_at?->format('Y-m-d') ?? '-',
                        $customer->created_at->format('Y-m-d'),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export phone numbers only for MaxChat WhatsApp broadcast
     */
    public function exportMaxChat(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="maxchat_broadcast_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            
            // Header: single column 'phone'
            fputcsv($handle, ['phone']);

            // Data: phone numbers only
            Customer::with('user')->chunk(100, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    if ($customer->user && $customer->user->phone) {
                        fputcsv($handle, [$customer->user->phone]);
                    }
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Legacy support - redirect to standard export
     */
    public function __invoke(): StreamedResponse
    {
        return $this->exportStandard();
    }
}
