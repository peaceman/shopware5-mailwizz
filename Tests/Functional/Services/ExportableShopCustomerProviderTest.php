<?php

namespace n2305Mailwizz\Tests\Functional\Services;

use n2305Mailwizz\Services\ExportableShopCustomerProvider;
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

        // already exported customer
        $customer = new Customer();
        $customer->setEmail('exported@example.com');

        $customerAttribute = new \Shopware\Models\Attribute\Customer();
        $customerAttribute->setMailwizzSubscriberId('foobar');
        $customer->setAttribute($customerAttribute);

        $this->modelManager->persist($customer);

        // unexported customer
        $customer = new Customer();
        $customer->setEmail('unexported@example.com');
        $customer->setShop($shop);
        $this->modelManager->persist($customer);

        $this->modelManager->flush();

        $provider = new ExportableShopCustomerProvider($this->modelManager);
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
    }
}
