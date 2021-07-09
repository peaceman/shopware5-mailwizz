<?php

namespace n2305Mailwizz\Tests\Functional\Services;

use n2305Mailwizz\Mailwizz;
use n2305Mailwizz\Models\CustomerMailwizzSubscriber;
use n2305Mailwizz\Models\CustomerMailwizzSubscriberRepo;
use n2305Mailwizz\Services\CustomerExporter;
use n2305Mailwizz\Services\CustomerExportMode;
use n2305Mailwizz\Utils\PluginConfig;
use Psr\Log\NullLogger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Shop\Shop;
use Shopware\Tests\Functional\Components\Plugin\TestCase;

class CustomerExporterTest extends TestCase
{
    /** @var ModelManager */
    private $modelManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelManager = Shopware()->Container()->get('models');
        $this->modelManager->beginTransaction();

        // disable event handling during tests
        Shopware()->Container()->get('n2305_mailwizz.subscriber.customer_subscriber')->setEnabled(false);
    }

    protected function tearDown(): void
    {
        $this->modelManager->rollback();

        parent::tearDown();
    }

    public function customerExportDataProvider(): iterable
    {
        yield 'periodic / no subscription' => [
            CustomerExportMode::periodicImport(),
            0,
            Mailwizz\Subscriber::STATE_UNSUBSCRIBED,
        ];

        yield 'periodic / subscription' => [
            CustomerExportMode::periodicImport(),
            1,
            Mailwizz\Subscriber::STATE_CONFIRMED,
        ];

        yield 'adhoc / no subscription' => [
            CustomerExportMode::adhocUpdate(),
            0,
            Mailwizz\Subscriber::STATE_UNSUBSCRIBED,
        ];

        yield 'adhoc / unconfirmed' => [
            CustomerExportMode::adhocUpdate(),
            1,
            Mailwizz\Subscriber::STATE_UNCONFIRMED,
        ];
    }

    /**
     * @dataProvider customerExportDataProvider
     */
    public function testCustomerExport(CustomerExportMode $exportMode, int $newsletter, string $status): void
    {
        $shop = $this->modelManager->find(Shop::class, 1);

        $customer = new Customer();
        $customer->setEmail('foo@example.com');
        $customer->setFirstname('foo');
        $customer->setLastname('bar');
        $customer->setNewsletter($newsletter);
        $customer->setShop($shop);

        $this->modelManager->persist($customer);
        $this->modelManager->flush();

        $mwApiConfig = new Mailwizz\ApiConfig(
            'https://example.com/api',
            'public-key',
            'secret-key',
            'list-id'
        );

        $pluginConfig = $this->createMock(PluginConfig::class);
        $pluginConfig->expects(static::once())
            ->method('forShop')
            ->with($shop)
            ->willReturnSelf();

        $pluginConfig->expects(static::once())
            ->method('getMwApiConfig')
            ->willReturn($mwApiConfig);

        $mwApiClient = $this->createMock(Mailwizz\ApiClient::class);
        $mwApiClient->expects(static::once())
            ->method('createOrUpdateSubscriber')
            ->with(
                static::callback(static function (Mailwizz\Subscriber $sub) use ($customer): bool {
                    static::assertEquals($customer->getFirstname(), $sub->getFirstName());
                    static::assertEquals($customer->getLastname(), $sub->getLastName());
                    static::assertEquals($customer->getEmail(), $sub->getEmail());
                    static::assertEquals((bool) $customer->getNewsletter(), $sub->wantsSubscription());

                    return true;
                }),
                $status
            )
            ->willReturn('subscriber-id');

        $mwApiClientFactory = $this->createMock(Mailwizz\ApiClientFactory::class);
        $mwApiClientFactory->expects(static::once())
            ->method('create')
            ->with($mwApiConfig)
            ->willReturn($mwApiClient);

        $exporter = new CustomerExporter(
            new NullLogger(),
            $this->modelManager,
            $pluginConfig,
            $mwApiClientFactory
        );

        $exporter->export($customer, $exportMode);

        // assert customer has subscriber id
        /** @var Customer $customer */
        $customer = $this->modelManager->find(Customer::class, $customer->getId());
        $this->modelManager->refresh($customer);

        /** @var CustomerMailwizzSubscriberRepo $subRepo */
        $subRepo = $this->modelManager->getRepository(CustomerMailwizzSubscriber::class);
        $subscriber = $subRepo->fetchForCustomer($customer);

        static::assertNotNull($subscriber, "customer doesn't have an attributes object");
        static::assertEquals('subscriber-id', $subscriber->getSubscriberId());
    }

    /**
     * @dataProvider customerExportDataProvider
     */
    public function testCustomerExportIgnoreUserDecision(CustomerExportMode $exportMode, int $newsletter, string $status): void
    {
        $shop = $this->modelManager->find(Shop::class, 1);

        $customer = new Customer();
        $customer->setEmail('foo@example.com');
        $customer->setFirstname('foo');
        $customer->setLastname('bar');
        $customer->setNewsletter($newsletter);
        $customer->setShop($shop);

        $this->modelManager->persist($customer);
        $this->modelManager->flush();

        $mwApiConfig = new Mailwizz\ApiConfig(
            'https://example.com/api',
            'public-key',
            'secret-key',
            'list-id'
        );

        $pluginConfig = $this->createMock(PluginConfig::class);
        $pluginConfig->expects(static::once())
            ->method('forShop')
            ->with($shop)
            ->willReturnSelf();

        $pluginConfig->expects(static::once())
            ->method('getIgnoreUserDecision')
            ->willReturn(true);

        $pluginConfig->expects(static::once())
            ->method('getMwApiConfig')
            ->willReturn($mwApiConfig);

        $mwApiClient = $this->createMock(Mailwizz\ApiClient::class);
        $mwApiClient->expects(static::once())
            ->method('createOrUpdateSubscriber')
            ->with(
                static::callback(static function (Mailwizz\Subscriber $sub) use ($customer): bool {
                    static::assertEquals($customer->getFirstname(), $sub->getFirstName());
                    static::assertEquals($customer->getLastname(), $sub->getLastName());
                    static::assertEquals($customer->getEmail(), $sub->getEmail());
                    static::assertEquals((bool) $customer->getNewsletter(), $sub->wantsSubscription());

                    return true;
                }),
                Mailwizz\Subscriber::STATE_CONFIRMED
            )
            ->willReturn('subscriber-id');

        $mwApiClientFactory = $this->createMock(Mailwizz\ApiClientFactory::class);
        $mwApiClientFactory->expects(static::once())
            ->method('create')
            ->with($mwApiConfig)
            ->willReturn($mwApiClient);

        $exporter = new CustomerExporter(
            new NullLogger(),
            $this->modelManager,
            $pluginConfig,
            $mwApiClientFactory
        );

        $exporter->export($customer, $exportMode);

        // assert customer has subscriber id
        /** @var Customer $customer */
        $customer = $this->modelManager->find(Customer::class, $customer->getId());
        $this->modelManager->refresh($customer);

        /** @var CustomerMailwizzSubscriberRepo $subRepo */
        $subRepo = $this->modelManager->getRepository(CustomerMailwizzSubscriber::class);
        $subscriber = $subRepo->fetchForCustomer($customer);

        static::assertNotNull($subscriber, "customer doesn't have an attributes object");
        static::assertEquals('subscriber-id', $subscriber->getSubscriberId());
    }

    public function testCustomerExportWithBlacklistedEmail(): void
    {
        $shop = $this->modelManager->find(Shop::class, 1);

        $customer = new Customer();
        $customer->setEmail('foo@example.com');
        $customer->setFirstname('foo');
        $customer->setLastname('bar');
        $customer->setNewsletter(1);
        $customer->setShop($shop);

        $this->modelManager->persist($customer);
        $this->modelManager->flush();

        $mwApiConfig = new Mailwizz\ApiConfig(
            'https://example.com/api',
            'public-key',
            'secret-key',
            'list-id'
        );

        $pluginConfig = $this->createMock(PluginConfig::class);
        $pluginConfig->expects(static::once())
            ->method('forShop')
            ->with($shop)
            ->willReturnSelf();

        $pluginConfig->expects(static::once())
            ->method('getMwApiConfig')
            ->willReturn($mwApiConfig);

        $mwApiClient = $this->createMock(Mailwizz\ApiClient::class);
        $mwApiClient->expects(static::once())
            ->method('createOrUpdateSubscriber')
            ->withAnyParameters()
            ->willThrowException(new Mailwizz\EmailBlacklistedException('foo@example.com'));

        $mwApiClientFactory = $this->createMock(Mailwizz\ApiClientFactory::class);
        $mwApiClientFactory->expects(static::once())
            ->method('create')
            ->with($mwApiConfig)
            ->willReturn($mwApiClient);

        $exporter = new CustomerExporter(
            new NullLogger(),
            $this->modelManager,
            $pluginConfig,
            $mwApiClientFactory
        );

        $exporter->export($customer, CustomerExportMode::adhocUpdate());

        // assert customer has the blacklisted subscriber id
        /** @var Customer $customer */
        $customer = $this->modelManager->find(Customer::class, $customer->getId());
        $this->modelManager->refresh($customer);

        /** @var CustomerMailwizzSubscriberRepo $subRepo */
        $subRepo = $this->modelManager->getRepository(CustomerMailwizzSubscriber::class);
        $subscriber = $subRepo->fetchForCustomer($customer);

        static::assertNotNull($subscriber, "customer doesn't have an attributes object");
        static::assertEquals('blacklisted', $subscriber->getSubscriberId());
    }

    public function testThatBlacklistedSubscriberIdsWontGetExported(): void
    {
        $shop = $this->modelManager->find(Shop::class, 1);

        $customer = new Customer();
        $customer->setEmail('foo@example.com');
        $customer->setFirstname('foo');
        $customer->setLastname('bar');
        $customer->setNewsletter(1);
        $customer->setShop($shop);

        $subscriber = new CustomerMailwizzSubscriber();
        $subscriber->setCustomer($customer);
        $subscriber->setSubscriberId(Mailwizz\Subscriber::SUBSCRIBER_ID_BLACKLISTED);

        $this->modelManager->persist($customer);
        $this->modelManager->persist($subscriber);
        $this->modelManager->flush();

        $pluginConfig = $this->createMock(PluginConfig::class);
        $pluginConfig->expects(static::never())
            ->method('forShop')
            ->withAnyParameters();

        $mwApiClientFactory = $this->createMock(Mailwizz\ApiClientFactory::class);
        $mwApiClientFactory->expects(static::never())
            ->method('create')
            ->withAnyParameters();

        $exporter = new CustomerExporter(
            new NullLogger(),
            $this->modelManager,
            $pluginConfig,
            $mwApiClientFactory
        );

        $exporter->export($customer, CustomerExportMode::adhocUpdate());
    }

    public function testThatBlacklistedEmailsWontGetExported(): void
    {
        $shop = $this->modelManager->find(Shop::class, 1);

        $customer = new Customer();
        $customer->setEmail('foo@example.com');
        $customer->setFirstname('foo');
        $customer->setLastname('bar');
        $customer->setNewsletter(1);
        $customer->setShop($shop);

        $this->modelManager->persist($customer);
        $this->modelManager->flush();

        $mwApiConfig = new Mailwizz\ApiConfig(
            'https://example.com/api',
            'public-key',
            'secret-key',
            'list-id'
        );

        $pluginConfig = $this->createMock(PluginConfig::class);
        $pluginConfig->expects(static::once())
            ->method('getEmailBlacklistSuffixes')
            ->willReturn(['example.com']);

        $pluginConfig->expects(static::never())
            ->method('getMwApiConfig')
            ->willReturn($mwApiConfig);

        $pluginConfig->expects(static::once())
            ->method('forShop')
            ->with($shop)
            ->willReturnSelf();

        $mwApiClientFactory = $this->createMock(Mailwizz\ApiClientFactory::class);
        $mwApiClientFactory->expects(static::never())
            ->method('create')
            ->withAnyParameters();

        $exporter = new CustomerExporter(
            new NullLogger(),
            $this->modelManager,
            $pluginConfig,
            $mwApiClientFactory
        );

        $exporter->export($customer, CustomerExportMode::adhocUpdate());
    }
}
