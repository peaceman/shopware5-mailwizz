<?php

namespace n2305Mailwizz\Mailwizz;

class ApiConfig
{
    /** @var string */
    private $apiUrl;

    /** @var string */
    private $publicKey;

    /** @var string */
    private $privateKey;

    /** @var string */
    private $listId;

    public function __construct(
        string $apiUrl,
        string $publicKey,
        string $privateKey,
        string $listId
    ) {
        $this->apiUrl = $apiUrl;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->listId = $listId;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getListId(): string
    {
        return $this->listId;
    }
}
