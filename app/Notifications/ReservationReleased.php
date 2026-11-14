<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationReleased extends Notification implements ShouldQueue
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
        $r = $this->reservation->loadMissing('room');
        $grace = config('booking.check_in.grace_minutes');

        return (new MailMessage)
            ->subject('Liberamos tu reserva: '.$r->room->name)
            ->greeting('Hola, '.$notifiable->name)
            ->line("No registraste asistencia en los primeros {$grace} minutos, así que liberamos la sala para otras personas:")
            ->line('**Sala:** '.$r->room->name)
            ->line('**Horario:** '.$r->localStart()->translatedFormat('l j \\d\\e F').', '.$r->timeRangeLabel())
            ->line('Para la próxima, escanea el código QR que está en la puerta de la sala al llegar.')
            ->action('Ver mis reservas', route('reservations.index'));
    }
}
