<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\Middleware;

use Closure;
use GuzzleHttp\Promise\Create;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * This Middleware is used to render request time slot on "Performance" tab in "Symfony Profiler".
 */
class ProfileMiddleware
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @param Stopwatch $stopwatch
     */
    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * Profiling each Request
     *
     * @return Closure
     */
    public function profile() : Closure
    {
        $stopwatch = $this->stopwatch;

        return function (callable $handler) use ($stopwatch) {

            return function ($request, array $options) use ($handler, $stopwatch) {
                $event = $stopwatch->start(
                    sprintf('%s %s', $request->getMethod(), $request->getUri()),
                    'guzzle_profiler'
                );

                return $handler($request, $options)->then(

                    function ($response) use ($event) {
                        $event->stop();

                        return $response;
                    },

                    function ($reason) use ($event) {
                        $event->stop();

                        return Create::rejectionFor($reason);
                    }
                );
            };
        };
    }
}
