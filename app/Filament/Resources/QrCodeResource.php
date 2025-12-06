<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QrCodeResource\Pages;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QrCodeResource extends Resource
{
    protected static ?string $model = QrCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'QR Code Manager';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 61;
    protected static ?string $modelLabel = 'QR Code';
    protected static ?string $pluralModelLabel = 'QR Codes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('QR Code Configuration')
                ->schema([
                    Forms\Components\CheckboxList::make('loyalty_types')
                        ->label('Loyalty Programs')
                        ->options([
                            'carwash' => 'Cuci Mobil',
                            'motorwash' => 'Cuci Motor',
                            'coffeeshop' => 'Coffee Shop',
                        ])
                        ->required()
                        ->columns(3)
                        ->helperText('Pilih satu atau lebih program untuk QR Code ini'),
                    
                    Forms\Components\Select::make('qr_type')
                        ->label('QR Type')
                        ->options([
                            'permanent' => 'Permanent (Reusable)',
                            'onetime' => 'One-Time Use',
                        ])
                        ->default('permanent')
                        ->required()
                        ->live(),
                    
                    Forms\Components\TextInput::make('name')
                        ->label('QR Name')
                        ->placeholder('e.g., Main Entrance QR')
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('location')
                        ->label('Location')
                        ->placeholder('e.g., Jakarta Selatan Branch')
                        ->maxLength(255),
                    
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expiration Date')
                        ->visible(fn (Forms\Get $get) => $get('qr_type') === 'onetime'),
                    
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                
                Tables\Columns\TextColumn::make('loyalty_types')
                    ->label('Programs')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state) || empty($state)) {
                            return '—';
                        }
                        
                        return collect($state)->map(fn($type) => match($type) {
                            'carwash' => 'Cuci Mobil',
                            'motorwash' => 'Cuci Motor',
                            'coffeeshop' => 'Coffee Shop',
                            default => ucfirst($type),
                        })->join(', ');
                    })
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->default('—'),
                
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->default('—'),
                
                Tables\Columns\BadgeColumn::make('qr_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'permanent',
                        'warning' => 'onetime',
                    ]),
                
                Tables\Columns\TextColumn::make('scan_count')
                    ->label('Scans')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('qr_type')
                    ->options([
                        'permanent' => 'Permanent',
                        'onetime' => 'One-Time',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View QR')
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\Action::make('view_url')
                        ->label('View URL')
                        ->icon('heroicon-o-link')
                        ->url(fn (QrCode $record) => $record->url)
                        ->openUrlInNewTab(),
                    
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (QrCode $record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon('heroicon-o-power')
                        ->color(fn (QrCode $record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn (QrCode $record) => $record->update(['is_active' => !$record->is_active]))
                        ->requiresConfirmation(),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('primary')
                ->button(),
                
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('QR Code Details')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('code')
                            ->label('Code')
                            ->copyable()
                            ->icon('heroicon-o-clipboard'),
                        
                        \Filament\Infolists\Components\TextEntry::make('loyalty_types')
                            ->label('Loyalty Programs')
                            ->formatStateUsing(fn ($state) => collect($state)->map(fn($type) => match($type) {
                                'carwash' => 'Cuci Mobil',
                                'motorwash' => 'Cuci Motor',
                                'coffeeshop' => 'Coffee Shop',
                                default => $type,
                            })->join(', '))
                            ->badge(),
                        
                        \Filament\Infolists\Components\TextEntry::make('qr_type')
                            ->label('QR Type')
                            ->badge(),
                        
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('Name')
                            ->default('—'),
                        
                        \Filament\Infolists\Components\TextEntry::make('location')
                            ->label('Location')
                            ->default('—'),
                        
                        \Filament\Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        
                        \Filament\Infolists\Components\TextEntry::make('scan_count')
                            ->label('Scan Count')
                            ->badge()
                            ->color('info'),
                        
                        \Filament\Infolists\Components\TextEntry::make('url')
                            ->label('Check-in URL')
                            ->copyable()
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                        
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQrCodes::route('/'),
            'create' => Pages\CreateQrCode::route('/create'),
            'view' => Pages\ViewQrCode::route('/{record}'),
            'edit' => Pages\EditQrCode::route('/{record}/edit'),
        ];
    }
}
