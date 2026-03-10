<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faecher', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('azubi_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('azubi_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['azubi_id', 'name']); // kein doppeltes Fach pro Azubi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faecher');
    }
};