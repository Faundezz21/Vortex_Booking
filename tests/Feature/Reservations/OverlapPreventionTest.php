<?php

namespace Tests\Feature\Reservations;

use App\Actions\Reservations\CreateReservation;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationConflictException;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// prueba prevencion de superposicion de reservas en todas las capas
class OverlapPreventionTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        // fecha de referencia en zona horaria local
        $this->travelTo(Carbon::parse('2026-11-16 07:00', 'America/Santiago'));

        $this->room = Room::factory()->create(['opens_at' => '08:00', 'closes_at' => '21:00', 'max_duration_minutes' => 240]);

        Reservation::factory()
            ->for($this->room)
            ->between('2026-11-16 10:00', '2026-11-16 12:00')
            ->create();
    }

    // casos de prueba con horarios que se superponen
    public static function overlappingSlots(): array
    {
        return [
            'mismo horario exacto' => ['10:00', 120],
            'empieza antes y termina dentro' => ['09:00', 90],
            'empieza dentro y termina después' => ['11:30', 60],
            'contenida dentro de la existente' => ['10:30', 60],
            'contiene a la existente' => ['09:30', 180],
            'mismo inicio, más corta' => ['10:00', 30],
            'mismo fin, más corta' => ['11:30', 30],
        ];
    }

    #[Test]
    #[DataProvider('overlappingSlots')]
    public function rechaza_reservas_que_se_superponen(string $start, int $minutes): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('rooms.show', $this->room))
            ->post(route('reservations.store'), [
                'room_id' => $this->room->id,
                'date' => '2026-11-16',
                'start_time' => $start,
                'duration_minutes' => $minutes,
            ]);

        $response->assertRedirect(route('rooms.show', $this->room));
        $response->assertSessionHasErrors(['start_time' => 'La sala ya está reservada en ese horario. Elige otro bloque.']);
        $this->assertSame(1, Reservation::count());
    }

    // casos de prueba con horarios contiguos
    public static function adjacentSlots(): array
    {
        return [
            'termina justo cuando empieza la existente' => ['09:00', 60],
            'empieza justo cuando termina la existente' => ['12:00', 60],
        ];
    }

    #[Test]
    #[DataProvider('adjacentSlots')]
    public function permite_reservas_contiguas(string $start, int $minutes): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), [
                'room_id' => $this->room->id,
                'date' => '2026-11-16',
                'start_time' => $start,
                'duration_minutes' => $minutes,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(2, Reservation::confirmed()->count());
    }

    #[Test]
    public function permite_el_mismo_horario_en_otra_sala(): void
    {
        $otherRoom = Room::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), [
                'room_id' => $otherRoom->id,
                'date' => '2026-11-16',
                'start_time' => '10:00',
                'duration_minutes' => 120,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Reservation::count());
    }

    #[Test]
    public function una_reserva_cancelada_libera_el_horario(): void
    {
        Reservation::query()->update(['status' => ReservationStatus::Cancelled]);

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), [
                'room_id' => $this->room->id,
                'date' => '2026-11-16',
                'start_time' => '10:00',
                'duration_minutes' => 120,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Reservation::confirmed()->count());
    }

    #[Test]
    public function la_accion_rechaza_la_superposicion_aunque_se_salte_la_validacion_del_formulario(): void
    {
        // simula concurrencia donde otra peticion entra en la transaccion primero
        $this->expectException(ReservationConflictException::class);

        app(CreateReservation::class)->handle(
            User::factory()->create(),
            $this->room,
            Carbon::parse('2026-11-16 11:00', 'America/Santiago'),
            Carbon::parse('2026-11-16 12:30', 'America/Santiago'),
        );
    }

    #[Test]
    public function un_conflicto_detectado_en_la_transaccion_vuelve_al_formulario_con_el_error(): void
    {
        $this->mock(CreateReservation::class)
            ->shouldReceive('handle')
            ->andThrow(new ReservationConflictException);

        $this->actingAs(User::factory()->create())
            ->from(route('rooms.show', $this->room))
            ->post(route('reservations.store'), [
                'room_id' => $this->room->id,
                'date' => '2026-11-16',
                'start_time' => '14:00',
                'duration_minutes' => 60,
            ])
            ->assertRedirect(route('rooms.show', $this->room))
            ->assertSessionHasErrors('start_time');
    }

    #[Test]
    public function postgresql_rechaza_la_superposicion_a_nivel_de_base_de_datos(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La restricción EXCLUDE solo existe en PostgreSQL.');
        }

        try {
            // insercion directa a base de datos sin pasar por la aplicacion
            DB::table('reservations')->insert([
                'user_id' => User::factory()->create()->id,
                'room_id' => $this->room->id,
                'starts_at' => Carbon::parse('2026-11-16 11:00', 'America/Santiago')->utc(),
                'ends_at' => Carbon::parse('2026-11-16 13:00', 'America/Santiago')->utc(),
                'status' => ReservationStatus::Confirmed->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('PostgreSQL debió rechazar la reserva superpuesta.');
        } catch (QueryException $e) {
            $this->assertSame('23P01', $e->getCode(), 'Se esperaba una violación de la restricción de exclusión.');
            $this->assertStringContainsString('reservations_no_overlap', $e->getMessage());
        }
    }
}
