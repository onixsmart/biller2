<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEstadoSunatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estado_sunat', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id');
            $table->string('ruc', 11);
            $table->string('user_sol',20 );
            $table->string('pass_sol' );
            $table->string('certificado_file');
            $table->tinyInteger('state_sunat');
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
        Schema::dropIfExists('estado_sunat');
    }
}
