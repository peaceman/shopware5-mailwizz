<?php

namespace n2305Mailwizz\Services;

use Psr\Log\LoggerInterface;
use Shopware\Models\Shop\Shop;

class ShopCustomerExporter
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ShopCustomerProvider */
    private $customerProvider;

    /** @var CustomerExporter */
    private $customerExporter;

    public function __construct(
        LoggerInterface $logger,
        ShopCustomerProvider $userProvider,
        CustomerExporter $userExporter
    ) {
        $this->logger = $logger;
        $this->customerProvider = $userProvider;
        $this->customerExporter = $userExporter;
    }

    public function export(Shop $shop)
    {
        $this->logger->info('Start exporting users of shop', [
            'shop' => ['id' => $shop->getId(), 'name' => $shop->getName()],
        ]);
        $startTime = microtime(true);

        $counter = 0;
        foreach ($this->customerProvider->fetch($shop) as $user) {
            $this->customerExporter->export($user, CustomerExportMode::periodicImport());
            $counter++;

            // 2 min time limit
            if ((microtime(true) - $startTime) >= 120) break;
        }

        $this->logger->info('Finished exporting users of shop', [
            'shop' => ['id' => $shop->getId(), 'name' => $shop->getName()],
            'exportedUsers' => $counter,
            'elapsed' => microtime(true) - $startTime,
        ]);
    }
}
