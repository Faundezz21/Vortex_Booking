<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reservation $reservation)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->reservation->loadMissing(['room', 'cancelledBy']);
        $byAdmin = $r->cancelled_by !== null && $r->cancelled_by !== $r->user_id;

        return (new MailMessage)
            ->subject('Reserva cancelada: '.$r->room->name)
            ->greeting('Hola, '.$notifiable->name)
            ->line($byAdmin
                ? 'Un administrador canceló tu reserva:'
                : 'Confirmamos la cancelación de tu reserva:')
            ->line('**Sala:** '.$r->room->name)
            ->line('**Fecha:** '.$r->localStart()->translatedFormat('l j \\d\\e F \\d\\e Y'))
            ->line('**Horario:** '.$r->timeRangeLabel())
            ->action('Buscar otra sala', route('rooms.index'))
            ->line('El horario quedó liberado para otras personas.');
    }
}
