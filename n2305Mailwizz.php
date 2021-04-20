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

        /** @var CrudServiceInterface $crudService */
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->update('s_user_attributes', 'mailwizz_subscriber_id', 'text');

        $this->regenerateAttributeModels(['s_user_attributes']);
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

        /** @var CrudServiceInterface $crudService */
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->delete('s_user_attributes', 'mailwizz_subscriber_id');

        $this->regenerateAttributeModels(['s_user_attributes']);
    }

    private function regenerateAttributeModels(array $attributeTables): void
    {
        $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();

        Shopware()->Models()->generateAttributeModels($attributeTables);
    }
}
