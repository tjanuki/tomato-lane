<?php

namespace App\Models;

use App\Enums\TaskColor;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $column_id
 * @property string $name
 * @property string|null $description
 * @property TaskColor $color
 * @property int $position
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Column $column
 */
#[Fillable(['column_id', 'name', 'description', 'color', 'position', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'color' => TaskColor::class,
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Column, $this> */
    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    /** @return HasMany<SubTask, $this> */
    public function subTasks(): HasMany
    {
        return $this->hasMany(SubTask::class)->orderBy('id');
    }
}
