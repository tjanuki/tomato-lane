<?php

namespace App\Http\Requests\Board;

use App\Enums\TaskColor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', Rule::enum(TaskColor::class)],
        ];
    }
}
