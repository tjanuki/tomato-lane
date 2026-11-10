<?php

namespace App\Enums;

/**
 * The fixed card palette. The value doubles as the key that links a task to
 * the project a user has named after that colour.
 */
enum TaskColor: string
{
    case White = 'white';
    case Yellow = 'yellow';
    case Green = 'green';
    case Blue = 'blue';
    case Purple = 'purple';
    case Red = 'red';
    case Orange = 'orange';
    case Magenta = 'magenta';
    case Cyan = 'cyan';
    case Brown = 'brown';

    /**
     * Card background, accent dot and text colours.
     *
     * @return array{bg: string, dot: string, text: string}
     */
    public function tint(): array
    {
        return match ($this) {
            self::White => ['bg' => '#e5e7eb', 'dot' => '#9ca3af', 'text' => '#1f2937'],
            self::Yellow => ['bg' => '#fdeaa8', 'dot' => '#f59e0b', 'text' => '#1f2937'],
            self::Green => ['bg' => '#bbe8c8', 'dot' => '#22c55e', 'text' => '#1f2937'],
            self::Blue => ['bg' => '#bfe3fb', 'dot' => '#3b9fd6', 'text' => '#1f2937'],
            self::Purple => ['bg' => '#ddd6fe', 'dot' => '#8b5cf6', 'text' => '#1f2937'],
            self::Red => ['bg' => '#fbcaca', 'dot' => '#ef4444', 'text' => '#1f2937'],
            self::Orange => ['bg' => '#fdd9b5', 'dot' => '#f97316', 'text' => '#1f2937'],
            self::Magenta => ['bg' => '#f9cfe4', 'dot' => '#ec4899', 'text' => '#1f2937'],
            self::Cyan => ['bg' => '#aeebf2', 'dot' => '#06b6d4', 'text' => '#1f2937'],
            self::Brown => ['bg' => '#e0d4cd', 'dot' => '#8d6e63', 'text' => '#1f2937'],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
