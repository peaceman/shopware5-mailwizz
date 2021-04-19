<?php

namespace n2305Mailwizz\Services;

class CustomerExportMode
{
    private const PERIODIC_IMPORT = 'periodic-import';
    private const ADHOC_UPDATE = 'adhoc-update';

    private function __construct(string $mode)
    {
        $this->mode = $mode;
    }

    public static function periodicImport(): self
    {
        return new self(self::PERIODIC_IMPORT);
    }

    public static function adhocUpdate(): self
    {
        return new self(self::ADHOC_UPDATE);
    }

    public function isPeriodicImport(): bool
    {
        return $this->mode === self::PERIODIC_IMPORT;
    }

    public function isAdhocUpdate(): bool
    {
        return $this->mode === self::ADHOC_UPDATE;
    }
}
