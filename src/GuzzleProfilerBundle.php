<?php

namespace SomeBlackMagic\GuzzleProfilerBundle;

use SomeBlackMagic\GuzzleProfilerBundle\DependencyInjection\GuzzleProfilerBundleExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class GuzzleProfilerBundle extends Bundle
{
    /**
     * Overwrite getContainerExtension
     *  - no naming convention of alias needed
     *  - extension class can be moved easily now
     *
     * @return ExtensionInterface The container extension
     */
    public function getContainerExtension() : ExtensionInterface
    {
       return new GuzzleProfilerBundleExtension();
    }

}
