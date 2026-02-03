<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            if (!Schema::hasColumn('bancos', 'nombre_corto')) {
                $table->string('nombre_corto')->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('bancos', 'color')) {
                $table->string('color', 7)->nullable()->after('sitio_web');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['nombre_corto', 'color']);
        });
    }
};
