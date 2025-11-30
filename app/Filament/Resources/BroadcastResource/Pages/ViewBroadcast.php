<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Filament\Resources\BroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ViewBroadcast extends ViewRecord
{
    protected static string $resource = BroadcastResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Broadcast Details')
                    ->schema([
                        TextEntry::make('message')
                            ->label('Message')
                            ->columnSpanFull(),

                        TextEntry::make('target_filter')
                            ->label('Target Audience')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'all' => 'All Customers',
                                'has_reward' => 'Customers with Rewards',
                                'active' => 'Active Customers',
                                default => $state,
                            }),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'queued' => 'info',
                                'sending' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('total_recipients')
                            ->label('Total Recipients'),

                        TextEntry::make('sent_count')
                            ->label('Sent Successfully')
                            ->color('success'),

                        TextEntry::make('failed_count')
                            ->label('Failed')
                            ->color('danger'),

                        TextEntry::make('estimated_cost')
                            ->label('Estimated Cost')
                            ->money('IDR'),
                    ])
                    ->columns(4),

                Section::make('Meta Information')
                    ->schema([
                        TextEntry::make('sender.name')
                            ->label('Sent By'),

                        TextEntry::make('sent_at')
                            ->label('Sent At')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2),
            ]);
    }
}
