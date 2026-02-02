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
    protected static ?string $navigationLabel = 'All Customers';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 60;
    protected static ?string $modelLabel = 'Customer';

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
                            ->helperText('Link to user account')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make('Car Wash')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                Forms\Components\TextInput::make('carwash_points')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label('Points')
                                    ->suffix('pts'),
                                
                                Forms\Components\TextInput::make('carwash_total_visits')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label('Total Visits'),
                                
                                Forms\Components\DateTimePicker::make('carwash_last_visit_at')
                                    ->label('Last Visit')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hint('Auto-recorded'),
                            ]),

                        Forms\Components\Section::make('Motor Wash')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Forms\Components\TextInput::make('motorwash_points')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label('Points')
                                    ->suffix('pts'),

                                Forms\Components\TextInput::make('motorwash_total_visits')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label('Total Visits'),

                                Forms\Components\DateTimePicker::make('motorwash_last_visit_at')
                                    ->label('Last Visit')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hint('Auto-recorded'),
                            ]),

                        Forms\Components\Section::make('Coffee Shop')
                            ->icon('heroicon-o-beaker')
                            ->schema([
                                Forms\Components\TextInput::make('coffeeshop_points')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label('Points')
                                    ->suffix('pts'),
                                
                                Forms\Components\TextInput::make('coffeeshop_total_visits')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label('Total Visits'),
                                
                                Forms\Components\DateTimePicker::make('coffeeshop_last_visit_at')
                                    ->label('Last Visit')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hint('Auto-recorded'),
                            ]),
                    ]),
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
                
                Tables\Columns\TextColumn::make('carwash_points')
                    ->label('Car Wash')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state >= SystemSetting::carwashRewardThreshold() ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('coffeeshop_points')
                    ->label('Coffee Shop')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state >= SystemSetting::coffeeshopRewardThreshold() ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('motorwash_points')
                    ->label('Motor Wash')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state >= SystemSetting::motorwashRewardThreshold() ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('carwash_total_visits')
                    ->label('CW Visits')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('coffeeshop_total_visits')
                    ->label('CS Visits')
                    ->sortable(),

                Tables\Columns\TextColumn::make('motorwash_total_visits')
                    ->label('MW Visits')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('carwash_last_visit_at')
                    ->label('Last CW')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('coffeeshop_last_visit_at')
                    ->label('Last CS')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('motorwash_last_visit_at')
                    ->label('Last MW')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_carwash_reward')
                    ->label('Ready for Car Wash Reward')
                    ->query(fn (Builder $query): Builder => $query->where('carwash_points', '>=', SystemSetting::carwashRewardThreshold())),
                
                Tables\Filters\Filter::make('has_coffeeshop_reward')
                    ->label('Ready for Coffee Shop Reward')
                    ->query(fn (Builder $query): Builder => $query->where('coffeeshop_points', '>=', SystemSetting::coffeeshopRewardThreshold())),
                
                Tables\Filters\Filter::make('active_carwash')
                    ->label('Active Car Wash (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->where('carwash_last_visit_at', '>=', now()->subDays(30))),
                
                Tables\Filters\Filter::make('active_coffeeshop')
                    ->label('Active Coffee Shop (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->where('coffeeshop_last_visit_at', '>=', now()->subDays(30))),

                Tables\Filters\Filter::make('has_motorwash_reward')
                    ->label('Ready for Motor Wash Reward')
                    ->query(fn (Builder $query): Builder => $query->where('motorwash_points', '>=', SystemSetting::motorwashRewardThreshold())),

                Tables\Filters\Filter::make('active_motorwash')
                    ->label('Active Motor Wash (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->where('motorwash_last_visit_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
                            fputcsv($handle, ['Phone', 'Name', 'CW Points', 'CS Points', 'MW Points', 'CW Visits', 'CS Visits', 'MW Visits']);
                            fputcsv($handle, ['081234567890', 'John Doe', '3', '2', '1', '5', '3', '2']);
                            fputcsv($handle, ['082345678901', 'Jane Smith', '0', '1', '0', '2', '1', '0']);
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
                            ->helperText('Format: Phone, Name, CW Points, CS Points, MW Points, CW Visits, CS Visits, MW Visits'),
                    ])
                    ->action(function (array $data) {
                        $file = \Illuminate\Support\Facades\Storage::disk('public')->path($data['file']);
                        
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
                    ->form([
                        Forms\Components\Radio::make('format')
                            ->label('Export Format')
                            ->options([
                                'standard' => 'Standard Export - Full customer data',
                                'maxchat' => 'MaxChat Template - Phone numbers only',
                            ])
                            ->descriptions([
                                'standard' => 'Export all customer information with service-specific details (Name, Phone, Points, Visits, etc.)',
                                'maxchat' => 'Export phone numbers only in MaxChat broadcast format (for WhatsApp campaigns)',
                            ])
                            ->default('standard')
                            ->required()
                            ->inline()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        $route = $data['format'] === 'maxchat' 
                            ? route('admin.export.customers.maxchat')
                            : route('admin.export.customers.standard');
                        
                        return redirect($route);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('carwash_last_visit_at', 'desc')
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
