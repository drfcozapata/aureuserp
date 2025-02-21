<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation as BaseViewOrders;
use Webkul\Sale\Traits\HasSaleOrderActions;

class ViewOrders extends BaseViewOrders
{
    use HasSaleOrderActions;

    protected static string $resource = OrdersResource::class;
}
