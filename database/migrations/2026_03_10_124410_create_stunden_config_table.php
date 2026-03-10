<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stunden_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('azubi_id')->unique();
            $table->decimal('max_stunden', 4, 2)->default(42);
            $table->decimal('betrieb_stunden', 4, 2)->default(32);
            $table->decimal('berufsschule_stunden', 4, 2)->default(10);
            $table->timestamps();

            $table->foreign('azubi_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stunden_config');
    }
};