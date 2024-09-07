<?php

namespace App\Notifications;

use App\Utils\MoneyMask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnviarEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $task;

    public function __construct($task)
    {
       $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Despesa Criada')
            ->line('A despesa: ' .$this->task->description . ' foi criada com sucesso!')
            ->line('Segue abaixo as informações')
            ->line('Valor: ' . MoneyMask::moneyExtensive($this->task->value))
            ->line('Data da criação: ' . $this->task->data)
            ->line('Usuário: ' . $this->task->user->name)
            ->action('Clique aqui e deixe sua recomendação', url('https://www.linkedin.com/in/yuriameno/'))
            ->line('Obrigado por utilizar minha API!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
