<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellOutNonfaktur extends Model
{
    use HasFactory;

    protected $table = 'selling_out_metd_non_faktur';

    protected $fillable = [
        'kode_cbg_ph',
        'cbg_ph',
        'tgl_transaksi',
        'id_outlet',
        'no_invoice',
        'status',
        'nama_outlet',
        'alamat_1',
        'alamat_2',
        'alamat_3',
        'kode_brg_metd',
        'kode_brg_phapros',
        'nama_brg_metd',
        'satuan_metd',
        'satuan_ph',
        'qty',
        'konversi_qty',
        'hna',
        'diskon_dimuka_persen',
        'diskon_dimuka_amount',
        'diskon_persen_1',
        'diskon_ammount_1',
        'diskon_persen_2',
        'diskon_ammount_2',
        'total_diskon_persen',
        'total_diskon_ammount',
        'netto',
        'brutto',
        'ppn',
        'jumlah',
        'segmen',
        'so_number',
        'no_shipper',
        'no_po',
        'batch_ph',
        'exp_date',
    ];

    protected $casts = [
        'tgl_transaksi' => 'date',
        'exp_date' => 'date',
    ];

    protected $guarded = [];
}
