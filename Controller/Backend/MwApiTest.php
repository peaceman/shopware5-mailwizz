<?php

namespace n2305Mailwizz\Controller\Backend;

use n2305Mailwizz\Mailwizz\ApiClientFactory;
use n2305Mailwizz\Utils\PluginConfig;
use Psr\Log\LoggerInterface;
use n2305Mailwizz\Mailwizz;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Repository as ShopRepo;

class MwApiTest extends \Shopware_Controllers_Backend_ExtJs
{
    /** @var LoggerInterface */
    private $logger;

    /** @var Mailwizz\ApiClientFactory */
    private $mwApiClientFactory;

    /** @var PluginConfig */
    private $pluginConfig;

    public function __construct(
        LoggerInterface $logger,
        ApiClientFactory $mwApiClientFactory,
        PluginConfig $pluginConfig
    ) {
        $this->logger = $logger;
        $this->mwApiClientFactory = $mwApiClientFactory;
        $this->pluginConfig = $pluginConfig;

        parent::__construct();
    }

    public function testAction()
    {
        /** @var ShopRepo $shopRepo */
        $shopRepo = $this->getModelManager()->getRepository(Shop::class);

        $results = [];

        foreach ($shopRepo->getActiveShops() as $shop) {
            $shopPluginConfig = $this->pluginConfig->forShop($shop);

            if (!$shopPluginConfig->hasConfiguredMailwizz()) {
                $results[] = sprintf('Plugin is not configured for shop %s', $shop->getName());
                continue;
            }

            $apiClient = $this->mwApiClientFactory->create($shopPluginConfig->getMwApiConfig());
            $listExists = $apiClient->listExists();

            $results[] = $listExists
                ? sprintf('Plugin connection to mailwizz successfully tested for shop %s. Check logs', $shop->getName())
                : sprintf('Plugin connection to mailwizz failed for shop %s. Check logs', $shop->getName());
        }

        $this->View()->assign('response', $results);
    }
}
