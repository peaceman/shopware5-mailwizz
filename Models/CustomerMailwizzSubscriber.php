<?php

namespace n2305Mailwizz\Models;

use DateTimeImmutable;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Customer\Customer;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CustomerMailwizzSubscriberRepo")
 * @ORM\Table(name="n2305_user_mailwizz_subscriber")
 * @ORM\HasLifecycleCallbacks
 */
class CustomerMailwizzSubscriber extends ModelEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Customer
     *
     * @ORM\OneToOne(targetEntity="Shopware\Models\Customer\Customer")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade", unique=true)
     */
    private $customer;

    /**
     * @var string
     *
     * @ORM\Column(name="subscriber_id", type="string", length=32)
     */
    private $subscriberId;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="created_at", type="datetimetz_immutable", nullable=false)
     */
    private $createdAt;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="updated_at", type="datetimetz_immutable", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSubscriberId()
    {
        return $this->subscriberId;
    }

    public function setSubscriberId($subscriberId)
    {
        $this->subscriberId = $subscriberId;
    }
}
