<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationConfirmed extends Notification implements ShouldQueue
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
        $r = $this->reservation->loadMissing('room.amenities');
        $pickup = $r->room->pickupItems();

        $mail = (new MailMessage)
            ->subject('Reserva confirmada: '.$r->room->name)
            ->greeting('¡Hola, '.$notifiable->name.'!')
            ->line('Tu reserva quedó confirmada:')
            ->line('**Sala:** '.$r->room->name)
            ->line('**Ubicación:** '.($r->room->location ?? '—'))
            ->line('**Fecha:** '.$r->localStart()->translatedFormat('l j \\d\\e F \\d\\e Y'))
            ->line('**Horario:** '.$r->timeRangeLabel());

        if ($pickup->isNotEmpty()) {
            $mail->line('**Antes de entrar, retira:** '.$pickup->pluck('name')->implode(', ')
                .($r->room->pickup_point ? ' en '.$r->room->pickup_point : '')
                .'. Devuélvelos al terminar.');
        }

        return $mail
            ->line('Al llegar, escanea el QR de la puerta para registrar tu asistencia en los primeros '.config('booking.check_in.grace_minutes').' minutos.')
            ->action('Ver mi reserva', route('reservations.show', $r))
            ->line('Si ya no la necesitas, cancélala para liberar la sala a otra persona.');
    }
}
