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
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("applications_title")->nullable();
            $table->text("description")->nullable();
            $table->string("url")->nullable();
            $table->string("image")->nullable();
            $table->string("pilar_id")->nullable();
            $table->timestamps();

            $table->foreign('pilar_id')->references('id')->on('pilars')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applications');
    }
};