<?php

namespace n2305Mailwizz\Tests\Unit\Services;

use n2305Mailwizz\Services\ShopCustomerExporter;
use n2305Mailwizz\Services\ShopCustomerProvider;
use n2305Mailwizz\Services\CustomerExporter;
use n2305Mailwizz\Tests\ShopMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Models\Customer\Customer;

class ShopCustomerExporterTest extends TestCase
{
    public function testExportCustomers(): void
    {
        $customers = [
            new Customer(),
            new Customer(),
        ];
        $shop = new ShopMock('23');

        $userProvider = $this->createMock(ShopCustomerProvider::class);
        $userProvider->expects(static::once())
            ->method('fetch')
            ->with($shop)
            ->willReturn($customers);

        $userExporter = $this->createMock(CustomerExporter::class);

        foreach ($customers as $idx => $user) {
            $userExporter->expects(static::at($idx))
                ->method('export')
                ->with($user);
        }

        $exporter = new ShopCustomerExporter(
            new NullLogger(),
            $userProvider,
            $userExporter
        );

        $exporter->export($shop);
    }
}
