<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wochenbericht_eintraege', function (Blueprint $table) {
            $table->string('stunden_abziehen_bei')->nullable()->after('stunden');
        });
    }

    public function down(): void
    {
        Schema::table('wochenbericht_eintraege', function (Blueprint $table) {
            $table->dropColumn('stunden_abziehen_bei');
        });
    }
};
