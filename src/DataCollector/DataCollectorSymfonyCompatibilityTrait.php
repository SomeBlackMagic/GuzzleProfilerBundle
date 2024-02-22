<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

trait DataCollectorSymfonyCompatibilityTrait
{
    abstract protected function doCollect(Request $request, Response $response, Throwable $exception = null);

    /**
     * @param Request $request
     * @param Response $response
     * @param Throwable|null $exception
     *
     * @return void
     */
    public function collect(Request $request, Response $response, Throwable $exception = null): void
    {
        $this->doCollect($request, $response, $exception);
    }
}
