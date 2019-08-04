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

use AulaSoftwareLibre\DDD\BaseBundle\MessageBus\CommandHandlerInterface;
use AulaSoftwareLibre\DDD\BaseBundle\MessageBus\EventHandlerInterface;
use AulaSoftwareLibre\DDD\BaseBundle\MessageBus\QueryHandlerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class BaseExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!$container->hasExtension('framework')) {
            throw new \LogicException('FrameworkBundle not found');
        }

        $config = [];

        $config['messenger']['default_bus'] = 'command.bus';
        $config['messenger']['buses']['command.bus']['middleware']['validation'] = null;
        $config['messenger']['buses']['command.bus']['middleware']['doctrine_transaction'] = null;
        $config['messenger']['buses']['event.bus']['middleware']['validation'] = null;
        $config['messenger']['buses']['event.bus']['default_middleware'] = 'allow_no_handlers';
        $config['messenger']['buses']['query.bus']['middleware']['validation'] = null;

        $container->prependExtensionConfig('framework', $config);

        $container
            ->registerForAutoconfiguration(CommandHandlerInterface::class)
            ->addTag('messenger.message_handler', ['bus' => 'command.bus']);

        $container
            ->registerForAutoconfiguration(EventHandlerInterface::class)
            ->addTag('messenger.message_handler', ['bus' => 'event.bus']);

        $container
            ->registerForAutoconfiguration(QueryHandlerInterface::class)
            ->addTag('messenger.message_handler', ['bus' => 'query.bus']);
    }
}
