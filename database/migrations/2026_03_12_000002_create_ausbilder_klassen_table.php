<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ausbilder_klassen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('klasse_id')->constrained('klassen')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'klasse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ausbilder_klassen');
    }
};
