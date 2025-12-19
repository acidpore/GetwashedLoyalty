<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerImportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        $header = fgetcsv($handle);
        
        $expectedHeaders = ['Phone', 'Name', 'CW Points', 'CS Points', 'MW Points', 'CW Visits', 'CS Visits', 'MW Visits'];
        if ($header !== $expectedHeaders) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Invalid CSV format. Expected headers: ' . implode(', ', $expectedHeaders)
            ], 422);
        }

        $imported = 0;
        $updated = 0;
        $errors = [];
        $row = 1;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                
                if (count($data) < 8) {
                    $errors[] = "Row {$row}: Incomplete data (expected 8 columns)";
                    continue;
                }

                [$phone, $name, $cwPoints, $csPoints, $mwPoints, $cwVisits, $csVisits, $mwVisits] = $data;

                $validator = Validator::make([
                    'phone' => $phone,
                    'name' => $name,
                    'cw_points' => $cwPoints,
                    'cs_points' => $csPoints,
                    'mw_points' => $mwPoints,
                    'cw_visits' => $cwVisits,
                    'cs_visits' => $csVisits,
                    'mw_visits' => $mwVisits,
                ], [
                    'phone' => 'required|string|max:20',
                    'name' => 'required|string|max:255',
                    'cw_points' => 'required|integer|min:0',
                    'cs_points' => 'required|integer|min:0',
                    'mw_points' => 'required|integer|min:0',
                    'cw_visits' => 'required|integer|min:0',
                    'cs_visits' => 'required|integer|min:0',
                    'mw_visits' => 'required|integer|min:0',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$row}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                $user = User::where('phone', $phone)->first();
                
                if (!$user) {
                    $user = User::create([
                        'name' => $name,
                        'phone' => $phone,
                        'email' => null,
                        'password' => Hash::make('password123'),
                        'role' => 'customer',
                    ]);
                    
                    Customer::create([
                        'user_id' => $user->id,
                        'carwash_points' => (int)$cwPoints,
                        'coffeeshop_points' => (int)$csPoints,
                        'motorwash_points' => (int)$mwPoints,
                        'carwash_total_visits' => (int)$cwVisits,
                        'coffeeshop_total_visits' => (int)$csVisits,
                        'motorwash_total_visits' => (int)$mwVisits,
                        'carwash_last_visit_at' => (int)$cwVisits > 0 ? now() : null,
                        'coffeeshop_last_visit_at' => (int)$csVisits > 0 ? now() : null,
                        'motorwash_last_visit_at' => (int)$mwVisits > 0 ? now() : null,
                    ]);
                    
                    $imported++;
                } else {
                    $user->update(['name' => $name]);
                    
                    $customer = Customer::where('user_id', $user->id)->first();
                    if ($customer) {
                        $customer->update([
                            'carwash_points' => (int)$cwPoints,
                            'coffeeshop_points' => (int)$csPoints,
                            'motorwash_points' => (int)$mwPoints,
                            'carwash_total_visits' => (int)$cwVisits,
                            'coffeeshop_total_visits' => (int)$csVisits,
                            'motorwash_total_visits' => (int)$mwVisits,
                        ]);
                    } else {
                        Customer::create([
                            'user_id' => $user->id,
                            'carwash_points' => (int)$cwPoints,
                            'coffeeshop_points' => (int)$csPoints,
                            'motorwash_points' => (int)$mwPoints,
                            'carwash_total_visits' => (int)$cwVisits,
                            'coffeeshop_total_visits' => (int)$csVisits,
                            'motorwash_total_visits' => (int)$mwVisits,
                            'carwash_last_visit_at' => (int)$cwVisits > 0 ? now() : null,
                            'coffeeshop_last_visit_at' => (int)$csVisits > 0 ? now() : null,
                            'motorwash_last_visit_at' => (int)$mwVisits > 0 ? now() : null,
                        ]);
                    }
                    
                    $updated++;
                }
            }

            DB::commit();
            fclose($handle);

            $message = "Import completed! ";
            if ($imported > 0) $message .= "{$imported} new customer(s) created. ";
            if ($updated > 0) $message .= "{$updated} customer(s) updated. ";
            if (count($errors) > 0) $message .= count($errors) . " error(s) occurred.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => [
                    'imported' => $imported,
                    'updated' => $updated,
                    'errors' => count($errors),
                ],
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
