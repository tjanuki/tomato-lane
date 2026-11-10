<?php

namespace App\Board;

use App\Enums\ColumnKind;
use App\Models\User;

/**
 * The columns every new user starts with, matching the original board.
 */
class DefaultBoard
{
    /** @var list<array{name: string, kind: ColumnKind, wip_limit?: int}> */
    public const COLUMNS = [
        ['name' => 'Templates', 'kind' => ColumnKind::List],
        ['name' => 'Today', 'kind' => ColumnKind::List, 'wip_limit' => 10],
        ['name' => 'Done', 'kind' => ColumnKind::Done],
        ['name' => 'Ideas', 'kind' => ColumnKind::List],
        ['name' => 'Monday', 'kind' => ColumnKind::Day],
        ['name' => 'Tuesday', 'kind' => ColumnKind::Day],
        ['name' => 'Wednesday', 'kind' => ColumnKind::Day],
        ['name' => 'Thursday', 'kind' => ColumnKind::Day],
        ['name' => 'Friday', 'kind' => ColumnKind::Day],
        ['name' => 'Saturday', 'kind' => ColumnKind::Day],
    ];

    public function createFor(User $user): void
    {
        foreach (self::COLUMNS as $position => $column) {
            $user->columns()->create([
                'name' => $column['name'],
                'kind' => $column['kind'],
                'position' => $position,
                'wip_limit' => $column['wip_limit'] ?? null,
            ]);
        }
    }
}
