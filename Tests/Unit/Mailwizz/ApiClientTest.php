<?php

namespace n2305Mailwizz\Tests\Unit\Mailwizz;

use n2305Mailwizz\Mailwizz\ApiClient;
use n2305Mailwizz\Mailwizz\ApiConfig;
use n2305Mailwizz\Mailwizz\EndpointFactory;
use n2305Mailwizz\Mailwizz\Subscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ApiClientTest extends TestCase
{
    public function createOrUpdateSubscriberDataProvider(): iterable
    {
        yield 'unconfirmed' => [
            'subscriber' => new Subscriber('foo@bar.com', 'first', 'last', true, 'subscriber-id'),
            'status' => Subscriber::STATE_UNCONFIRMED,
        ];

        yield 'confirmed' => [
            'subscriber' => new Subscriber('foo@bar.com', 'first', 'last', true, 'subscriber-id'),
            'status' => Subscriber::STATE_CONFIRMED,
        ];

        yield 'unsubscribed' => [
            'subscriber' => new Subscriber('foo@bar.com', 'first', 'last', true, 'subscriber-id'),
            'status' => Subscriber::STATE_UNSUBSCRIBED,
        ];
    }

    /** @dataProvider createOrUpdateSubscriberDataProvider */
    public function testCreateOrUpdateSubscriberWithSubscriberId(Subscriber $subscriber, string $status): void
    {
        $apiConfig = new ApiConfig('foo', 'foo', 'foo', 'list-id');

        $request = new \MailWizzApi_Http_Request(new \MailWizzApi_Http_Client());
        $request->params = new \MailWizzApi_Params();

        $response = new \MailWizzApi_Http_Response($request);

        $endpoint = $this->createMock(\MailWizzApi_Endpoint_ListSubscribers::class);
        $endpoint->expects(static::once())
            ->method('update')
            ->with(
                $apiConfig->getListId(),
                $subscriber->getSubscriberId(),
                [
                    'EMAIL' => $subscriber->getEmail(),
                    'FNAME' => $subscriber->getFirstName(),
                    'LNAME' => $subscriber->getLastName(),
                    'details' => [
                        'status' => $status
                    ],
                ]
            )
            ->willReturn($response);

        $endpointFactory = $this->createMock(EndpointFactory::class);
        $endpointFactory->expects(static::once())
            ->method('getListSubscribers')
            ->willReturn($endpoint);

        $apiClient = $this->createApiClient($apiConfig, $endpointFactory);
        $subscriberId = $apiClient->createOrUpdateSubscriber($subscriber, $status);
        static::assertEquals($subscriber->getSubscriberId(), $subscriberId);
    }

    public function createOrUpdateSubscriberWithoutSubscriberIdDataProvider(): iterable
    {
        yield 'unconfirmed' => [
            'subscriber' => new Subscriber('foo@bar.com', 'first', 'last', true, null),
            'status' => Subscriber::STATE_UNCONFIRMED,
        ];

        yield 'confirmed' => [
            'subscriber' => new Subscriber('foo@bar.com', 'first', 'last', true, null),
            'status' => Subscriber::STATE_CONFIRMED,
        ];

        yield 'unsubscribed' => [
            'subscriber' => new Subscriber('foo@bar.com', 'first', 'last', true, null),
            'status' => Subscriber::STATE_UNSUBSCRIBED,
        ];
    }

    /** @dataProvider createOrUpdateSubscriberWithoutSubscriberIdDataProvider */
    public function testCreateOrUpdateSubscriberWithoutSubscriberId(Subscriber $subscriber, string $status): void
    {
        $apiConfig = new ApiConfig('foo', 'foo', 'foo', 'list-id');

        $request = new \MailWizzApi_Http_Request(new \MailWizzApi_Http_Client());
        $request->params = new \MailWizzApi_Params();

        $response = new \MailWizzApi_Http_Response($request);
        $response->body = new \MailWizzApi_Params(['data' => ['record' => ['subscriber_uid' => 'foobar']]]);

        $endpoint = $this->createMock(\MailWizzApi_Endpoint_ListSubscribers::class);
        $endpoint->expects(static::once())
            ->method('createUpdate')
            ->with(
                $apiConfig->getListId(),
                [
                    'EMAIL' => $subscriber->getEmail(),
                    'FNAME' => $subscriber->getFirstName(),
                    'LNAME' => $subscriber->getLastName(),
                    'details' => [
                        'status' => $status
                    ],
                ]
            )
            ->willReturn($response);

        $endpointFactory = $this->createMock(EndpointFactory::class);
        $endpointFactory->expects(static::once())
            ->method('getListSubscribers')
            ->willReturn($endpoint);

        $apiClient = $this->createApiClient($apiConfig, $endpointFactory);
        $subscriberId = $apiClient->createOrUpdateSubscriber($subscriber, $status);
        static::assertEquals('foobar', $subscriberId);
    }

    public function testCreateOrUpdateSubscriberApiError(): void
    {
        $subscriber = new Subscriber('foo@bar.com', 'first', 'last', true, null);
        $status = Subscriber::STATE_UNSUBSCRIBED;

        $apiConfig = new ApiConfig('foo', 'foo', 'foo', 'list-id');

        $request = new \MailWizzApi_Http_Request(new \MailWizzApi_Http_Client());
        $request->params = new \MailWizzApi_Params();

        $response = new \MailWizzApi_Http_Response($request);
        $response->body = new \MailWizzApi_Params(['data' => ['record' => []]]);
        $response->setHttpCode(401);

        $endpoint = $this->createMock(\MailWizzApi_Endpoint_ListSubscribers::class);
        $endpoint->expects(static::once())
            ->method('createUpdate')
            ->with(
                $apiConfig->getListId(),
                [
                    'EMAIL' => $subscriber->getEmail(),
                    'FNAME' => $subscriber->getFirstName(),
                    'LNAME' => $subscriber->getLastName(),
                    'details' => [
                        'status' => $status
                    ],
                ]
            )
            ->willReturn($response);

        $endpointFactory = $this->createMock(EndpointFactory::class);
        $endpointFactory->expects(static::once())
            ->method('getListSubscribers')
            ->willReturn($endpoint);

        $apiClient = $this->createApiClient($apiConfig, $endpointFactory);
        $subscriberId = $apiClient->createOrUpdateSubscriber($subscriber, $status);
        static::assertNull($subscriberId);
    }

    public function testCreateOrUpdateSubscriberApiException(): void
    {
        $subscriber = new Subscriber('foo@bar.com', 'first', 'last', true, null);
        $status = Subscriber::STATE_UNSUBSCRIBED;

        $apiConfig = new ApiConfig('foo', 'foo', 'foo', 'list-id');

        $endpoint = $this->createMock(\MailWizzApi_Endpoint_ListSubscribers::class);
        $endpoint->expects(static::once())
            ->method('createUpdate')
            ->with(
                $apiConfig->getListId(),
                [
                    'EMAIL' => $subscriber->getEmail(),
                    'FNAME' => $subscriber->getFirstName(),
                    'LNAME' => $subscriber->getLastName(),
                    'details' => [
                        'status' => $status
                    ],
                ]
            )
            ->willThrowException(new \Exception('dis is broken'));

        $endpointFactory = $this->createMock(EndpointFactory::class);
        $endpointFactory->expects(static::once())
            ->method('getListSubscribers')
            ->willReturn($endpoint);

        $apiClient = $this->createApiClient($apiConfig, $endpointFactory);
        $subscriberId = $apiClient->createOrUpdateSubscriber($subscriber, $status);
        static::assertNull($subscriberId);
    }

    private function createApiClient(ApiConfig $apiConfig, EndpointFactory $endpointFactory): ApiClient
    {
        return new ApiClient(
            new NullLogger(),
            $apiConfig,
            $endpointFactory
        );
    }
}
