<?php

namespace n2305Mailwizz\Services;

use Shopware\Models\Shop\Shop;

interface ShopUserProvider
{
    public function fetch(Shop $shop): iterable;
}
