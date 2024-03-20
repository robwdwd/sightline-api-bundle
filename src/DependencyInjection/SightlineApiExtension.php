<?php
/*
 * This file is part of the Sightline API Bundle.
 *
 * Copyright 2022-2024 Robert Woodward
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Robwdwd\SightlineApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SightlineApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $containerBuilder): void
    {
        $xmlFileLoader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__ . '/../Resources/config'));
        $xmlFileLoader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definitionWebServices = $containerBuilder->getDefinition('robwdwd_sightline_api.sightline_web_service');
        $definitionWebServices->setArgument(2, $config);

        $definitionSOAP = $containerBuilder->getDefinition('robwdwd_sightline_api.sightline_soap');
        $definitionSOAP->setArgument(1, $config);

        $definitionRS = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.sightline_rest_api');
        $definitionRS->setArgument(2, $config);

        $definitionRS = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.sightline_rest_paged_api');
        $definitionRS->setArgument(2, $config);

        $definitionRSMO = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.sightline_managed_object_api');
        $definitionRSMO->setArgument(2, $config);

        $definitionRSMT = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.sightline_mitigation_template_api');
        $definitionRSMT->setArgument(2, $config);

        $definitionRSNG = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.sightline_notification_group_api');
        $definitionRSNG->setArgument(2, $config);

        $definitionRSTQ = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.sightline_traffic_query_api');
        $definitionRSTQ->setArgument(2, $config);
    }
}
