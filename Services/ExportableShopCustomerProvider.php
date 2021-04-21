<?php

namespace n2305Mailwizz\Services;

use Doctrine\ORM\Query\Expr\Join;
use n2305Mailwizz\Utils\PluginConfig;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Attribute\Customer as CustomerAttribute;

class ExportableShopCustomerProvider implements ShopCustomerProvider
{
    const CHUNK_SIZE = 25;

    /** @var ModelManager */
    private $modelManager;

    /** @var PluginConfig */
    private $pluginConfig;

    public function __construct(ModelManager $modelManager, PluginConfig $pluginConfig)
    {
        $this->modelManager = $modelManager;
        $this->pluginConfig = $pluginConfig;
    }

    public function fetch(Shop $shop)
    {
        $lastUserId = 0;

        $qb = $this->modelManager->createQueryBuilder();
        $qb->select('customer')
            ->from(Customer::class, 'customer')
            ->leftJoin(CustomerAttribute::class, 'attribute', Join::WITH, 'attribute.customer = customer.id')
            ->where($qb->expr()->isNull('attribute.mailwizzSubscriberId'))
            ->andWhere($qb->expr()->eq('customer.shop', ':shop'))
            ->andWhere($qb->expr()->gt('customer.id', ':lastUserId'))
            ->orderBy('customer.id', 'asc')
            ->setMaxResults(static::CHUNK_SIZE);

        $this->applyBlacklistedEmailSuffixesFilter($qb);

        $qb->setParameter('shop', $shop);

        $query = $qb->getQuery();

        while (true) {
            $query->setParameter('lastUserId', $lastUserId);

            $results = $query->getResult();
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

    private function applyBlacklistedEmailSuffixesFilter(QueryBuilder $qb)
    {
        $suffixes = $this->pluginConfig->getEmailBlacklistSuffixes();

        foreach ($suffixes as $idx => $suffix) {
            $qb->andWhere($qb->expr()->notLike('customer.email', ":bsuffix_$idx"));

            $suffix = addcslashes($suffix, '%_');
            $qb->setParameter("bsuffix_$idx", "%$suffix");
        }
    }
}
