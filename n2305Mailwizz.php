<?php

namespace n2305Mailwizz;

use n2305Mailwizz\Bootstrap\Database;
use Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

require_once __DIR__ . '/vendor/autoload.php';

class n2305Mailwizz extends Plugin
{
    const PLUGIN_NAME = 'n2305Mailwizz';

    public function install(InstallContext $installContext)
    {
        $database = new Database(
            $this->container->get('models')
        );

        $database->install();
    }

    public function uninstall(UninstallContext $uninstallContext)
    {
        $database = new Database(
            $this->container->get('models')
        );

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $database->uninstall();
    }

    private function regenerateAttributeModels(array $attributeTables)
    {
        $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();

        Shopware()->Models()->generateAttributeModels($attributeTables);
    }
}
