<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abteilungen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kuerzel', 20)->nullable(); // z.B. "IT-AE"
            $table->text('beschreibung')->nullable();
            $table->boolean('aktiv')->default(true);
            $table->timestamps();
        });

        // Fremdschlüssel in klassen-Tabelle ergänzen
        Schema::table('klassen', function (Blueprint $table) {
            $table->foreignId('abteilung_id')
                  ->nullable()
                  ->constrained('abteilungen')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('klassen', function (Blueprint $table) {
            $table->dropForeign(['abteilung_id']);
            $table->dropColumn('abteilung_id');
        });

        Schema::dropIfExists('abteilungen');
    }
};
