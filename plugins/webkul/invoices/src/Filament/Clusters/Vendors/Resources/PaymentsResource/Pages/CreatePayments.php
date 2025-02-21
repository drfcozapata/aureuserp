<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource\Pages;

use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages\CreatePayments as BaseCreatePayments;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource;

class CreatePayments extends BaseCreatePayments
{
    protected static string $resource = PaymentsResource::class;
}
