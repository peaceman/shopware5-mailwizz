<?php

namespace n2305Mailwizz\Mailwizz;

class EndpointFactory
{
    /** @var ApiConfig */
    private $apiConfig;

    /** @var \MailWizzApi_Config */
    private $mailwizzConfig;

    /** @var array */
    private $endpointCache = [];

    public function __construct(ApiConfig $config)
    {
        $this->apiConfig = $config;
    }

    private function getMailwizzConfig(): \MailWizzApi_Config
    {
        if (is_null($config = $this->mailwizzConfig)) {
            $config = $this->mailwizzConfig = $this->createMailwizzConfig($this->apiConfig);
        }

        return $config;
    }

    private function createMailwizzConfig(ApiConfig $config): \MailWizzApi_Config
    {
        return new \MailWizzApi_Config([
            'apiUrl' => $config->getApiUrl(),
            'publicKey' => $config->getPublicKey(),
            'privateKey' => $config->getPrivateKey(),
        ]);
    }

    public function getListSubscribers(): \MailWizzApi_Endpoint_ListSubscribers
    {
        $endpoint = $this->getEndpoint(\MailWizzApi_Endpoint_ListSubscribers::class);
        $endpoint::setConfig($this->getMailwizzConfig());

        return $endpoint;
    }

    public function getLists(): \MailWizzApi_Endpoint_Lists
    {
        $endpoint = $this->getEndpoint(\MailWizzApi_Endpoint_Lists::class);
        $endpoint::setConfig($this->getMailwizzConfig());

        return $endpoint;
    }

    private function getEndpoint(string $class)
    {
        if (is_null($endpoint = $this->endpointCache[$class] ?? null)) {
            $endpoint = $this->endpointCache[$class] = new $class();
        }

        return $endpoint;
    }
}
