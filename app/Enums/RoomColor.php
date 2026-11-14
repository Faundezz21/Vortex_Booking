<?php

namespace App\Enums;

// colores de sala para las vistas del calendario
enum RoomColor: string
{
    case Blue = 'blue';
    case Teal = 'teal';
    case Amber = 'amber';
    case Rose = 'rose';
    case Violet = 'violet';
    case Lime = 'lime';
    case Orange = 'orange';
    case Slate = 'slate';

    public function label(): string
    {
        return match ($this) {
            self::Blue => 'Azul',
            self::Teal => 'Verde azulado',
            self::Amber => 'Ámbar',
            self::Rose => 'Rosa',
            self::Violet => 'Violeta',
            self::Lime => 'Lima',
            self::Orange => 'Naranjo',
            self::Slate => 'Gris',
        };
    }

    // color solido para indicadores y bordes
    public function dot(): string
    {
        return match ($this) {
            self::Blue => 'bg-blue-500',
            self::Teal => 'bg-teal-500',
            self::Amber => 'bg-amber-500',
            self::Rose => 'bg-rose-500',
            self::Violet => 'bg-violet-500',
            self::Lime => 'bg-lime-500',
            self::Orange => 'bg-orange-500',
            self::Slate => 'bg-slate-500',
        };
    }

    // estilo del bloque de evento en el calendario
    public function event(): string
    {
        return match ($this) {
            self::Blue => 'bg-blue-50 border-blue-500 text-blue-900 dark:bg-blue-500/15 dark:text-blue-100',
            self::Teal => 'bg-teal-50 border-teal-500 text-teal-900 dark:bg-teal-500/15 dark:text-teal-100',
            self::Amber => 'bg-amber-50 border-amber-500 text-amber-900 dark:bg-amber-500/15 dark:text-amber-100',
            self::Rose => 'bg-rose-50 border-rose-500 text-rose-900 dark:bg-rose-500/15 dark:text-rose-100',
            self::Violet => 'bg-violet-50 border-violet-500 text-violet-900 dark:bg-violet-500/15 dark:text-violet-100',
            self::Lime => 'bg-lime-50 border-lime-600 text-lime-900 dark:bg-lime-500/15 dark:text-lime-100',
            self::Orange => 'bg-orange-50 border-orange-500 text-orange-900 dark:bg-orange-500/15 dark:text-orange-100',
            self::Slate => 'bg-slate-100 border-slate-500 text-slate-900 dark:bg-slate-500/20 dark:text-slate-100',
        };
    }
}
