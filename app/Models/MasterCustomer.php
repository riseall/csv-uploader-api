<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCustomer extends Model
{
    use HasFactory;

    protected $table = 'master_customers';

    protected $fillable = [
        'id_outlet',
        'nama_outlet',
        'cbg_ph',
        'kode_cbg_ph',
        'cbg_metd',
        'alamat_1',
        'alamat_2',
        'alamat_3',
        'no_telp',
    ];

    protected $guarded = [];
}
