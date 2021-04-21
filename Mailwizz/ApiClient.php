<?php

namespace n2305Mailwizz\Mailwizz;

use MailWizzApi_Http_Response;
use Psr\Log\LoggerInterface;

class ApiClient
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ApiConfig */
    private $config;

    /** @var EndpointFactory */
    private $endpointFactory;

    public function __construct(
        LoggerInterface $logger,
        ApiConfig $config,
        EndpointFactory $endpointFactory
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->endpointFactory = $endpointFactory;
    }

    public function createOrUpdateSubscriber(Subscriber $subscriber, string $status)
    {
        $endpoint = $this->endpointFactory->getListSubscribers();
        $data = [
            'EMAIL' => $subscriber->getEmail(),
            'FNAME' => $subscriber->getFirstName(),
            'LNAME' => $subscriber->getLastName(),
            'details' => [
                'status' => $status,
            ],
        ];

        try {
            if ($subscriber->getSubscriberId()) {
                $response = $endpoint->update($this->config->getListId(), $subscriber->getSubscriberId(), $data);
                $this->checkForBlacklistedEmailError($response, $subscriber->getEmail());

                if (!empty($message = $response->getMessage())) {
                    $this->logger->error('An error occurred during mailwizz subscriber update', [
                        'subscriber' => $subscriber->asLoggingContext(),
                        'apiConfig' => $this->config->asLoggingContext(),
                        'message' => $message,
                    ]);

                    return null;
                }

                return $subscriber->getSubscriberId();
            } else {
                $response = $endpoint->createUpdate($this->config->getListId(), $data);
                $this->checkForBlacklistedEmailError($response, $subscriber->getEmail());

                if (!empty($message = $response->getMessage())) {
                    $this->logger->error('An error occurred during mailwizz subscriber create/update', [
                        'subscriber' => $subscriber->asLoggingContext(),
                        'apiConfig' => $this->config->asLoggingContext(),
                        'message' => $message,
                    ]);

                    return null;
                }

                return $response->body['data']['record']['subscriber_uid'] ?? null;
            }
        } catch (EmailBlacklistedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('An exception occurred during create or update of a mailwizz subscriber', [
                'subscriber' => $subscriber->asLoggingContext(),
                'apiConfig' => $this->config->asLoggingContext(),
                'exception' => $e,
            ]);

            return null;
        }
    }

    private function checkForBlacklistedEmailError(MailWizzApi_Http_Response $response, string $email): void
    {
        if ($response->getCode() != 409) return;

        $error = strtolower($response->body['error'] ?? '');

        if (strpos($error, 'blacklisted') !== false) {
            throw new EmailBlacklistedException($email);
        }
    }

    public function listExists(): bool
    {
        try {
            $listId = $this->config->getListId();
            $response = $this->endpointFactory->getLists()->getList($listId);

            if (!empty($message = $response->getMessage())) {
                $this->logger->error('An error occurred during mailwizz list check', [
                    'apiConfig' => $this->config->asLoggingContext(),
                    'message' => $message,
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('An exception occurred during mailwizz list check', [
                'apiConfig' => $this->config->asLoggingContext(),
                'exception' => $e,
            ]);

            return false;
        }
    }
}
