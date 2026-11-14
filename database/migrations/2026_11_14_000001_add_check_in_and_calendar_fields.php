<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// agrega campos para check in liberacion y sincronizacion de calendario
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // color para el calendario y token secreto para el qr
            $table->string('color', 20)->default('blue')->after('is_active');
            $table->string('check_in_token', 64)->nullable()->unique()->after('color');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('status');
            $table->timestamp('released_at')->nullable()->after('cancelled_by');
        });

        Schema::table('users', function (Blueprint $table) {
            // token privado para el feed de calendario del usuario
            $table->string('calendar_token', 64)->nullable()->unique()->after('role');
        });

        // genera tokens para registros existentes
        DB::table('rooms')->whereNull('check_in_token')->pluck('id')
            ->each(fn ($id) => DB::table('rooms')->where('id', $id)->update(['check_in_token' => Str::random(40)]));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('calendar_token'));
        Schema::table('reservations', fn (Blueprint $table) => $table->dropColumn(['checked_in_at', 'released_at']));
        Schema::table('rooms', fn (Blueprint $table) => $table->dropColumn(['color', 'check_in_token']));
    }
};
