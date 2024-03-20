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

        $definitionWS = $containerBuilder->getDefinition('robwdwd_sightline_api.ws');
        $definitionWS->setArgument(2, $config);

        $definitionSOAP = $containerBuilder->getDefinition('robwdwd_sightline_api.soap');
        $definitionSOAP->setArgument(1, $config);

        $definitionRS = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.rest');
        $definitionRS->setArgument(2, $config);

        $definitionRS = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.paged');
        $definitionRS->setArgument(2, $config);

        $definitionRSMO = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.managed_object');
        $definitionRSMO->setArgument(2, $config);

        $definitionRSMT = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.mitigation_template');
        $definitionRSMT->setArgument(2, $config);

        $definitionRSNG = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.notification_group');
        $definitionRSNG->setArgument(2, $config);

        $definitionRSTQ = $containerBuilder->getDefinition('robwdwd_sightline_api.rest.traffic_query');
        $definitionRSTQ->setArgument(2, $config);
    }
}
