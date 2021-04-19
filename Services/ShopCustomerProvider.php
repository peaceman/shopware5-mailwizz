<?php

namespace n2305Mailwizz\Services;

use Shopware\Models\Shop\Shop;

interface ShopCustomerProvider
{
    public function fetch(Shop $shop): iterable;
}
