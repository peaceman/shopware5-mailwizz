<?php

namespace n2305Mailwizz\Tests;

use Shopware\Models\Shop\Shop;

class ShopMock extends Shop
{
    public $id;

    public function __construct($id)
    {
        parent::__construct();

        $this->id = $id;
    }
}
