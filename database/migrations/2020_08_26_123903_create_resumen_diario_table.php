<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResumenDiarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resumen_diario', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->dateTime('fecha_emision');
            $table->dateTime('fecha_resumen');
            $table->tinyInteger('estado_sunat');
            $table->integer('business_id');
            //$table->foreign('business_id')->references('id')->on('business');
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
        Schema::dropIfExists('resumen_diario');
    }
}
