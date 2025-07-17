<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mst_cust_metd', function (Blueprint $table) {
            $table->id();
            $table->string('id_outlet')->unique();
            $table->string('nama_outlet')->nullable();
            $table->string('cbg_ph')->nullable();
            $table->string('kode_cbg_ph')->unique();
            $table->string('cbg_metd')->nullable();
            $table->string('alamat_1')->nullable();
            $table->string('alamat_2')->nullable();
            $table->string('alamat_3')->nullable();
            $table->string('no_telp', 20)->nullable();
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
        Schema::dropIfExists('mst_cust_metd');
    }
}
