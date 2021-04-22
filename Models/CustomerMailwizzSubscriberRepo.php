<?php

namespace n2305Mailwizz\Models;

use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Customer\Customer;

class CustomerMailwizzSubscriberRepo extends ModelRepository
{
    /**
     * @param Customer $customer
     * @return CustomerMailwizzSubscriber|null
     */
    public function fetchForCustomer(Customer $customer)
    {
        return $this->fetchForCustomerId($customer->getId());
    }

    /**
     * @param int $customerId
     * @return CustomerMailwizzSubscriber|null
     */
    public function fetchForCustomerId(int $customerId)
    {
        return $this->createQueryBuilder('s')
            ->innerJoin(Customer::class, 'c', Join::WITH, 's.customer = c')
            ->where('c.id = :customerId')
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
