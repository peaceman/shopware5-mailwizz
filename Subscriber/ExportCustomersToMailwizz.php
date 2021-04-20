<?php

namespace n2305Mailwizz\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use n2305Mailwizz\Services\ShopCustomerExporter;

class ExportCustomersToMailwizz implements SubscriberInterface
{
    /** @var ShopCustomerExporter */
    private $shopCustomerExporter;

    public function __construct(ShopCustomerExporter $shopCustomerExporter)
    {
        $this->shopCustomerExporter = $shopCustomerExporter;
    }

    public static function getSubscribedEvents()
    {
        return [
            'ExportCustomersToMailwizz' => 'onExportCustomers',
        ];
    }

    public function onExportCustomers(Enlight_Event_EventArgs $e)
    {
        $this->shopCustomerExporter->export($e->get('shop'));
    }
}
