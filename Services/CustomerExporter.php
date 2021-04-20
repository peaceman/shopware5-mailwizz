<?php

namespace n2305Mailwizz\Services;

use n2305Mailwizz\Utils\PluginConfig;
use n2305Mailwizz\Mailwizz;
use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Attribute\Customer as CustomerAttribute;
use Throwable;

class CustomerExporter
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ModelManager */
    private $modelManager;

    /** @var PluginConfig */
    private $pluginConfig;

    /** @var Mailwizz\ApiClientFactory */
    private $mwApiClientFactory;

    public function __construct(
        LoggerInterface $logger,
        ModelManager $modelManager,
        PluginConfig $pluginConfig,
        Mailwizz\ApiClientFactory $mwApiClientFactory
    ) {
        $this->logger = $logger;
        $this->modelManager = $modelManager;
        $this->pluginConfig = $pluginConfig;
        $this->mwApiClientFactory = $mwApiClientFactory;
    }

    public function export(Customer $customer, CustomerExportMode $exportMode)
    {
        $shop = $customer->getShop();
        $pluginConfig = $this->pluginConfig->forShop($shop);
        $apiClient = $this->mwApiClientFactory->create($pluginConfig->getMwApiConfig());

        try {
            $subscriber = Mailwizz\Subscriber::createFromCustomer($customer);
        } catch (Throwable $e) {
            $this->logger->warn('Failed to create subscriber dto from customer', [
                'customer' => ['id' => $customer->getId(), 'email' => $customer->getEmail()],
                'exception' => $e,
            ]);

            return;
        }

        $subscriberId = $apiClient->createOrUpdateSubscriber(
            $subscriber,
            $this->determineSubscriberStatus($subscriber, $exportMode)
        );

        if (empty($subscriberId)) {
            $this->logger->warn('Failed to create or update subscriber', [
                'shop' => ['id' => $shop->getId(), 'name' => $shop->getName()],
                'customer' => ['id' => $customer->getId(), 'email' => $customer->getEmail()],
            ]);

            return;
        }

        if (empty($subscriber->getSubscriberId())) {
            $this->storeSubscriberIdAtCustomer($subscriberId, $customer);
        }
    }

    private function storeSubscriberIdAtCustomer(
        string $subscriptionId,
        Customer $customer
    ) {
        $attr = $customer->getAttribute() ?? new CustomerAttribute();
        $attr->setMailwizzSubscriberId($subscriptionId);

        $customer->setAttribute($attr);

        $this->modelManager->persist($customer);
        $this->modelManager->persist($attr);
        $this->modelManager->flush();
    }

    private function determineSubscriberStatus(
        Mailwizz\Subscriber $subscriber,
        CustomerExportMode $exportMode
    ): string {
        if (!$subscriber->wantsSubscription()) {
            return Mailwizz\Subscriber::STATE_UNSUBSCRIBED;
        }

        switch (true) {
            case $exportMode->isAdhocUpdate():
                return Mailwizz\Subscriber::STATE_UNCONFIRMED;
            case $exportMode->isPeriodicImport():
                return Mailwizz\Subscriber::STATE_CONFIRMED;
        }
    }
}
