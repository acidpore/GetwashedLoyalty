<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('User Account')
                            ->helperText('Link to user account'),
                        
                        Forms\Components\TextInput::make('current_points')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label('Current Points')
                            ->helperText('Active points for rewards'),
                        
                        Forms\Components\TextInput::make('total_visits')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label('Total Visits')
                            ->helperText('Lifetime visit count'),
                        
                        Forms\Components\DateTimePicker::make('last_visit_at')
                            ->label('Last Visit')
                            ->displayFormat('d/m/Y H:i')
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->icon('heroicon-m-phone'),
                
                Tables\Columns\TextColumn::make('current_points')
                    ->label('Points')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= SystemSetting::rewardPointsThreshold() => 'success',
                        $state >= 3 => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('total_visits')
                    ->label('Total Visits')
                    ->sortable()
                    ->icon('heroicon-m-chart-bar'),
                
                Tables\Columns\TextColumn::make('last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->last_visit_at?->format('d/m/Y H:i')),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_reward')
                    ->label('Ready for Reward')
                    ->query(fn (Builder $query): Builder => $query->where('current_points', '>=', SystemSetting::rewardPointsThreshold())),
                
                Tables\Filters\Filter::make('active_customers')
                    ->label('Active (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->where('last_visit_at', '>=', now()->subDays(30))),
                
                Tables\Filters\SelectFilter::make('points_range')
                    ->label('Points Range')
                    ->options(function () {
                        $threshold = SystemSetting::rewardPointsThreshold();
                        return [
                            '0-2' => '0-2 points',
                            '3-4' => '3-4 points',
                            "{$threshold}+" => "{$threshold}+ points (Reward)",
                        ];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $threshold = SystemSetting::rewardPointsThreshold();
                        return match ($data['value'] ?? null) {
                            '0-2' => $query->whereBetween('current_points', [0, 2]),
                            '3-4' => $query->whereBetween('current_points', [3, 4]),
                            "{$threshold}+" => $query->where('current_points', '>=', $threshold),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reset_points')
                    ->label('Reset Points')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Customer $record) => $record->resetPoints())
                    ->visible(fn (Customer $record) => $record->current_points > 0),
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_template')
                    ->label('Download CSV Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function () {
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="customer_import_template.csv"',
                        ];
                        
                        $callback = function () {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Phone', 'Name', 'Current Points', 'Total Visits']);
                            fputcsv($handle, ['081234567890', 'John Doe', '3', '5']);
                            fputcsv($handle, ['082345678901', 'Jane Smith', '0', '2']);
                            fclose($handle);
                        };
                        
                        return response()->stream($callback, 200, $headers);
                    }),
                    
                Tables\Actions\Action::make('import')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Upload CSV File')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                            ->maxSize(2048)
                            ->required()
                            ->helperText('Format: Phone, Name, Current Points, Total Visits'),
                    ])
                    ->action(function (array $data) {
                        $file = storage_path('app/' . $data['file']);
                        
                        $request = new \Illuminate\Http\Request();
                        $request->files->set('file', new \Illuminate\Http\UploadedFile($file, basename($file), 'text/csv', null, true));
                        
                        $controller = new \App\Http\Controllers\CustomerImportController();
                        $response = $controller->__invoke($request);
                        $result = $response->getData();
                        
                        if ($result->success) {
                            \Filament\Notifications\Notification::make()
                                ->title('Import Successful')
                                ->body($result->message)
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Import Failed')
                                ->body($result->message)
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('admin.export.customers'))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_visit_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
