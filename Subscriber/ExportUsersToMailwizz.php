<?php

namespace n2305Mailwizz\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use n2305Mailwizz\Services\ShopUserExporter;

class ExportUsersToMailwizz implements SubscriberInterface
{
    /** @var ShopUserExporter */
    private $shopUserExporter;

    public function __construct(ShopUserExporter $shopUserExporter)
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
