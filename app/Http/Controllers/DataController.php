<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterProduct;
use App\Models\MasterCustomer;
use App\Models\StockMetd;
use App\Models\SellOutFaktur;
use App\Models\SellOutNonfaktur;

class DataController extends Controller
{
    public function getMasterProducts()
    {
        return response()->json(MasterProduct::orderBy('created_at', 'desc')->get());
    }

    public function getMasterCustomers()
    {
        return response()->json(MasterCustomer::orderBy('created_at', 'desc')->get());
    }

    public function getStockMetd()
    {
        return response()->json(StockMetd::orderBy('created_at', 'desc')->get());
    }

    public function getSellOutFaktur()
    {
        return response()->json(SellOutFaktur::orderBy('created_at', 'desc')->get());
    }

    public function getSellOutNonfaktur()
    {
        return response()->json(SellOutNonfaktur::orderBy('created_at', 'desc')->get());
    }
}
