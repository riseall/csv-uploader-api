<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMetd extends Model
{
    use HasFactory;

    protected $table = 'stock_ph_in_metd';

    protected $fillable = [
        'kode_brg_metd',
        'kode_brg_ph',
        'nama_brg_metd',
        'nama_brg_phapros',
        'plant',
        'nama_plant',
        'suhu_gudang_penyimpanan',
        'batch_phapros',
        'expired_date',
        'satuan_metd',
        'satuan_phapros',
        'harga_beli',
        'konversi_qty',
        'qty_onhand_metd',
        'qty_selleable',
        'qty_non_selleable',
        'qty_intransit_in',
        'nilai_intransit_in',
        'qty_intransit_pass',
        'nilai_intransit_pass',
        'tgl_terima_brg',
        'source_beli',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'tgl_terima_brg' => 'date'
    ];

    protected $guarded = [];
}
