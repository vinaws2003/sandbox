<?php

namespace App\Contracts;

use App\Models\Node;
use Illuminate\Support\Collection;

interface CollectorInterface
{
    /**
     * Get the node types this collector supports.
     *
     * @return array<string>
     */
    public static function supports(): array;

    /**
     * Test the connection to the node.
     *
     * @throws \App\Exceptions\ConnectionException
     */
    public function testConnection(Node $node): bool;

    /**
     * Collect metrics from the node.
     *
     * @return Collection<int, array{type: string, value: float, metadata?: array}>
     * @throws \App\Exceptions\ConnectionException
     */
    public function collect(Node $node): Collection;
}
