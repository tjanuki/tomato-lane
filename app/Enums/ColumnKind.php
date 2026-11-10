<?php

namespace App\Enums;

enum ColumnKind: string
{
    /** An ordinary list such as Today or Ideas. */
    case List = 'list';

    /** A weekday planning column, rendered without a background. */
    case Day = 'day';

    /** The single completion column; cards are grouped by completion day. */
    case Done = 'done';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
