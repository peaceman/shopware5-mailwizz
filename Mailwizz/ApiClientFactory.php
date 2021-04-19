<?php

namespace n2305Mailwizz\Mailwizz;

use Psr\Log\LoggerInterface;

class ApiClientFactory
{
    /** @var LoggerInterface */
    private $logger;

    /** @var array|ApiClient[]  */
    private $cache = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(ApiConfig $apiConfig): ApiClient
    {
        $cacheKey = $this->createCacheKey($apiConfig);

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = new ApiClient(
                $this->logger,
                $apiConfig,
                new EndpointFactory($apiConfig)
            );
        }

        return $this->cache[$cacheKey];
    }

    private function createCacheKey(ApiConfig $apiConfig): string
    {
        return md5(implode(',', [
            $apiConfig->getApiUrl(),
            $apiConfig->getListId(),
            $apiConfig->getPublicKey(),
            $apiConfig->getPrivateKey(),
        ]));
    }
}
