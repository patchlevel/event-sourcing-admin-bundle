<?php

namespace Patchlevel\EventSourcingAdminBundle\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Setup;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\EventBus\HeaderNotFound;
use Patchlevel\EventSourcing\EventBus\Message;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;

#[Projector('trace')]
class TraceProjector
{
    private const NODE_TABLE = 'trace_node';
    private const LINK_TABLE = 'trace_link';

    /**
     * @var array<string, Node>
     */
    private array $nodes = [];

    /**
     * @var array<string, Link>
     */
    private array $links = [];

    public function __construct(
        private readonly Connection    $connection,
        private readonly EventRegistry $eventRegistry,
    )
    {
    }

    #[Subscribe('*')]
    public function handleAll(Message $message): void
    {
        $this->init();

        $toId = $this->insertMessageAsNode($message);

        try {
            /**
             * @var list<array{name: string, category: string}> $traces
             */
            $traces = $message->header('trace');
        } catch (HeaderNotFound) {
            return;
        }

        foreach ($traces as $trace) {
            $fromId = $this->insertTrace($trace);
            $this->insertLink($fromId, $toId);
        }
    }

    private function insertMessageAsNode(Message $message): Node
    {
        $name = $this->eventRegistry->eventName($message->event()::class);
        $category = 'aggregate/' . $message->aggregateName();

        return $this->addNode($name, $category);
    }

    /**
     * @param array{name: string, category: string} $trace
     */
    private function insertTrace(array $trace): Node
    {
        return $this->addNode($trace['name'], $trace['category']);
    }

    private function insertLink(Node $from, Node $to): Link
    {
        $link = new Link($from->id, $to->id);

        if (array_key_exists($link->id, $this->links)) {
            return $this->links[$link->id];
        }

        $this->connection->insert(
            self::LINK_TABLE,
            [
                'from_id' => $link->fromId,
                'to_id' => $link->toId,
            ]
        );

        $this->links[$link->id] = $link;

        return $link;
    }

    private function addNode(string $name, string $category): Node
    {
        $node = new Node(
            $name,
            $category,
        );

        if (array_key_exists($node->id, $this->nodes)) {
            return $this->nodes[$node->id];
        }

        $this->connection->insert(
            self::NODE_TABLE,
            [
                'id' => $node->id,
                'name' => $node->name,
                'category' => $node->category,
            ]
        );

        $this->nodes[$node->id] = $node;

        return $node;
    }

    #[Setup]
    public function setup(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $table = new Table(self::NODE_TABLE);
        $table->addColumn('id', 'string', ['length' => 255]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('category', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);

        $schemaManager->createTable($table);

        $table = new Table(self::LINK_TABLE);
        $table->addColumn('from_id', 'string', ['length' => 255]);
        $table->addColumn('to_id', 'string', ['length' => 255]);

        $table->setPrimaryKey(['from_id', 'to_id']);
        $table->addForeignKeyConstraint(self::NODE_TABLE, ['from_id'], ['id']);
        $table->addForeignKeyConstraint(self::NODE_TABLE, ['to_id'], ['id']);

        $schemaManager->createTable($table);
    }

    #[Teardown]
    public function teardown(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $schemaManager->dropTable(self::LINK_TABLE);
        $schemaManager->dropTable(self::NODE_TABLE);
    }

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        $this->init();

        return array_values($this->nodes);
    }

    /**
     * @return list<Link>
     */
    public function links(): array
    {
        $this->init();

        return array_values($this->links);
    }

    private function init(): void
    {
        if ($this->nodes === []) {
            $result = $this->connection->fetchAllAssociative('SELECT id, name, category FROM ' . self::NODE_TABLE);

            foreach ($result as $row) {
                $node = new Node(
                    $row['name'],
                    $row['category'],
                );

                $this->nodes[$node->id] = $node;
            }
        }

        if ($this->links === []) {
            $result = $this->connection->fetchAllAssociative('SELECT from_id, to_id FROM ' . self::LINK_TABLE);

            foreach ($result as $row) {
                $link = new Link(
                    $row['from_id'],
                    $row['to_id'],
                );

                $this->links[$link->id] = $link;
            }
        }
    }
}
