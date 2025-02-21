<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\TaxResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Account\Filament\Clusters\Configuration\Resources\TaxResource;

class ViewTax extends ViewRecord
{
    protected static string $resource = TaxResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/clusters/configurations/resources/tax/pages/view-tax.header-actions.delete.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/tax/pages/view-tax.header-actions.delete.notification.body'))
                ),
        ];
    }
}
