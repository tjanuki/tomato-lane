<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SubTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 * @property string $name
 * @property bool $finished
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Task $task
 */
#[Fillable(['name', 'finished'])]
class SubTask extends Model
{
    /** @use HasFactory<SubTaskFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'finished' => 'boolean',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
