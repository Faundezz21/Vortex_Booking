<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// crea tablas para equipamiento y punto de entrega fisica de salas
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('icon', 40)->default('check');
            // equipment para instalados en la sala y pickup para elementos a retirar
            $table->string('kind', 20)->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('amenity_room', function (Blueprint $table) {
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->primary(['amenity_id', 'room_id']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('pickup_point', 150)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', fn (Blueprint $table) => $table->dropColumn('pickup_point'));
        Schema::dropIfExists('amenity_room');
        Schema::dropIfExists('amenities');
    }
};
