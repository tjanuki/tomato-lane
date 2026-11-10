<?php

namespace App\Http\Requests\Board;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MoveTaskRequest extends FormRequest
{
    /** Ownership is checked before validation, so a foreign task is a 403. */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && $this->user()?->can('update', $task) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'column_id' => [
                'required',
                'integer',
                Rule::exists('columns', 'id')->where('user_id', $this->user()?->id),
            ],
            'ordered_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ordered_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * The dropped order must include the moved card and only the user's cards.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Task $task */
                $task = $this->route('task');
                $ids = $this->orderedIds();

                if (! in_array($task->id, $ids, true)) {
                    $validator->errors()->add('ordered_ids', 'The moved task is missing from the new order.');

                    return;
                }

                $owned = Task::query()
                    ->where('user_id', $this->user()?->id)
                    ->whereIn('id', $ids)
                    ->count();

                if ($owned !== count($ids)) {
                    $validator->errors()->add('ordered_ids', 'The new order contains unknown tasks.');
                }
            },
        ];
    }

    /** @return list<int> */
    public function orderedIds(): array
    {
        return array_values(array_map('intval', (array) $this->input('ordered_ids', [])));
    }
}
