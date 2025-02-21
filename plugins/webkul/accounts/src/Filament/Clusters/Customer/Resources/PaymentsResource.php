<?php

namespace Webkul\Account\Filament\Clusters\Customer\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Enums\PaymentStatus;
use Webkul\Account\Enums\PaymentType;
use Webkul\Account\Filament\Clusters\Customer;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Payment;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;

class PaymentsResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $cluster = Customer::class;

    public static function getModelLabel(): string
    {
        return __('accounts::filament/clusters/customers/resources/payment.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounts::filament/clusters/customers/resources/payment.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('accounts::filament/clusters/customers/resources/payment.navigation.group');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'state',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('accounts::filament/clusters/customers/resources/payment.global-search.name')  => $record?->name ?? '—',
            __('accounts::filament/clusters/customers/resources/payment.global-search.state') => $record?->state ?? '—',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        ProgressStepper::make('state')
                            ->hiddenLabel()
                            ->inline()
                            ->options(PaymentStatus::class)
                            ->default(PaymentStatus::DRAFT->value)
                            ->columnSpan('full')
                            ->disabled()
                            ->live()
                            ->reactive(),
                    ])->columns(2),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Radio::make('payment_type')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.payment-type'))
                                    ->options(PaymentType::class)
                                    ->default(PaymentType::SEND->value),
                                Forms\Components\Select::make('journal_id')
                                    ->relationship(
                                        'journal',
                                        'name',
                                        fn ($query) => $query->whereIn('type', ['bank', 'cash'])
                                    )
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.journal'))
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->live()
                                    ->required(),
                                Forms\Components\Select::make('partner_bank_id')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.customer-bank-account'))
                                    ->relationship(
                                        'partnerBank',
                                        'account_number',
                                    )
                                    ->searchable()
                                    ->visible(function (Get $get, Set $set) {
                                        if ($get('journal_id')) {
                                            $journal = Journal::find($get('journal_id'));

                                            return $journal->type == 'bank';
                                        }

                                        return false;
                                    })
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('partner_id')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.customer'))
                                    ->relationship(
                                        'partner',
                                        'name',
                                    )
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('payment_method_line_id')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.payment-method'))
                                    ->relationship(
                                        'paymentMethodLine',
                                        'name',
                                    )
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('amount')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.amount'))
                                    ->default(0)
                                    ->required(),
                                Forms\Components\DatePicker::make('date')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.date'))
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Forms\Components\TextInput::make('memo')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.form.sections.fields.memo')),
                            ])->columns(2),
                    ]),
            ])
            ->columns('full');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.company'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partnerBank.account_holder_name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.bank-account-holder'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pairedInternalTransferPayment.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.paired-internal-transfer-payment'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethodLine.name')
                    ->searchable()
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.payment-method-line'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.payment-method'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.currency'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.partner'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('outstandingAccount.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.outstanding-amount'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('destinationAccount.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.destination-account'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.created-by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paymentTransaction.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.columns.payment-transaction'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.name'))
                    ->collapsible(),
                Tables\Grouping\Group::make('company.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.company'))
                    ->collapsible(),
                Tables\Grouping\Group::make('paymentMethodLine.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.payment-method-line'))
                    ->collapsible(),
                Tables\Grouping\Group::make('partner.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.partner'))
                    ->collapsible(),
                Tables\Grouping\Group::make('paymentMethod.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.payment-method'))
                    ->collapsible(),
                Tables\Grouping\Group::make('partnerBank.account_holder_name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.partner-bank-account'))
                    ->collapsible(),
                Tables\Grouping\Group::make('pairedInternalTransferPayment.name')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.paired-internal-transfer-payment'))
                    ->collapsible(),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.created-at'))
                    ->collapsible(),
                Tables\Grouping\Group::make('updated_at')
                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.groups.updated-at'))
                    ->date()
                    ->collapsible(),
            ])
            ->filtersFormColumns(2)
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraintPickerColumns(2)
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('company.name')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.company'))
                            ->icon('heroicon-o-user')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.company'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            ),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('partnerBank.account_holder_name')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.customer-bank-account'))
                            ->icon('heroicon-o-user')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.customer-bank-account'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            ),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('pairedInternalTransferPayment.name')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.paired-internal-transfer-payment'))
                            ->icon('heroicon-o-user')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.paired-internal-transfer-payment'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            ),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('paymentMethodLine.name')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.payment-method-line'))
                            ->icon('heroicon-o-user')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.payment-method-line'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            ),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('paymentMethod.name')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.payment-method'))
                            ->icon('heroicon-o-user')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.payment-method'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            ),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('currency.name')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.currency'))
                            ->icon('heroicon-o-user')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.currency'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            ),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('partner.name')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.partner'))
                            ->icon('heroicon-o-user')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.partner'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            ),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('created_at')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.created-at')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('updated_at')
                            ->label(__('accounts::filament/clusters/customers/resources/payment.table.filters.updated-at')),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('accounts::filament/clusters/customers/resources/payment.table.actions.delete.notification.title'))
                            ->body(__('accounts::filament/clusters/customers/resources/payment.table.actions.delete.notification.body'))
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('accounts::filament/clusters/customers/resources/payment.table.bulk-actions.delete.notification.title'))
                                ->body(__('accounts::filament/clusters/customers/resources/payment.table.bulk-actions.delete.notification.body'))
                        ),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(['default' => 3])
                    ->schema([
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\Section::make(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-information.title'))
                                    ->schema([
                                        Infolists\Components\TextEntry::make('state')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                PaymentStatus::DRAFT->value      => 'gray',
                                                PaymentStatus::IN_PROCESS->value => 'warning',
                                                PaymentStatus::PAID->value       => 'success',
                                                PaymentStatus::CANCELED->value   => 'danger',
                                                default                          => 'gray',
                                            })
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-information.entries.state'))
                                            ->formatStateUsing(fn (string $state): string => PaymentStatus::options()[$state])
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('payment_type')
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-information.entries.payment-type'))
                                            ->icon('heroicon-o-banknotes')
                                            ->formatStateUsing(fn ($state) => PaymentState::options()[$state]),
                                        Infolists\Components\TextEntry::make('journal.name')
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-information.entries.journal'))
                                            ->icon('heroicon-o-document-text')
                                            ->placeholder('—'),
                                        Infolists\Components\TextEntry::make('partnerBank.account_number')
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-information.entries.customer-bank-account'))
                                            ->icon('heroicon-o-building-library')
                                            ->placeholder('—')
                                            ->visible(fn ($record) => $record->journal?->type === 'bank'),
                                        Infolists\Components\TextEntry::make('partner.name')
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-information.entries.customer'))
                                            ->icon('heroicon-o-user')
                                            ->placeholder('—'),
                                    ])->columns(2),
                                Infolists\Components\Section::make(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-details.title'))
                                    ->schema([
                                        Infolists\Components\TextEntry::make('amount')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-details.entries.amount'))
                                            ->placeholder('—'),
                                        Infolists\Components\TextEntry::make('date')
                                            ->icon('heroicon-o-calendar')
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-details.entries.date'))
                                            ->placeholder('—')
                                            ->date(),
                                        Infolists\Components\TextEntry::make('memo')
                                            ->label('Memo')
                                            ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-details.entries.memo'))
                                            ->icon('heroicon-o-document-text')
                                            ->placeholder('—')
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ])->columnSpan(2),

                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-method.title'))
                                ->schema([
                                    Infolists\Components\TextEntry::make('paymentMethodLine.name')
                                        ->label('Payment Method')
                                        ->label(__('accounts::filament/clusters/customers/resources/payment.infolist.sections.payment-method.entries.payment-method'))
                                        ->icon('heroicon-o-credit-card')
                                        ->placeholder('—'),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayments::route('/create'),
            'view'   => Pages\ViewPayments::route('/{record}'),
            'edit'   => Pages\EditPayments::route('/{record}/edit'),
        ];
    }
}
