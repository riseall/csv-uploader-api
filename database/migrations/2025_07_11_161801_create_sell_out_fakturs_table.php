<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellOutFaktursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sell_out_fakturs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_cbg_ph')->nullable();
            $table->string('cbg_ph')->nullable();
            $table->date('tgl_faktur')->nullable();
            $table->string('id_outlet')->nullable();
            $table->string('no_faktur')->nullable();
            $table->string('no_invoice')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('nama_outlet')->nullable();
            $table->string('alamat_1')->nullable();
            $table->string('alamat_2')->nullable();
            $table->string('alamat_3')->nullable();
            $table->string('kode_brg_metd')->nullable();
            $table->string('kode_brg_phapros')->nullable();
            $table->string('nama_brg_metd')->nullable();
            $table->string('satuan_metd', 10)->nullable();
            $table->string('satuan_ph', 10)->nullable();
            $table->integer('qty')->nullable();
            $table->integer('konversi_qty')->nullable();
            $table->decimal('hna', 15, 0)->nullable();
            $table->decimal('diskon_dimuka_persen', 5, 0)->nullable();
            $table->decimal('diskon_dimuka_amount', 15, 0)->nullable();
            $table->decimal('diskon_persen_1', 5, 0)->nullable();
            $table->decimal('diskon_ammount_1', 15, 0)->nullable();
            $table->decimal('diskon_persen_2', 5, 0)->nullable();
            $table->decimal('diskon_ammount_2', 15, 0)->nullable();
            $table->decimal('total_diskon_persen', 5, 0)->nullable();
            $table->decimal('total_diskon_ammount', 15, 0)->nullable();
            $table->decimal('netto', 15, 0)->nullable();
            $table->decimal('brutto', 15, 0)->nullable();
            $table->decimal('ppn', 15, 0)->nullable();
            $table->decimal('jumlah', 15, 0)->nullable();
            $table->string('segmen')->nullable();
            $table->string('so_number')->nullable();
            $table->string('no_shipper')->nullable();
            $table->string('no_po')->nullable();
            $table->string('batch_ph')->nullable();
            $table->date('exp_date')->nullable();
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
        Schema::dropIfExists('sell_out_fakturs');
    }
}
