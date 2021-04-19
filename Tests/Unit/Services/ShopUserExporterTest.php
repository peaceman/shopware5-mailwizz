<?php

namespace n2305Mailwizz\Tests\Unit\Services;

use n2305Mailwizz\Services\ShopUserExporter;
use n2305Mailwizz\Services\ShopCustomerProvider;
use n2305Mailwizz\Services\CustomerExporter;
use n2305Mailwizz\Tests\ShopMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Models\User\User;

class ShopUserExporterTest extends TestCase
{
    public function testExportUsers(): void
    {
        $users = [
            new User(),
            new User(),
        ];
        $shop = new ShopMock('23');

        $userProvider = $this->createMock(ShopCustomerProvider::class);
        $userProvider->expects(static::once())
            ->method('fetch')
            ->with($shop)
            ->willReturn($users);

        $userExporter = $this->createMock(CustomerExporter::class);

        foreach ($users as $idx => $user) {
            $userExporter->expects(static::at($idx))
                ->method('export')
                ->with($user);
        }

        $exporter = new ShopUserExporter(
            new NullLogger(),
            $userProvider,
            $userExporter
        );

        $exporter->export($shop);
    }
}
