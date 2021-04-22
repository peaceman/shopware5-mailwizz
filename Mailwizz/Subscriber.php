<?php

namespace n2305Mailwizz\Mailwizz;

use n2305Mailwizz\Models\CustomerMailwizzSubscriberRepo;
use Shopware\Models\Customer\Customer;

class Subscriber
{
    const STATE_CONFIRMED = 'confirmed';
    const STATE_UNCONFIRMED = 'unconfirmed';
    const STATE_UNSUBSCRIBED = 'unsubscribed';

    const SUBSCRIBER_ID_BLACKLISTED = 'blacklisted';

    /** @var string */
    private $email;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    /** @var bool*/
    private $wantsSubscription;

    /** @var ?string */
    private $subscriberId;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        bool $wantsSubscription,
        $subscriberId
    ) {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->wantsSubscription = $wantsSubscription;
        $this->subscriberId = $subscriberId;
    }

    public static function createFromCustomer(
        Customer $customer,
        CustomerMailwizzSubscriberRepo $subscriberRepo
    ): self {
        $subscriber = $subscriberRepo->fetchForCustomer($customer);
        $subscriberId = $subscriber ? $subscriber->getSubscriberId() : null;

        return new static(
            $customer->getEmail(),
            $customer->getFirstname() ?? '',
            $customer->getLastname() ?? '',
            (bool) $customer->getNewsletter(),
            $subscriberId
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function wantsSubscription(): bool
    {
        return $this->wantsSubscription;
    }

    public function getSubscriberId()
    {
        return $this->subscriberId;
    }

    public function isBlacklisted(): bool
    {
        return $this->subscriberId === self::SUBSCRIBER_ID_BLACKLISTED;
    }

    public function asLoggingContext(): array
    {
        return [
            'email' => $this->email,
            'subscriberId' => $this->subscriberId,
            'wantsSubscription' => $this->wantsSubscription,
        ];
    }
}
