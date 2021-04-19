<?php

namespace n2305Mailwizz\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use n2305Mailwizz\Services\ShopCustomerExporter;

class ExportUsersToMailwizz implements SubscriberInterface
{
    /** @var ShopCustomerExporter */
    private $shopUserExporter;

    public function __construct(ShopCustomerExporter $shopUserExporter)
    {
        $this->shopUserExporter = $shopUserExporter;
    }

    public static function getSubscribedEvents()
    {
        return [
            'ExportUsersToMailwizz' => 'onExportUsers',
        ];
    }

    public function onExportUsers(Enlight_Event_EventArgs $e): void
    {
        $this->shopUserExporter->export($e->get('shop'));
    }
}
