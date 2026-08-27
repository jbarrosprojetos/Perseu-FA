<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pessoas_juridicas', function (Blueprint $table) {
            $table->string('ncm')->nullable()->after('cnae');
        });
    }

    public function down(): void
    {
        Schema::table('pessoas_juridicas', function (Blueprint $table) {
            $table->dropColumn('ncm');
        });
    }
};
