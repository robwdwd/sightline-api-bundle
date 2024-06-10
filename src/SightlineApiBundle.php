<?php

/*
 * This file is part of the Sightline API Bundle.
 *
 * Copyright 2022-2024 Robert Woodward
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Robwdwd\SightlineApiBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Sightline API Symfony Bundle.
 */
class SightlineApiBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('hostname')
            ->info('Hostname of the Sightline Leader.')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('wskey')
            ->info('Web services API key.')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('resttoken')
            ->info('REST API Token.')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('username')
            ->info('SOAP Username.')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('password')
            ->info('SOAP Password.')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('wsdl')
            ->info('WSDL description file for Sightline SOAP APIs.')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->booleanNode('cache')
            ->info('Turn caching on or off.')
            ->defaultFalse()
            ->end()
            ->integerNode('cache_ttl')
            ->info('Time to live of cached responses.')
            ->defaultValue(900)
            ->min(1)
            ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // load an XML, PHP or YAML file
        $container->import('../config/services.xml');

        $definitionWebServices = $builder->getDefinition('robwdwd_sightline_api.sightline_web_service');
        $definitionWebServices->setArgument(2, $config);

        $definitionSOAP = $builder->getDefinition('robwdwd_sightline_api.sightline_soap');
        $definitionSOAP->setArgument(1, $config);

        $definitionRS = $builder->getDefinition('robwdwd_sightline_api.rest.sightline_rest_api');
        $definitionRS->setArgument(2, $config);

        $definitionRS = $builder->getDefinition('robwdwd_sightline_api.rest.sightline_rest_paged_api');
        $definitionRS->setArgument(2, $config);

        $definitionRSMO = $builder->getDefinition('robwdwd_sightline_api.rest.sightline_managed_object_api');
        $definitionRSMO->setArgument(2, $config);

        $definitionRSMT = $builder->getDefinition('robwdwd_sightline_api.rest.sightline_mitigation_template_api');
        $definitionRSMT->setArgument(2, $config);

        $definitionRSNG = $builder->getDefinition('robwdwd_sightline_api.rest.sightline_notification_group_api');
        $definitionRSNG->setArgument(2, $config);

        $definitionRSTQ = $builder->getDefinition('robwdwd_sightline_api.rest.sightline_traffic_query_api');
        $definitionRSTQ->setArgument(2, $config);
    }
}
