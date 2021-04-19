<?php

namespace n2305Mailwizz\Tests\Unit\Mailwizz;

use n2305Mailwizz\Mailwizz\ApiClient;
use n2305Mailwizz\Mailwizz\ApiClientFactory;
use n2305Mailwizz\Mailwizz\ApiConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ApiClientFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $apiConfig = $this->createMock(ApiConfig::class);

        $factory = $this->createFactory();
        $apiClient = $factory->create($apiConfig);

        static::assertInstanceOf(ApiClient::class, $apiClient);
    }

    public function testApiClientCache(): void
    {
        $apiConfigA = new ApiConfig('api url', 'public key', 'private key', 'list id');
        $apiConfigB = new ApiConfig('api url B', 'public key', 'private key', 'list id');

        $factory = $this->createFactory();

        static::assertSame($factory->create($apiConfigA), $factory->create($apiConfigA));
        static::assertNotSame($factory->create($apiConfigA), $factory->create($apiConfigB));
    }

    private function createFactory(): ApiClientFactory
    {
        return new ApiClientFactory(
            new NullLogger()
        );
    }
}
