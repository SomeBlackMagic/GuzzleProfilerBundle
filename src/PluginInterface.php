<?php

namespace SomeBlackMagic\GuzzleProfilerBundle;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

interface PluginInterface
{
    /**
     * The name of this plugin. It will be used as the configuration key.
     *
     * @return string
     */
    public function getPluginName() : string;

    /**
     * @param ArrayNodeDefinition $pluginNode
     *
     * @return void
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode) : void;

    /**
     * Load this plugin: define services, load service definition files, etc.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container) : void;

    /**
     * Add configuration nodes for this plugin to the provided node.
     *
     * @param array $config
     * @param ContainerBuilder $container
     * @param string $clientName
     * @param Definition $handler
     *
     * @return void
     */
    public function loadForClient(array $config, ContainerBuilder $container, string $clientName, Definition $handler) : void;

    /**
     * When the container is generated for the first time, you can register compiler passes inside this method.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container);

    /**
     * When the bundles are booted, you can do any runtime initialization required inside this method.
     */
    public function boot();
}
