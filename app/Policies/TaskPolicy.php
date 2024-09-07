<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{

    /**
     * Determine whether the user can view the model.
     */
    public function viewTask(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function updateTask(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    public function sendEmail(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deleteTask(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
