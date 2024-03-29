<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\Events;

use Psr\Http\Message\ResponseInterface;

class PostTransactionEvent extends Event
{
    /** @var ResponseInterface|null */
    protected $response;

    /** @var string */
    protected $serviceName;

    /**
     * @param ResponseInterface|null $response
     * @param string $serviceName
     */
    public function __construct(?ResponseInterface $response, string $serviceName)
    {
        $this->response = $response;
        $this->serviceName = $serviceName;
    }

    /**
     * Get the transaction from the event.
     *
     * This returns the transaction we are working with.
     *
     * @return ResponseInterface|null
     */
    public function getTransaction() : ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Sets the transaction inline with the event.
     *
     * @param ResponseInterface|null $response
     *
     * @return void
     */
    public function setTransaction(?ResponseInterface $response) : void
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getServiceName() : string
    {
        return $this->serviceName;
    }
}
