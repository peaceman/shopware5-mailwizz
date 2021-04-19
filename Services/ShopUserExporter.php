<?php

namespace n2305Mailwizz\Services;

use Psr\Log\LoggerInterface;
use Shopware\Models\Shop\Shop;

class ShopUserExporter
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ShopUserProvider */
    private $userProvider;

    /** @var UserExporter */
    private $userExporter;

    public function __construct(
        LoggerInterface $logger,
        ShopUserProvider $userProvider,
        UserExporter $userExporter
    ) {
        $this->logger = $logger;
        $this->userProvider = $userProvider;
        $this->userExporter = $userExporter;
    }

    public function export(Shop $shop): void
    {
        $this->logger->info('Start exporting users of shop', [
            'shop' => ['id' => $shop->getId(), 'name' => $shop->getName()],
        ]);
        $startTime = microtime(true);

        $counter = 0;
        foreach ($this->userProvider->fetch($shop) as $user) {
            $this->userExporter->export($user);
            $counter++;
        }

        $this->logger->info('Finished exporting users of shop', [
            'shop' => ['id' => $shop->getId(), 'name' => $shop->getName()],
            'exportedUsers' => $counter,
            'elapsed' => microtime(true) - $startTime,
        ]);
    }
}
