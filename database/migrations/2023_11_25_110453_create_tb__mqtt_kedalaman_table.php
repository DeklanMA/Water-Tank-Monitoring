<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTbMqttKedalamanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tb_MqttKedalaman', function (Blueprint $table) {
            $table->id('id_kedalaman');
            $table->float('kedalaman'); // Sesuaikan tipe data dengan kebutuhan Anda
            $table->timestamps(); // Tambahkan kolom waktu pembuatan dan pembaruan
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tb_MqttKedalaman');
    }
}
