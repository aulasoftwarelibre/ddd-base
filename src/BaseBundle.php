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

namespace AulaSoftwareLibre\DDD\BaseBundle;

use AulaSoftwareLibre\DDD\BaseBundle\DependencyInjection\BaseExtension;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BaseBundle extends Bundle
{
    public function getContainerExtension(): Extension
    {
        return new BaseExtension();
    }
}
