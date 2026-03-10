<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rolle', ['admin', 'ausbilder', 'azubi'])->default('azubi')->after('email');
            $table->date('ausbildungsbeginn')->nullable()->after('rolle');
            $table->date('ausbildungsende')->nullable()->after('ausbildungsbeginn');
            $table->unsignedBigInteger('klasse_id')->nullable()->after('ausbildungsende');
            $table->string('abteilung')->nullable()->after('klasse_id');
            $table->boolean('aktiv')->default(true)->after('abteilung');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'rolle', 'ausbildungsbeginn', 'ausbildungsende',
                'klasse_id', 'abteilung', 'aktiv'
            ]);
        });
    }
};