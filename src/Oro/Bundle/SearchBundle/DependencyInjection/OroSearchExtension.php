<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroSearchExtension extends Extension
{
    /**
     * Load configuration
     *
     * @param array                                                   $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('oro_search.log_queries', $config['log_queries']);

        $this->searchMappingsConfig($config, $container);

        $loader->load('engine/' . $config['engine'] . '.yml');

        if ($config['engine'] == 'orm' && count($config['engine_orm'])) {
            $this->remapParameters(
                $config,
                $container,
                array(
                     'engine_orm' => 'oro_search.engine_orm'
                )
            );
        }

        $container->setParameter('oro_search.realtime_update', $config['realtime_update']);

        $loader->load('services.yml');

        $container->setParameter('oro_search.twig.item_container_template', $config['item_container_template']);
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_search';
    }

    /**
     * Add search mapping config
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function searchMappingsConfig(array $config, ContainerBuilder $container)
    {
        $entitiesConfig = $config['entities_config'];
        if (!count($entitiesConfig)) {
            $configLoader = new CumulativeConfigLoader(
                'oro_search',
                new YamlCumulativeFileLoader('Resources/config/search.yml')
            );
            $resources    = $configLoader->load($container);
            foreach ($resources as $resource) {
                $entitiesConfig += $resource->data;
            }
        }
        $container->setParameter('oro_search.entities_config', $entitiesConfig);
    }

    /**
     * Remap parameters form to container params
     *
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param array                                                   $map
     */
    protected function remapParameters(array $config, ContainerBuilder $container, array $map)
    {
        foreach ($map as $name => $paramName) {
            if (array_key_exists($name, $config)) {
                $container->setParameter($paramName, $config[$name]);
            }
        }
    }
}
