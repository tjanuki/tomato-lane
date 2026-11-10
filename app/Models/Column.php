<?php

namespace App\Models;

use App\Enums\ColumnKind;
use Carbon\CarbonImmutable;
use Database\Factories\ColumnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property ColumnKind $kind
 * @property int $position
 * @property int|null $wip_limit
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'kind', 'position', 'wip_limit'])]
class Column extends Model
{
    /** @use HasFactory<ColumnFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => ColumnKind::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position')->orderBy('id');
    }

    public function isDone(): bool
    {
        return $this->kind === ColumnKind::Done;
    }
}
