<?php

use App\Enums\ReservationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            // fechas almacenadas en formato utc
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 20)->default(ReservationStatus::Confirmed->value);
            $table->string('notes', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'starts_at']);
            $table->index(['user_id', 'starts_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            // restriccion exclude en postgresql para evitar solapamientos
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
            DB::statement('ALTER TABLE reservations ADD CONSTRAINT reservations_valid_range CHECK (ends_at > starts_at)');
            DB::statement(sprintf(
                "ALTER TABLE reservations ADD CONSTRAINT reservations_no_overlap
                 EXCLUDE USING gist (room_id WITH =, tsrange(starts_at, ends_at, '[)') WITH &&)
                 WHERE (status = '%s')",
                ReservationStatus::Confirmed->value,
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
