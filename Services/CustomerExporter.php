<?php

namespace n2305Mailwizz\Services;

use n2305Mailwizz\Utils\PluginConfig;
use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\User\User;

class CustomerExporter
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ModelManager */
    private $modelManager;

    /** @var PluginConfig */
    private $pluginConfig;

    /** @var MwApiClientFactory */
    private $mwApiClientFactory;

    public function __construct(
        LoggerInterface $logger,
        ModelManager $modelManager,
        PluginConfig $pluginConfig,
        MwApiClientFactory $mwApiClientFactory
    ) {
        $this->logger = $logger;
        $this->modelManager = $modelManager;
        $this->pluginConfig = $pluginConfig;
        $this->mwApiClientFactory = $mwApiClientFactory;
    }

    public function export(User $user): void
    {
        // todo implement
    }
}
