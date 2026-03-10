<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_eintraege', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('azubi_id');
            $table->string('fach');
            $table->enum('typ', ['test', 'klassenarbeit', 'hausaufgabe', 'projekt', 'sonstiges']);
            $table->date('datum');
            $table->string('thema')->nullable();
            $table->text('lernnotizen')->nullable();
            $table->decimal('note', 3, 1)->nullable(); // z.B. 1.5
            $table->timestamps();

            $table->foreign('azubi_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_eintraege');
    }
};