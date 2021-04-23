<?php

namespace n2305Mailwizz\Services;

use Doctrine\DBAL\LockMode;
use n2305Mailwizz\Models\CustomerMailwizzSubscriber;
use n2305Mailwizz\Subscriber\CustomerSubscriber;
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
        try {
            $subscriber = Mailwizz\Subscriber::createFromCustomer(
                $customer,
                $this->modelManager->getRepository(CustomerMailwizzSubscriber::class)
            );
        } catch (Throwable $e) {
            $this->logger->warn('Failed to create subscriber dto from customer', [
                'customer' => ['id' => $customer->getId(), 'email' => $customer->getEmail()],
                'exception' => $e,
            ]);

            return;
        }

        if ($subscriber->isBlacklisted()) {
            return;
        }

        $shop = $customer->getShop();
        $pluginConfig = $this->pluginConfig->forShop($shop);

        if ($this->isEmailBlacklisted($subscriber->getEmail(), $pluginConfig)) {
            return;
        }

        try {
            $apiClient = $this->mwApiClientFactory->create($pluginConfig->getMwApiConfig());
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
        } catch (Mailwizz\EmailBlacklistedException $e) {
            $this->storeSubscriberIdAtCustomer(Mailwizz\Subscriber::SUBSCRIBER_ID_BLACKLISTED, $customer);
        }
    }

    private function storeSubscriberIdAtCustomer(
        string $subscriptionId,
        Customer $customer
    ) {
        $subscriber = new CustomerMailwizzSubscriber();
        $subscriber->setSubscriberId($subscriptionId);
        $subscriber->setCustomer($customer);

        $this->modelManager->persist($customer);
        $this->modelManager->persist($subscriber);
        $this->modelManager->flush();
    }

    private function determineSubscriberStatus(
        Mailwizz\Subscriber $subscriber,
        CustomerExportMode $exportMode
    ): string {
        if ($this->pluginConfig->getIgnoreUserDecision())
            return Mailwizz\Subscriber::STATE_CONFIRMED;

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

    private function isEmailBlacklisted(string $email, PluginConfig $config): bool
    {
        $suffixes = $config->getEmailBlacklistSuffixes();

        foreach ($suffixes as $suffix) {
            $suffix = preg_quote($suffix, '/');
            $pattern = "/$suffix$/i";

            if (preg_match($pattern, $email) === 1) {
                return true;
            }
        }

        return false;
    }
}
