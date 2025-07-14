<?php

use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Rute otentikasi
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/receive-data', [UploadController::class, 'receiveData']);

    // Rute API baru untuk setiap jenis data
    Route::get('/master-products', [DataController::class, 'getMasterProducts']);
    Route::get('/master-customers', [DataController::class, 'getMasterCustomers']);
    Route::get('/stock-metd', [DataController::class, 'getStockMetd']);
    Route::get('/sellout-faktur', [DataController::class, 'getSellOutFaktur']);
    Route::get('/sellout-nonfaktur', [DataController::class, 'getSellOutNonfaktur']);
});
