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
        Schema::create('agendas', function (Blueprint $table) {
            // Mengubah format tanggal menjadi "Y-m-d"
            //   $tanggalPost = Carbon::createFromFormat('d-m-Y', $request->tanggal_post)->format('Y-m-d');

            $table->uuid('id')->primary();
            $table->string("agenda_title");
            $table->text("description")->nullable();
            $table->string("slug");
            $table->date("hold_on");
            $table->date("posted_at");
            $table->string("image")->nullable();
            $table->string("user_id");
            $table->string("category_id");
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agendas');
    }
};