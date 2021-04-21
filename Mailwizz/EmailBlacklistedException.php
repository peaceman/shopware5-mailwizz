<?php

namespace n2305Mailwizz\Mailwizz;

class EmailBlacklistedException extends \RuntimeException
{
    /** @var string */
    private $email;

    public function __construct(string $email)
    {
        parent::__construct("Tried to export a blacklisted email: `$email`");

        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
