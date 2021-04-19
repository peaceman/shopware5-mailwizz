<?php

namespace n2305Mailwizz\Utils;

use n2305Mailwizz\n2305Mailwizz;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Shop\Shop;

class PluginConfig
{
    /** @var ConfigReader */
    private $configReader;

    /** @var ?Shop */
    private $shop;

    public function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    public function forShop(?Shop $shop): PluginConfig
    {
        $configService = new static($this->configReader);
        $configService->shop = $shop;

        return $configService;
    }

    public function get(string $key, $default = null)
    {
        return $this->fetchFromConfigReader($key) ?? $default;
    }

    public function getMwApiUrl(): ?string
    {
        return $this->fetchFromConfigReader('mwApiUrl');
    }

    public function getMwApiPublicKey(): ?string
    {
        return $this->fetchFromConfigReader('mwApiPublicKey');
    }

    public function getMwApiPrivateKey(): ?string
    {
        return $this->fetchFromConfigReader('mwApiPrivateKey');
    }

    public function gtMwListId(): ?string
    {
        return $this->fetchFromConfigReader('mwListId');
    }

    protected function fetchFromConfigReader(string $key)
    {
        return $this->configReader->getByPluginName(n2305Mailwizz::PLUGIN_NAME, $this->shop)[$key] ?? null;
    }

    public function isActive(): bool
    {
        return $this->fetchFromConfigReader('activeForShop') ?? false;
    }
}
