<?php

namespace n2305Mailwizz\Services;

use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Attribute\Customer as CustomerAttribute;

class ExportableShopCustomerProvider implements ShopCustomerProvider
{
    const CHUNK_SIZE = 25;

    /** @var ModelManager */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function fetch(Shop $shop)
    {
        $lastUserId = null;

        while (true) {
            $qb = $this->modelManager->createQueryBuilder();
            $qb->select('customer')
                ->from(Customer::class, 'customer')
                ->leftJoin(CustomerAttribute::class, 'attribute', Join::WITH, 'attribute.customer = customer.id')
                ->where($qb->expr()->isNull('attribute.mailwizzSubscriberId'))
                ->andWhere($qb->expr()->eq('customer.shop', ':shop'))
                ->setParameter('shop', $shop)
                ->orderBy('customer.id', 'asc')
                ->setMaxResults(static::CHUNK_SIZE);

            if (!is_null($lastUserId)) {
                $qb->andWhere($qb->expr()->gt('customer.id', ':lastUserId'));
                $qb->setParameter('lastUserId', $lastUserId);
            }

            $results = $qb->getQuery()->getResult();
            $counter = 0;

            /** @var Customer $customer */
            foreach ($results as $customer) {
                yield $customer;

                $counter++;
                $lastUserId = $customer->getId();
            }

            if ($counter === 0) break;
        }
    }
}
