<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\Middleware;

use Closure;
use GuzzleHttp\Promise\Create;
use SomeBlackMagic\GuzzleProfilerBundle\Events\Event;
use SomeBlackMagic\GuzzleProfilerBundle\Events\PostTransactionEvent;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;
use SomeBlackMagic\GuzzleProfilerBundle\Events\GuzzleEvents;
use SomeBlackMagic\GuzzleProfilerBundle\Events\PreTransactionEvent;
use Throwable;

/**
 * Dispatches an Event using the Symfony Event Dispatcher.
 * Dispatches a PRE_TRANSACTION event, before the transaction is sent
 * Dispatches a POST_TRANSACTION event, when the remote hosts responds.
 */
class EventDispatchMiddleware
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var string */
    private $serviceName;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $serviceName
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, string $serviceName)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->serviceName = $serviceName;
    }

    /**
     * @return Closure
     */
    public function dispatchEvent() : Closure
    {
        return function (callable $handler) {

            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {
                // Create the Pre Transaction event.
                $preTransactionEvent = new PreTransactionEvent($request, $this->serviceName);

                // Dispatch it through the symfony Dispatcher.
                $this->doDispatch($preTransactionEvent, GuzzleEvents::PRE_TRANSACTION);
                $this->doDispatch($preTransactionEvent, GuzzleEvents::preTransactionFor($this->serviceName));

                // Continue the handler chain.
                $promise = $handler($preTransactionEvent->getTransaction(), $options);

                // Handle the response form the server.
                return $promise->then(
                    function (ResponseInterface $response) {
                        // Create the Post Transaction event.
                        $postTransactionEvent = new PostTransactionEvent($response, $this->serviceName);

                        // Dispatch the event on the symfony event dispatcher.
                        $this->doDispatch($postTransactionEvent, GuzzleEvents::POST_TRANSACTION);
                        $this->doDispatch($postTransactionEvent, GuzzleEvents::postTransactionFor($this->serviceName));

                        // Continue down the chain.
                        return $postTransactionEvent->getTransaction();
                    },
                    function (Throwable $reason) {
                        // Get the response. The response in a RequestException can be null too.
                        $response = $reason instanceof RequestException ? $reason->getResponse() : null;

                        // Create the Post Transaction event.
                        $postTransactionEvent = new PostTransactionEvent($response, $this->serviceName);

                        // Dispatch the event on the symfony event dispatcher.
                        $this->doDispatch($postTransactionEvent, GuzzleEvents::POST_TRANSACTION);
                        $this->doDispatch($postTransactionEvent, GuzzleEvents::postTransactionFor($this->serviceName));

                        // Continue down the chain.
                        return Create::rejectionFor($reason);
                    }
                );
            };
        };
    }

    private function doDispatch(Event $event, string $name): void
    {
        if ($this->eventDispatcher instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcher->dispatch($event, $name);

            return;
        }

        // BC compatibility
        $this->eventDispatcher->dispatch($name, $event);
    }
}
