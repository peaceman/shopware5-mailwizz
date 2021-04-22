<?php

namespace n2305Mailwizz\Bootstrap;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use n2305Mailwizz\Models\CustomerMailwizzSubscriber;

class Database
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var SchemaTool
     */
    private $schemaTool;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->schemaTool = new SchemaTool($this->entityManager);
    }

    /**
     * Installs all registered ORM classes
     */
    public function install()
    {
        $this->schemaTool->updateSchema(
            $this->getClassesMetaData(),
            true // make sure to use the save mode
        );
    }

    /**
     * Drops all registered ORM classes
     */
    public function uninstall()
    {
        $this->schemaTool->dropSchema(
            $this->getClassesMetaData()
        );
    }

    /**
     * @return array
     */
    private function getClassesMetaData()
    {
        return [
            $this->entityManager->getClassMetadata(CustomerMailwizzSubscriber::class),
        ];
    }
}
