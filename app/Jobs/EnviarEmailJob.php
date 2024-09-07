<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Notifications\EnviarEmail;
use App\Models\User;

class EnviarEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $task;

    public function __construct(User $user, $task)
    {
        $this->user = $user;
        $this->task = $task;
    }

    public function handle()
    {
        $this->user->notify(new EnviarEmail($this->task));
    }
}