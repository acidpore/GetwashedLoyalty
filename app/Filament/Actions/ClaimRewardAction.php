<?php

namespace App\Filament\Actions;

use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class ClaimRewardAction
{
    /**
     * Staff-only: trade in the pending reward and log it. Points are frozen at
     * the milestone until this runs, so claiming is what lets the customer
     * carry on collecting. Only the final tier ends the cycle at 0.
     */
    public static function make(string $loyaltyType): Action
    {
        return Action::make("claim_{$loyaltyType}")
            ->label('Klaim Hadiah')
            ->icon('heroicon-o-gift')
            ->color('success')
            ->visible(fn (Customer $record) => $record->hasReward($loyaltyType))
            ->requiresConfirmation()
            ->modalHeading('Klaim hadiah')
            ->modalDescription(function (Customer $record) use ($loyaltyType) {
                $milestone = $record->earnedMilestone($loyaltyType);

                if (! $milestone) {
                    return 'Tidak ada hadiah yang bisa diklaim.';
                }

                $isFinalTier = $milestone['at'] >= SystemSetting::maxPoints($loyaltyType);

                return 'Serahkan "' . $milestone['reward'] . '" (milestone ' . $milestone['at'] . ' poin). '
                    . ($isFinalTier
                        ? 'Ini milestone terakhir, poin customer kembali ke 0.'
                        : 'Poin customer tetap ' . $record->getPoints($loyaltyType)
                            . ' dan bisa lanjut dikumpulkan ke milestone berikutnya.');
            })
            ->action(function (Customer $record) use ($loyaltyType) {
                $milestone = $record->claimReward($loyaltyType, auth()->id());

                if (! $milestone) {
                    Notification::make()
                        ->title('Belum ada hadiah yang bisa diklaim')
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Hadiah diklaim: ' . $milestone['reward'])
                    ->body('Poin sekarang ' . $record->getPoints($loyaltyType) . '.')
                    ->success()
                    ->send();
            });
    }
}
