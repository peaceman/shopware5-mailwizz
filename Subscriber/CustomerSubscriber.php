<?php

namespace n2305Mailwizz\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use n2305Mailwizz\Services\CustomerExportMode;
use Psr\Log\LoggerInterface;
use Shopware\Models\Customer\Customer;
use Throwable;

class CustomerSubscriber implements EventSubscriber
{
    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $enabled = true;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleModelEvent($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleModelEvent($args);
    }

    private function handleModelEvent(LifecycleEventArgs $args): void
    {
        if (!$this->enabled) return;

        try {
            $model = $args->getEntity();
            if (!($model instanceof Customer))
                return;

            // constructor dependency injection would cause a circular reference with the model manager
            $customerExporter = Shopware()->Container()->get('n2305_mailwizz.services.customer_exporter');
            $customerExporter->export($model, CustomerExportMode::adhocUpdate());
        } catch (Throwable $e) {
            $this->logger->error('An exception occurred during customer export', [
                'customer' => [
                    'id' => $model->getId(),
                    'email' => $model->getEmail(),
                ],
                'exception' => $e,
            ]);
        }
    }
}
