<?php

namespace n2305Mailwizz\Tests\Functional\Services;

use n2305Mailwizz\Models\CustomerMailwizzSubscriber;
use n2305Mailwizz\Services\ExportableShopCustomerProvider;
use n2305Mailwizz\Tests\PluginConfigMock;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Shop\Shop;
use Shopware\Tests\Functional\Components\Plugin\TestCase;

class ExportableShopCustomerProviderTest extends TestCase
{
    /** @var ModelManager */
    private $modelManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelManager = Shopware()->Container()->get('models');
        $this->modelManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->modelManager->rollback();

        parent::tearDown();
    }

    public function testProvider(): void
    {
        $shop = $this->modelManager->find(Shop::class, 1);
        $pluginConfig = new PluginConfigMock([
            'emailBlacklistSuffixes' => 'amazon.com, blacklist.com',
        ]);

        // already exported customer
        $this->createExportedCustomer('exported@example.com', 'foobar', $shop);

        // unexported customer
        $this->createUnexportedCustomer('unexported@example.com', $shop);
        $this->createUnexportedCustomer('unexported@amazon.com', $shop);
        $this->createUnexportedCustomer('unexported@blacklist.com', $shop);

        $provider = new ExportableShopCustomerProvider($this->modelManager, $pluginConfig);
        $emails = [];

        /** @var Customer $customer */
        foreach ($provider->fetch($shop) as $customer) {
            $emails[] = $customer->getEmail();
        }

        static::assertTrue(
            in_array('unexported@example.com', $emails),
            "couldn't find the unexported user in the result set"
        );

        static::assertFalse(
            in_array('exported@example.com', $emails),
            'found the exported user in the result set'
        );

        static::assertFalse(
            in_array('unexported@amazon.com', $emails),
            'found the blacklisted user in the result set'
        );

        static::assertFalse(
            in_array('unexported@blacklist.com', $emails),
            'found the blacklisted user in the result set'
        );
    }

    private function createExportedCustomer(string $email, string $subscriberId, Shop $shop): void
    {
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setShop($shop);

        $subscriber = new CustomerMailwizzSubscriber();
        $subscriber->setCustomer($customer);
        $subscriber->setSubscriberId($subscriberId);

        $this->modelManager->persist($subscriber);
        $this->modelManager->persist($customer);
        $this->modelManager->flush();
    }

    private function createUnexportedCustomer(string $email, Shop $shop): void
    {
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setShop($shop);

        $this->modelManager->persist($customer);
        $this->modelManager->flush();
    }
}
