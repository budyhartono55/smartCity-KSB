<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pilars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("pilar_title")->nullable();
            $table->string("sub_dimensial")->nullable();
            $table->text("strategy")->nullable();
            $table->text("program")->nullable();
            $table->text("pengembangan_kebijakan_dan_kelembagaan")->nullable();
            $table->text("infrastruktur_pendukung")->nullable();
            $table->text("penguatan_literasi")->nullable();
            $table->string("image")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pilars');
    }
};