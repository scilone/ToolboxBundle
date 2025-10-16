<?php

namespace SciloneToolboxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SciloneToolboxBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Configure FixtureManager service if Elasticsearch client is available and FixtureManager not already defined
        if ($container->hasDefinition('Elasticsearch\Client') && !$container->hasDefinition('SciloneToolboxBundle\Elasticsearch\FixtureManager')) {
            $container->register('SciloneToolboxBundle\Elasticsearch\FixtureManager', 'SciloneToolboxBundle\Elasticsearch\FixtureManager')
                ->addArgument($container->getDefinition('Elasticsearch\Client'))
                ->addArgument('%kernel.project_dir%/elasticsearch-fixtures');
        }
    }
}