<?php

declare(strict_types=1);

/*
 * This file is part of the `ddd-base` project.
 *
 * (c) Aula de Software Libre de la UCO <aulasoftwarelibre@uco.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AulaSoftwareLibre\DDD\BaseBundle\DependencyInjection;

use AulaSoftwareLibre\DDD\BaseBundle\Handlers\CommandHandler;
use AulaSoftwareLibre\DDD\BaseBundle\Handlers\EventHandler;
use AulaSoftwareLibre\DDD\BaseBundle\Handlers\QueryHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class BaseExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));

        $loader->load('services.xml');

        $container
            ->registerForAutoconfiguration(CommandHandler::class)
            ->setPublic(true)
            ->setAutowired(true)
            ->addTag('prooph_service_bus.default_command_bus.route_target', ['message_detection' => true]);

        $container
            ->registerForAutoconfiguration(EventHandler::class)
            ->setPublic(true)
            ->setAutowired(true)
            ->addTag('prooph_service_bus.default_event_bus.route_target', ['message_detection' => true]);

        $container
            ->registerForAutoconfiguration(QueryHandler::class)
            ->setPublic(true)
            ->setAutowired(true)
            ->addTag('prooph_service_bus.default_query_bus.route_target', ['message_detection' => true]);
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!$container->hasExtension('prooph_service_bus')) {
            throw new \LogicException('ProophServiceBusBundle not found');
        }

        $config = [];
        $config['command_buses']['default_command_bus'] = null;
        $config['event_buses']['default_event_bus'] = null;
        $config['query_buses']['default_query_bus'] = null;

        $container->prependExtensionConfig('prooph_service_bus', $config);
    }
}
