<?php

namespace App\Policies;

use App\Models\SubTask;
use App\Models\User;

class SubTaskPolicy
{
    public function update(User $user, SubTask $subTask): bool
    {
        return $subTask->task->user_id === $user->id;
    }
}
