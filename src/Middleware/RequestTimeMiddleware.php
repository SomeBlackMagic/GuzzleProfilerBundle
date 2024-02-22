<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\Middleware;

use Closure;
use SomeBlackMagic\GuzzleProfilerBundle\Log\Logger;
use SomeBlackMagic\GuzzleProfilerBundle\Log\LoggerInterface;
use SomeBlackMagic\GuzzleProfilerBundle\DataCollector\HttpDataCollector;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\TransferStats;

class RequestTimeMiddleware
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var HttpDataCollector */
    private $dataCollector;

    /**
     * @param LoggerInterface $logger
     * @param HttpDataCollector $dataCollector
     */
    public function __construct(LoggerInterface $logger, HttpDataCollector $dataCollector)
    {
        $this->logger = $logger;
        $this->dataCollector = $dataCollector;
    }

    /**
     * @param callable $handler
     *
     * @return Closure
     */
    public function __invoke(callable $handler) : Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $options['on_stats'] = $this->getOnStatsCallback(
                isset($options['on_stats']) ? $options['on_stats'] : null,
                isset($options['request_id']) ? $options['request_id'] : null
            );

            // Continue the handler chain.
            return $handler($request, $options);
        };
    }

    /**
     * Create callback for on_stats options.
     * If request has on_stats option, it will be called inside of this callback.
     *
     * @param null|callable $initialOnStats
     * @param null|string $requestId
     *
     * @return Closure
     */
    protected function getOnStatsCallback(?callable $initialOnStats, ?string $requestId) : Closure
    {
        return function (TransferStats $stats) use ($initialOnStats, $requestId) {
            if (is_callable($initialOnStats)) {
                call_user_func($initialOnStats, $stats);
            }

            $this->dataCollector->addTotalTime((float)$stats->getTransferTime());

            if (($this->logger instanceof Logger) && $requestId) {
                $this->logger->addTransferTimeByRequestId($requestId, (float)$stats->getTransferTime());
            }
        };
    }
}
