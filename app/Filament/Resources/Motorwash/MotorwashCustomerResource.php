<?php

namespace App\Filament\Resources\Motorwash;

use App\Filament\Resources\Motorwash\MotorwashCustomerResource\Pages;
use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MotorwashCustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?string $navigationGroup = 'Motor Wash';
    protected static ?int $navigationSort = 40;
    protected static ?string $modelLabel = 'Motor Wash Customer';
    protected static ?string $pluralModelLabel = 'Motor Wash Customers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Customer Information')
                ->schema([
                    Forms\Components\TextInput::make('user.name')
                        ->label('Name')
                        ->required(),
                    Forms\Components\TextInput::make('user.phone')
                        ->label('Phone')
                        ->tel()
                        ->required(),
                ]),
            
            Forms\Components\Section::make('Motor Wash Loyalty')
                ->schema([
                    Forms\Components\TextInput::make('motorwash_points')
                        ->label('Points')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('motorwash_total_visits')
                        ->label('Total Visits')
                        ->numeric()
                        ->default(0),
                    Forms\Components\DateTimePicker::make('motorwash_last_visit_at')
                        ->label('Last Visit'),
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
                
                Tables\Columns\TextColumn::make('motorwash_points')
                    ->label('Points')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state >= SystemSetting::motorwashRewardThreshold() ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('motorwash_total_visits')
                    ->label('Total Visits')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('motorwash_last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('ready_for_reward')
                    ->label('Ready for Reward')
                    ->query(fn ($query) => $query->where('motorwash_points', '>=', SystemSetting::motorwashRewardThreshold())),
                
                Tables\Filters\Filter::make('active_customers')
                    ->label('Active (Last 30 Days)')
                    ->query(fn ($query) => $query->where('motorwash_last_visit_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_point')
                    ->label('Add Point')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function (Customer $record) {
                        $record->addPoints('motorwash');
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
                                    $customer->motorwash_points = $row[2] ?? 0;
                                    $customer->motorwash_total_visits = $row[3] ?? 0;
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
                    ->action(function () {
                        $customers = Customer::with('user')
                            ->where('motorwash_total_visits', '>', 0)
                            ->orderBy('motorwash_last_visit_at', 'desc')
                            ->get();

                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="motorwash_customers_' . now()->format('Y-m-d') . '.csv"',
                        ];

                        $callback = function () use ($customers) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Name', 'Phone', 'Points', 'Total Visits', 'Last Visit']);
                            
                            foreach ($customers as $customer) {
                                fputcsv($handle, [
                                    $customer->user->name,
                                    $customer->user->phone,
                                    $customer->motorwash_points,
                                    $customer->motorwash_total_visits,
                                    $customer->motorwash_last_visit_at?->format('Y-m-d H:i:s') ?? '',
                                ]);
                            }
                            
                            fclose($handle);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                    
                Tables\Actions\Action::make('export_pdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn () => route('pdf.customers.motorwash'))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('motorwash_last_visit_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('motorwash_total_visits', '>', 0));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMotorwashCustomers::route('/'),
            'edit' => Pages\EditMotorwashCustomer::route('/{record}/edit'),
        ];
    }
}
