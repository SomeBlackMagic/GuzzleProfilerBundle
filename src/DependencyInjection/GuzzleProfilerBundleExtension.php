<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\DependencyInjection;

use Exception;
use SomeBlackMagic\GuzzleProfilerBundle\Log\Logger;
use SomeBlackMagic\GuzzleProfilerBundle\Twig\Extension\DebugExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\ExpressionLanguage\Expression;

class GuzzleProfilerBundleExtension extends ConfigurableExtension
{

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container) : Configuration
    {
        return new Configuration($this->getAlias(), $container->getParameter('kernel.debug'));
    }

    /**
     * Loads the Guzzle configuration.
     *
     * @param array $config
     * @param ContainerBuilder $container a ContainerBuilder instance
     *
     * @return void
     * @throws Exception
     */
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $configPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Resources', 'config']);
        $loader     = new XmlFileLoader($container, new FileLocator($configPath));

        $loader->load('services.xml');


        $logging       = $config['logging'] === true;
        $profiling     = $config['profiling'] === true;


        $this->createHandler($container, $config, $profiling);


        $this->defineTwigDebugExtension($container);
        $this->defineDataCollector($container, $config['slow_response_time'] / 1000);
        $this->defineFormatter($container);
        $this->defineSymfonyLogFormatter($container);
        $this->defineSymfonyLogMiddleware($container);
    }

    /**
     * @param ContainerBuilder $container
     * @param array $options
     * @param bool $profiling
     *
     *@throws InvalidArgumentException
     *
     * @throws BadMethodCallException
     */
    protected function createHandler(ContainerBuilder $container, array $options, bool $profiling): void
    {
        // Event Dispatching service
        $eventService = $this->createEventMiddleware();
        $container->setDefinition('guzzle_profiler.middleware.event_dispatch', $eventService);

        if ($profiling) {
            $this->defineProfileMiddleware($container);
        }

        $logMode = $this->convertLogMode($options['logging']);
        if ($logMode > Logger::LOG_MODE_NONE) {
            $loggerName = $this->defineLogger($container, $logMode);
            $this->defineLogMiddleware($container, $loggerName);
            $this->defineRequestTimeMiddleware($container, $loggerName);
        }
    }

    /**
     * @param  int|bool $logMode
     * @return int
     */
    private function convertLogMode($logMode) : int
    {
        if ($logMode === true) {
            return Logger::LOG_MODE_REQUEST_AND_RESPONSE;
        } elseif ($logMode === false) {
            return Logger::LOG_MODE_NONE;
        } else {
           return $logMode;
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function defineTwigDebugExtension(ContainerBuilder $container) : void
    {
        $twigDebugExtensionDefinition = new Definition(DebugExtension::class);
        $twigDebugExtensionDefinition->addTag('twig.extension');
        $twigDebugExtensionDefinition->setPublic(false);
        $container->setDefinition('guzzle_profiler.twig_extension.debug', $twigDebugExtensionDefinition);
    }

    /**
     * Define Logger
     *
     * @param ContainerBuilder $container
     * @param int $logMode
     *
     * @return void
     */
    protected function defineLogger(ContainerBuilder $container, int $logMode): string
    {
        $loggerDefinition = new Definition('%guzzle_profiler.logger.class%');
        $loggerDefinition->setPublic(false);
        $loggerDefinition->setArgument(0, $logMode);
        $loggerDefinition->addTag('guzzle_profiler.logger');

        $container->setDefinition('guzzle_profiler.logger', $loggerDefinition);

        return 'guzzle_profiler.logger';
    }

    /**
     * Define Data Collector
     *
     * @param ContainerBuilder $container
     * @param float $slowResponseTime
     *
     * @return void
     *@throws BadMethodCallException
     *
     */
    protected function defineDataCollector(ContainerBuilder $container, float $slowResponseTime) : void
    {
        $dataCollectorDefinition = new Definition('%guzzle_profiler.data_collector.class%');
        $dataCollectorDefinition->addArgument(array_map(function($loggerId) : Reference {
            return new Reference($loggerId);
        }, array_keys($container->findTaggedServiceIds('guzzle_profiler.logger'))));

        $dataCollectorDefinition->addArgument($slowResponseTime);
        $dataCollectorDefinition->setPublic(false);
        $dataCollectorDefinition->addTag('data_collector', [
            'id' => 'guzzle_profiler',
            'template' => '@GuzzleProfiler/debug.html.twig',
        ]);
        $container->setDefinition('guzzle_profiler.data_collector', $dataCollectorDefinition);
    }

    /**
     * Define Formatter
     *
     * @param ContainerBuilder $container
     *
     * @return void
     *@throws BadMethodCallException
     *
     */
    protected function defineFormatter(ContainerBuilder $container) : void
    {
        $formatterDefinition = new Definition('%guzzle_profiler.formatter.class%');
        $formatterDefinition->setPublic(true);
        $container->setDefinition('guzzle_profiler.formatter', $formatterDefinition);
    }

    /**
     * Define Request Time Middleware
     *
     * @param ContainerBuilder $container
     * @param string $loggerName
     *
     * @return void
     */
    protected function defineRequestTimeMiddleware(ContainerBuilder $container, string $loggerName) : void
    {
        $requestTimeMiddlewareDefinition = new Definition('%guzzle_profiler.middleware.request_time.class%');
        $requestTimeMiddlewareDefinition->addArgument(new Reference($loggerName));
        $requestTimeMiddlewareDefinition->addArgument(new Reference('guzzle_profiler.data_collector'));
        $requestTimeMiddlewareDefinition->setPublic(true);
        $container->setDefinition('guzzle_profiler.middleware.request_time', $requestTimeMiddlewareDefinition);
    }

    /**
     * Define Log Middleware for client
     *
     * @param ContainerBuilder $container
     * @param string $loggerName
     *
     * @return void
     */
    protected function defineLogMiddleware(ContainerBuilder $container, string $loggerName) : void
    {
        $logMiddlewareDefinition = new Definition('%guzzle_profiler.middleware.log.class%');
        $logMiddlewareDefinition->addArgument(new Reference($loggerName));
        $logMiddlewareDefinition->addArgument(new Reference('guzzle_profiler.formatter'));
        $logMiddlewareDefinition->setPublic(true);
        $container->setDefinition('guzzle_profiler.middleware.log', $logMiddlewareDefinition);
    }

    /**
     * Define Profile Middleware for client
     *
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function defineProfileMiddleware(ContainerBuilder $container) : void
    {
        $profileMiddlewareDefinition = new Definition('%guzzle_profiler.middleware.profile.class%');
        $profileMiddlewareDefinition->addArgument(new Reference('debug.stopwatch'));
        $profileMiddlewareDefinition->setPublic(true);
        $container->setDefinition('guzzle_profiler.middleware.profile', $profileMiddlewareDefinition);
    }


    /**
     * Create Middleware For dispatching events
     *
     * @return Definition
     */
    protected function createEventMiddleware() : Definition
    {
        $eventMiddleWare = new Definition('%guzzle_profiler.middleware.event_dispatcher.class%');
        $eventMiddleWare->addArgument(new Reference('event_dispatcher'));
        $eventMiddleWare->setPublic(true);

        return $eventMiddleWare;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function defineSymfonyLogFormatter(ContainerBuilder $container) : void
    {
        $formatterDefinition = new Definition('%guzzle_profiler.symfony_log_formatter.class%');
        $formatterDefinition->setArguments(['%guzzle_profiler.symfony_log_formatter.pattern%']);
        $formatterDefinition->setPublic(true);
        $container->setDefinition('guzzle_profiler.symfony_log_formatter', $formatterDefinition);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function defineSymfonyLogMiddleware(ContainerBuilder $container) : void
    {
        $logMiddlewareDefinition = new Definition('%guzzle_profiler.middleware.symfony_log.class%');
        $logMiddlewareDefinition->addArgument(new Reference('logger'));
        $logMiddlewareDefinition->addArgument(new Reference('guzzle_profiler.symfony_log_formatter'));
        $logMiddlewareDefinition->setPublic(true);
        $logMiddlewareDefinition->addTag('monolog.logger', ['channel' => 'guzzle_profiler']);
        $container->setDefinition('guzzle_profiler.middleware.symfony_log', $logMiddlewareDefinition);
    }

    /**
     * Returns alias of extension
     *
     * @return string
     */
    public function getAlias() : string
    {
        return 'guzzle_profiler';
    }
}
