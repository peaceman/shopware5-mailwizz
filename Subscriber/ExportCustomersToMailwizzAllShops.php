<?php

namespace n2305Mailwizz\Subscriber;

use Enlight\Event\SubscriberInterface;
use n2305Mailwizz\Utils\PluginConfig;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Repository as ShopRepo;
use Shopware\Models\Shop\Shop;

class ExportCustomersToMailwizzAllShops implements SubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ModelManager */
    private $modelManager;

    /** @var PluginConfig */
    private $pluginConfig;

    /** @var ContainerAwareEventManager */
    private $eventManager;

    public function __construct(
        LoggerInterface $logger,
        ModelManager $modelManager,
        PluginConfig $pluginConfig,
        ContainerAwareEventManager $eventManager
    ) {
        $this->logger = $logger;
        $this->modelManager = $modelManager;
        $this->pluginConfig = $pluginConfig;
        $this->eventManager = $eventManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_ExportCustomersToMailwizzAllShops' => 'onExportCustomers',
        ];
    }

    public function onExportCustomers()
    {
        /** @var ShopRepo $shopRepo */
        $shopRepo = $this->modelManager->getRepository(Shop::class);

        /** @var Shop $shop */
        foreach ($shopRepo->getActiveShops() as $shop) {
            $shopPluginConfig = $this->pluginConfig->forShop($shop);
            if (!$this->hasConfiguredMailwizz($shopPluginConfig))
                continue;

            $this->logger->info('Dispatching ExportUsersToMailwizz', [
                'shop' => ['id' => $shop->getId(), 'name' => $shop->getName()]
            ]);

            $this->eventManager->notify(
                'ExportCustomersToMailwizz',
                ['shop' => $shop, 'pluginConfig' => $shopPluginConfig]
            );
        }
    }

    private function hasConfiguredMailwizz(PluginConfig $config): bool
    {
        static $requiredKeys = ['mwApiUrl', 'mwApiPublicKey', 'mwApiPrivateKey', 'mwListId'];

        foreach ($requiredKeys as $requiredKey) {
            if (empty($config->get($requiredKey)))
                return false;
        }

        return $config->isActive() && true;
    }
}
