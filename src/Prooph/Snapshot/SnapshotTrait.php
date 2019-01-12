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

namespace AulaSoftwareLibre\DDD\BaseBundle\Prooph\Snapshot;

use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\SnapshotStore\Snapshot;
use Prooph\SnapshotStore\SnapshotStore;

trait SnapshotTrait
{
    /**
     * @var SnapshotStore|null
     */
    protected $snapshotStore;

    protected function saveSnapshot(AggregateRoot $aggregateRoot)
    {
        $version = (function () { return $this->version; })->call($aggregateRoot);

        if (1 !== $version % 20) {
            return;
        }

        $this->snapshotStore->save(
            new Snapshot(
                AggregateType::fromAggregateRoot($aggregateRoot)->toString(),
                $aggregateRoot->aggregateId(),
                $aggregateRoot,
                $version,
                new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
            )
        );
    }
}
