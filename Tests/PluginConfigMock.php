<?php

namespace n2305Mailwizz\Tests;

use n2305Mailwizz\Utils\PluginConfig;

class PluginConfigMock extends PluginConfig
{
    /** @var array */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function fetchFromConfigReader(string $key)
    {
        return $this->data[$key] ?? null;
    }
}
