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

namespace AulaSoftwareLibre\DDD\BaseBundle\Prooph\EventStoreBusBridge;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\AbstractPlugin;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventPublisher extends AbstractPlugin
{
    /**
     * @var MessageBusInterface
     */
    private $eventBus;
    /**
     * @var \Iterator[]
     */
    private $cachedEventStreams = [];

    public function __construct(MessageBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use ($eventStore): void {
                $recordedEvents = $event->getParam('streamEvents', new \ArrayIterator());

                if (!$this->inTransaction($eventStore)) {
                    if ($event->getParam('streamNotFound', false)
                        || $event->getParam('concurrencyException', false)
                    ) {
                        return;
                    }

                    foreach ($recordedEvents as $recordedEvent) {
                        $this->eventBus->dispatch($recordedEvent);
                    }
                } else {
                    $this->cachedEventStreams[] = $recordedEvents;
                }
            }
        );

        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event) use ($eventStore): void {
                $stream = $event->getParam('stream');
                $recordedEvents = $stream->streamEvents();

                if (!$this->inTransaction($eventStore)) {
                    if ($event->getParam('streamExistsAlready', false)) {
                        return;
                    }

                    foreach ($recordedEvents as $recordedEvent) {
                        $this->eventBus->dispatch($recordedEvent);
                    }
                } else {
                    $this->cachedEventStreams[] = $recordedEvents;
                }
            }
        );

        if ($eventStore instanceof TransactionalActionEventEmitterEventStore) {
            $this->listenerHandlers[] = $eventStore->attach(
                TransactionalActionEventEmitterEventStore::EVENT_COMMIT,
                function (ActionEvent $event): void {
                    foreach ($this->cachedEventStreams as $stream) {
                        foreach ($stream as $recordedEvent) {
                            $this->eventBus->dispatch($recordedEvent);
                        }
                    }
                    $this->cachedEventStreams = [];
                }
            );

            $this->listenerHandlers[] = $eventStore->attach(
                TransactionalActionEventEmitterEventStore::EVENT_ROLLBACK,
                function (ActionEvent $event): void {
                    $this->cachedEventStreams = [];
                }
            );
        }
    }

    private function inTransaction(EventStore $eventStore): bool
    {
        return false;
        return $eventStore instanceof TransactionalActionEventEmitterEventStore
            && $eventStore->inTransaction();
    }
}
