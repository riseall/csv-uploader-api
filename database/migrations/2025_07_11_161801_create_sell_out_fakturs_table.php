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
        Schema::create('selling_out_metd_faktur', function (Blueprint $table) {
            $table->id();
            $table->string('kode_cbg_ph')->nullable();
            $table->string('cbg_ph')->nullable();
            $table->date('tgl_faktur')->nullable();
            $table->string('id_outlet')->nullable();
            $table->string('no_faktur')->nullable();
            // $table->string('no_invoice')->nullable();
            // $table->string('status', 50)->nullable();
            $table->string('nama_outlet')->nullable();
            $table->string('alamat_1')->nullable();
            $table->string('alamat_2')->nullable();
            $table->string('alamat_3')->nullable();
            $table->string('kode_brg_metd')->nullable();
            $table->string('kode_brg_phapros')->nullable();
            $table->string('nama_brg_metd')->nullable();
            $table->string('satuan_metd')->nullable();
            $table->string('satuan_ph')->nullable();
            $table->string('qty')->nullable();
            $table->string('konversi_qty')->nullable();
            $table->string('hna')->nullable();
            // $table->string('diskon_dimuka_persen')->nullable();
            // $table->string('diskon_dimuka_amount')->nullable();
            $table->string('diskon_persen_1')->nullable();
            $table->string('diskon_ammount_1')->nullable();
            $table->string('diskon_persen_2')->nullable();
            $table->string('diskon_ammount_2')->nullable();
            $table->string('total_diskon_persen')->nullable();
            $table->string('total_diskon_ammount')->nullable();
            $table->string('netto')->nullable();
            $table->string('brutto')->nullable();
            $table->string('ppn')->nullable();
            $table->string('jumlah')->nullable();
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
        Schema::dropIfExists('selling_out_metd_faktur');
    }
}
