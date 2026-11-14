<?php

namespace App\Support;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Carbon\CarbonInterface;

// genera calendarios ics rfc 5545 para suscripcion y descarga
class IcsCalendar
{
    /** @param iterable<Reservation> $reservations */
    public static function build(iterable $reservations, string $name): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Vortex Booking//Reservas de salas//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape($name),
            'X-WR-TIMEZONE:'.config('app.display_timezone'),
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
            'X-PUBLISHED-TTL:PT1H',
        ];

        foreach ($reservations as $reservation) {
            array_push($lines, ...self::event($reservation));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    /** @return list<string> */
    private static function event(Reservation $r): array
    {
        $room = $r->room;
        $description = trim(($r->notes ? $r->notes."\n\n" : '').'Ver o cancelar: '.route('reservations.show', $r));

        return [
            'BEGIN:VEVENT',
            'UID:reservation-'.$r->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.self::date($r->updated_at ?? now()),
            'DTSTART:'.self::date($r->starts_at),
            'DTEND:'.self::date($r->ends_at),
            'SUMMARY:'.self::escape($room->code().' · '.$room->name),
            'LOCATION:'.self::escape((string) $room->location),
            'DESCRIPTION:'.self::escape($description),
            'STATUS:'.($r->status === ReservationStatus::Confirmed ? 'CONFIRMED' : 'CANCELLED'),
            // recordatorio quince minutos antes para acudir al check in
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.self::escape('Reserva en '.$room->name),
            'TRIGGER:-PT15M',
            'END:VALARM',
            'END:VEVENT',
        ];
    }

    private static function date(CarbonInterface $date): string
    {
        return $date->copy()->utc()->format('Ymd\THis\Z');
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    // ajusta lineas a un maximo de 75 caracteres segun el estandar
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = '';
        $current = '';
        foreach (mb_str_split($line) as $char) {
            if (strlen($current.$char) > ($out === '' ? 75 : 74)) {
                $out .= ($out === '' ? '' : "\r\n ").$current;
                $current = '';
            }
            $current .= $char;
        }

        return $out."\r\n ".$current;
    }
}
