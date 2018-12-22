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

        $config['messenger']['buses']['messenger.bus.commands'] = null;
        $config['messenger']['buses']['messenger.bus.events'] = null;
        $config['messenger']['buses']['messenger.bus.query'] = null;

        $container->prependExtensionConfig('framework', $config);
    }
}
