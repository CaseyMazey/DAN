<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wochenberichte', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('azubi_id');
            $table->date('startdatum');
            $table->date('enddatum');
            $table->boolean('abgegeben')->default(false);
            $table->timestamp('abgegeben_am')->nullable();
            $table->boolean('auto_abgeschickt')->default(false);
            $table->boolean('azubi_signiert')->default(false);
            $table->timestamp('azubi_signiert_am')->nullable();
            $table->boolean('signiert')->default(false);
            $table->timestamp('signiert_am')->nullable();
            $table->boolean('bearbeitet')->default(false);
            $table->boolean('gesperrt')->default(false);
            $table->timestamps();
            $table->unique(['azubi_id', 'startdatum']);
            $table->foreign('azubi_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('wochenbericht_eintraege', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wochenbericht_id');
            $table->enum('kategorie', ['betrieb', 'unterweisung', 'berufsschule']);
            $table->text('beschreibung');
            $table->decimal('stunden', 4, 2)->default(0);
            $table->timestamps();
            $table->foreign('wochenbericht_id')->references('id')->on('wochenberichte')->onDelete('cascade');
        });

        Schema::create('wochenbericht_bearbeitungen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wochenbericht_id');
            $table->unsignedBigInteger('autor_id');
            $table->text('kommentar');
            $table->timestamps();
            $table->foreign('wochenbericht_id')->references('id')->on('wochenberichte')->onDelete('cascade');
            $table->foreign('autor_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('wochenbericht_kommentare', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wochenbericht_id');
            $table->unsignedBigInteger('autor_id');
            $table->text('inhalt');
            $table->timestamps();
            $table->foreign('wochenbericht_id')->references('id')->on('wochenberichte')->onDelete('cascade');
            $table->foreign('autor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wochenbericht_kommentare');
        Schema::dropIfExists('wochenbericht_bearbeitungen');
        Schema::dropIfExists('wochenbericht_eintraege');
        Schema::dropIfExists('wochenberichte');
    }
};