<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aufgaben', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('azubi_id');
            $table->unsignedBigInteger('ausbilder_id')->nullable();
            $table->string('titel');
            $table->text('beschreibung')->nullable();
            $table->enum('kategorie', ['betrieb', 'berufsschule', 'unterweisung']);
            $table->string('stunden_abziehen_bei')->nullable(); // betrieb oder berufsschule (nur bei unterweisung)
            $table->decimal('stunden', 4, 2)->default(0);
            $table->enum('status', ['geplant', 'in_bearbeitung', 'fertig'])->default('geplant');
            $table->boolean('archiviert')->default(false);
            $table->timestamp('archiviert_am')->nullable();
            $table->boolean('in_wochenbericht')->default(false); // wurde bereits übernommen
            $table->timestamps();

            $table->foreign('azubi_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ausbilder_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aufgaben');
    }
};