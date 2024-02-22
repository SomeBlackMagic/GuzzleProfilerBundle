<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\Events;

use function sprintf;

final class GuzzleEvents
{
    const PRE_TRANSACTION = 'guzzle_profiler.pre_transaction';

    const POST_TRANSACTION = 'guzzle_profiler.post_transaction';

    const EVENTS = [
        self::PRE_TRANSACTION,
        self::POST_TRANSACTION,
    ];

    public static function preTransactionFor(string $serviceName): string
    {
        return sprintf('%s.%s', self::PRE_TRANSACTION, $serviceName);
    }

    public static function postTransactionFor(string $serviceName): string
    {
        return sprintf('%s.%s', self::POST_TRANSACTION, $serviceName);
    }
}
