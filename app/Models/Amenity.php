<?php

namespace App\Models;

use App\Enums\AmenityKind;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'kind', 'sort'];

    protected function casts(): array
    {
        return ['kind' => AmenityKind::class];
    }

    // catalogo base de equipamiento y elementos para entrega fisica
    public const CATALOG = [
        ['aire-acondicionado', 'Aire acondicionado', 'sparkles', AmenityKind::Equipment],
        ['calefaccion', 'Calefacción', 'fire', AmenityKind::Equipment],
        ['proyector', 'Proyector', 'video-camera', AmenityKind::Equipment],
        ['pantalla', 'Pantalla / TV', 'tv', AmenityKind::Equipment],
        ['pizarra', 'Pizarra', 'pencil', AmenityKind::Equipment],
        ['computadores', 'Computadores', 'computer-desktop', AmenityKind::Equipment],
        ['enchufes', 'Enchufes en la mesa', 'bolt', AmenityKind::Equipment],
        ['wifi', 'Wi-Fi', 'wifi', AmenityKind::Equipment],
        ['llave', 'Llave de la sala', 'key', AmenityKind::Pickup],
        ['control-aire', 'Control del aire acondicionado', 'device-phone-mobile', AmenityKind::Pickup],
        ['control-proyector', 'Control del proyector', 'device-phone-mobile', AmenityKind::Pickup],
        ['cable-hdmi', 'Cable HDMI', 'link', AmenityKind::Pickup],
        ['plumones', 'Plumones y borrador', 'paint-brush', AmenityKind::Pickup],
    ];

    /** @return BelongsToMany<Room, $this> */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }

    /** @param Builder<Amenity> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('kind')->orderBy('sort')->orderBy('name');
    }
}
