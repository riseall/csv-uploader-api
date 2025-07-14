<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterProduct extends Model
{
    use HasFactory;

    protected $table = 'master_products';

    protected $fillable = [
        'kode_brg_metd',
        'kode_brg_ph',
        'nama_brg_metd',
        'nama_brg_ph',
        'satuan_metd',
        'satuan_ph',
        'konversi_qty'
    ];

    protected $guarded = [];
}
