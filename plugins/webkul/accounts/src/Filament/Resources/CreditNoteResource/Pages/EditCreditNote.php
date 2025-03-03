<?php

namespace Webkul\Account\Filament\Resources\CreditNoteResource\Pages;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\EditInvoice as EditRecord;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Models\MoveLine;
use Webkul\Partner\Models\Partner;
use Webkul\Account\Filament\Resources\CreditNoteResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions as BaseActions;

class EditCreditNote extends EditRecord
{
    protected static string $resource = CreditNoteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('Credit Note updated'))
            ->body(__('Credit Note has been updated successfully.'));
    }

    protected function getHeaderActions(): array
    {
        $predefinedActions = parent::getHeaderActions();

        $predefinedActions = collect($predefinedActions)->filter(function ($action) {
            return !in_array($action->getName(), [
                'customers.invoice.set-as-checked',
                'customers.invoice.credit-note',
            ]);
        })->map(function ($action) {
            if ($action->getName() == 'customers.invoice.preview') {
                return BaseActions\PreviewAction::make()
                    ->modalHeading(__('Preview Credit Note'))
                    ->setTemplate('accounts::credit-note/actions/preview.index');
            }

            return $action;
        })->toArray();

        return [
            ...$predefinedActions,
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        $record = $this->getRecord();

        $data['partner_id'] ??= $record->partner_id;
        $data['invoice_date'] ??= $record->invoice_date;
        $data['name'] ??= $record->name;
        $data['auto_post'] ??= $record->auto_post;
        $data['invoice_currency_rate'] ??= 1.0;

        if ($data['partner_id']) {
            $partner = Partner::find($data['partner_id']);

            $data['commercial_partner_id'] = $partner->id;
            $data['partner_shipping_id'] = $partner->id;
            $data['invoice_partner_display_name'] = $partner->name;
        } else {
            $data['invoice_partner_display_name'] = "#Created By: {$user->name}";
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $this->getResource()::collectTotals($record);
    }
}
