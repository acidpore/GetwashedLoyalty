<?php

namespace App\Filament\Resources\Carwash;

use App\Filament\Resources\Carwash\CarwashCustomerResource\Pages;
use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CarwashCustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?string $navigationGroup = 'Car Wash';
    protected static ?int $navigationSort = 30;
    protected static ?string $modelLabel = 'Car Wash Customer';
    protected static ?string $pluralModelLabel = 'Car Wash Customers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Customer Information')
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('name')
                        ->label('Name')
                        ->content(fn ($record) => $record->user?->name ?? '-'),
                    Forms\Components\Placeholder::make('phone')
                        ->label('Phone')
                        ->content(fn ($record) => $record->user?->phone ?? '-'),
                ]),
            
            Forms\Components\Section::make('Car Wash Loyalty')
                ->schema([
                    Forms\Components\TextInput::make('carwash_points')
                        ->label('Points')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('carwash_total_visits')
                        ->label('Total Visits')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Placeholder::make('carwash_last_visit_at_display')
                        ->label('Last Visit')
                        ->content(fn ($record) => $record->carwash_last_visit_at?->format('d M Y H:i') ?? '-')
                        ->hint('Auto-recorded'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('carwash_points')
                    ->label('Points')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state >= SystemSetting::carwashRewardThreshold() ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('carwash_total_visits')
                    ->label('Total Visits')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('carwash_last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('ready_for_reward')
                    ->label('Ready for Reward')
                    ->query(fn ($query) => $query->where('carwash_points', '>=', SystemSetting::carwashRewardThreshold())),
                
                Tables\Filters\Filter::make('active_customers')
                    ->label('Active (Last 30 Days)')
                    ->query(fn ($query) => $query->where('carwash_last_visit_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_point')
                    ->label('Add Point')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function (Customer $record) {
                        $record->addPoints('carwash');
                    })
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Upload CSV File')
                            ->acceptedFileTypes(['text/csv', 'text/plain'])
                            ->required()
                            ->helperText('Format: Name, Phone, Points, Total Visits'),
                    ])
                    ->action(function (array $data) {
                        $file = storage_path('app/public/' . $data['file']);
                        $imported = 0;
                        $errors = [];

                        if (($handle = fopen($file, 'r')) !== false) {
                            $header = fgetcsv($handle);
                            
                            while (($row = fgetcsv($handle)) !== false) {
                                try {
                                    $user = \App\Models\User::firstOrCreate(
                                        ['phone' => $row[1]],
                                        ['name' => $row[0]]
                                    );
                                    
                                    $customer = Customer::firstOrCreate(['user_id' => $user->id]);
                                    $customer->carwash_points = $row[2] ?? 0;
                                    $customer->carwash_total_visits = $row[3] ?? 0;
                                    $customer->save();
                                    
                                    $imported++;
                                } catch (\Exception $e) {
                                    $errors[] = "Row error: {$row[0]} - {$e->getMessage()}";
                                }
                            }
                            fclose($handle);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Import Completed')
                            ->body("Imported {$imported} customers" . (count($errors) > 0 ? ". Errors: " . implode(', ', array_slice($errors, 0, 3)) : ''))
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\Radio::make('format')
                            ->label('Export Format')
                            ->options([
                                'standard' => 'Standard Export - Full customer data',
                                'maxchat' => 'MaxChat Template - Phone numbers only',
                            ])
                            ->descriptions([
                                'standard' => 'Export car wash customer details (Name, Phone, Points, Total Visits, Last Visit)',
                                'maxchat' => 'Export phone numbers only for MaxChat WhatsApp broadcast',
                            ])
                            ->default('standard')
                            ->required()
                            ->inline()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        $customers = Customer::with('user')
                            ->where('carwash_total_visits', '>', 0)
                            ->orderBy('carwash_last_visit_at', 'desc')
                            ->get();

                        if ($data['format'] === 'maxchat') {
                            // MaxChat Template Export (phone only)
                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="carwash_maxchat_broadcast_' . now()->format('Y-m-d') . '.csv"',
                            ];

                            $callback = function () use ($customers) {
                                $handle = fopen('php://output', 'w');
                                fputcsv($handle, ['phone']);
                                
                                foreach ($customers as $customer) {
                                    if ($customer->user && $customer->user->phone) {
                                        fputcsv($handle, [$customer->user->phone]);
                                    }
                                }
                                
                                fclose($handle);
                            };
                        } else {
                            // Standard Export (full data)
                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="carwash_customers_' . now()->format('Y-m-d') . '.csv"',
                            ];

                            $callback = function () use ($customers) {
                                $handle = fopen('php://output', 'w');
                                fputcsv($handle, ['Name', 'Phone', 'Points', 'Total Visits', 'Last Visit']);
                                
                                foreach ($customers as $customer) {
                                    fputcsv($handle, [
                                        $customer->user->name,
                                        $customer->user->phone,
                                        $customer->carwash_points,
                                        $customer->carwash_total_visits,
                                        $customer->carwash_last_visit_at?->format('Y-m-d H:i:s') ?? '',
                                    ]);
                                }
                                
                                fclose($handle);
                            };
                        }

                        return response()->stream($callback, 200, $headers);
                    }),
                    
                Tables\Actions\Action::make('export_pdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn () => route('pdf.customers.carwash'))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('carwash_last_visit_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('carwash_total_visits', '>', 0));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarwashCustomers::route('/'),
            'edit' => Pages\EditCarwashCustomer::route('/{record}/edit'),
        ];
    }
}
