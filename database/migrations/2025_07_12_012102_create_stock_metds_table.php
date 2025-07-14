<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockMetdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_metds', function (Blueprint $table) {
            $table->id();
            $table->string('kode_brg_metd')->nullable();
            $table->string('kode_brg_ph')->nullable();
            $table->string('nama_brg_metd')->nullable();
            $table->string('nama_brg_phapros')->nullable();
            $table->string('plant')->nullable();
            $table->string('nama_plant')->nullable();
            $table->string('suhu_gudang_penyimpanan')->nullable();
            $table->string('batch_phapros')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('satuan_metd', 10)->nullable();
            $table->string('satuan_phapros', 10)->nullable();
            $table->integer('harga_beli')->nullable();
            $table->integer('konversi_qty')->nullable();
            $table->integer('qty_onhand_metd')->nullable();
            $table->integer('qty_selleable')->nullable();
            $table->integer('qty_non_selleable')->nullable();
            $table->integer('qty_intransit_in')->nullable();
            $table->bigInteger('nilai_intransit_in')->nullable();
            $table->integer('qty_intransit_pass')->nullable();
            $table->bigInteger('nilai_intransit_pass')->nullable();
            $table->date('tgl_terima_brg')->nullable();
            $table->string('source_beli')->nullable();
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
        Schema::dropIfExists('stock_metds');
    }
}
