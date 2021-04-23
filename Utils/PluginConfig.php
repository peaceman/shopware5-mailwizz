<?php

namespace n2305Mailwizz\Utils;

use n2305Mailwizz\Mailwizz;
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

    public function forShop($shop): PluginConfig
    {
        $configService = new static($this->configReader);
        $configService->shop = $shop;

        return $configService;
    }

    public function get(string $key, $default = null)
    {
        return $this->fetchFromConfigReader($key) ?? $default;
    }

    public function getMwApiUrl()
    {
        return $this->fetchFromConfigReader('mwApiUrl');
    }

    public function getMwApiPublicKey()
    {
        return $this->fetchFromConfigReader('mwApiPublicKey');
    }

    public function getMwApiPrivateKey()
    {
        return $this->fetchFromConfigReader('mwApiPrivateKey');
    }

    public function getMwListId()
    {
        return $this->fetchFromConfigReader('mwListId');
    }

    public function getMwApiConfig(): Mailwizz\ApiConfig
    {
        return new Mailwizz\ApiConfig(
            $this->getMwApiUrl(),
            $this->getMwApiPublicKey(),
            $this->getMwApiPrivateKey(),
            $this->getMwListId()
        );
    }

    public function getEmailBlacklistSuffixes(): array
    {
        $val = $this->fetchFromConfigReader('emailBlacklistSuffixes');
        $suffixes = explode(',', $val);

        $suffixes = array_map(static function ($v): string {
            return trim($v);
        }, $suffixes);

        $suffixes = array_filter($suffixes, static function ($v): string {
            return !empty($v);
        });

        return array_values($suffixes);
    }

    public function getIgnoreUserDecision(): bool
    {
        return $this->fetchFromConfigReader('ignoreUserDecision') ?? false;
    }

    public function hasConfiguredMailwizz(): bool
    {
        static $requiredKeys = ['mwApiUrl', 'mwApiPublicKey', 'mwApiPrivateKey', 'mwListId'];

        foreach ($requiredKeys as $requiredKey) {
            if (empty($this->get($requiredKey)))
                return false;
        }

        return $this->isActive();
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
