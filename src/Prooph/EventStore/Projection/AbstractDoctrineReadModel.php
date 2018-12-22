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

namespace AulaSoftwareLibre\DDD\BaseBundle\Prooph\EventStore\Projection;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use LogicException;
use Prooph\EventStore\Projection\AbstractReadModel;

abstract class AbstractDoctrineReadModel extends AbstractReadModel
{
    /**
     * @var EntityManagerInterface|null
     */
    private $manager;
    /**
     * @var ClassMetadata
     */
    private $_class;

    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        $this->manager = $registry->getManagerForClass($entityClass);
        if (null === $this->manager) {
            throw new LogicException(sprintf(
                'Could not find the entity manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this entity’s metadata.',
                $entityClass
            ));
        }

        $this->_class = $this->manager->getClassMetadata($entityClass);
    }

    public function init(): void
    {
        $schemaTool = new SchemaTool($this->manager);
        $schemaTool->createSchema([$this->_class]);
    }

    public function isInitialized(): bool
    {
        $tableName = $this->_class->getTableName();
        $tables = $this->getConnection()->getSchemaManager()->listTables();

        return !empty(
            array_filter($tables, function (Table $table) use ($tableName) {
                return $table->getName() === $tableName;
            })
        );
    }

    public function reset(): void
    {
        $tableName = $this->_class->getTableName();
        $stmt = $this->getConnection()->getDatabasePlatform()->getTruncateTableSQL($tableName);
        $this->getConnection()->prepare($stmt)->execute();
    }

    public function delete(): void
    {
        $tableName = $this->_class->getTableName();
        $stmt = $this->getConnection()->getDatabasePlatform()->getDropTableSQL($tableName);
        $this->getConnection()->prepare($stmt)->execute();
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection(): \Doctrine\DBAL\Connection
    {
        return $this->manager->getConnection();
    }
}
