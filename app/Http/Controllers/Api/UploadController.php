<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\MasterCustomer;
use App\Models\StockMetd;
use App\Models\SellOutFaktur;
use App\Models\SellOutNonfaktur;
use App\Models\UploadedFileRecord;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException; // Import Carbon Exception
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    private static $expectedHeaders = [
        'Master Product' => [
            'kode_brg_metd',
            'kode_brg_ph',
            'nama_brg_metd',
            'nama_brg_ph',
            'satuan_metd',
            'satuan_ph',
            'konversi_qty'
        ],
        'Master Customer' => [
            'id_outlet',
            'nama_outlet',
            'cbg_ph',
            'kode_cbg_ph',
            'cbg_metd',
            'alamat_1',
            'alamat_2',
            'alamat_3',
            'no_telp'
        ],
        'Stock METD' => [
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
            'source_beli'
        ],
        'Sellout Faktur' => [
            'kode_cbg_ph',
            'cbg_ph',
            'tgl_faktur',
            'id_outlet',
            'no_faktur',
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
            'exp_date'
        ],
        'Sellout Nonfaktur' => [
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
            'exp_date'
        ],
    ];

    /**
     * Menerima file CSV dari frontend, menyimpannya ke storage, dan memproses data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function receiveData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_type' => 'required|in:Master Product,Master Customer,Stock METD,Sellout Faktur,Sellout Nonfaktur',
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $dataType = $request->input('data_type');
        $uploadedFile = $request->file('csv_file');
        $user = $request->user();
        $uploadDate = Carbon::now()->toDateString();

        if (!$user) {
            Log::warning('Unauthenticated request reached DataReceiverController.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        DB::beginTransaction();
        try {
            $folderPath = 'uploads/' . $dataType . '/' . $uploadDate;
            $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
            $filePath = $uploadedFile->storeAs($folderPath, $fileName, 'public');

            if (!$filePath) {
                throw new \Exception('Gagal menyimpan file ke storage.');
            }

            UploadedFileRecord::create([
                'data_type' => $dataType,
                'original_file_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'file_path' => $filePath,
                'upload_date' => $uploadDate,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $fileContent = Storage::disk('public')->get($filePath);
            $parsedPayload = [];
            $rows = explode("\n", $fileContent);

            $header = [];
            if (!empty($rows)) {
                $header = str_getcsv(array_shift($rows), ';');
                $header = array_map('trim', $header);
            }

            if (empty($header)) {
                throw new \Exception('Header CSV tidak dapat dibaca atau kosong dari file yang disimpan.');
            }

            if (!isset(self::$expectedHeaders[$dataType])) {
                throw new \Exception('Tipe data tidak dikenal untuk validasi header: ' . $dataType);
            }

            $expected = self::$expectedHeaders[$dataType];
            $sortedHeader = $header;
            $sortedExpected = $expected;
            sort($sortedHeader);
            sort($sortedExpected);

            if ($sortedHeader !== $sortedExpected) {
                $missingHeaders = array_diff($expected, $header);
                $unexpectedHeaders = array_diff($header, $expected);

                $errorMessage = "File CSV tidak sesuai dengan tipe data yang dipilih ({$dataType}).";
                if (!empty($missingHeaders)) {
                    $errorMessage .= " Header yang hilang: [" . implode(', ', $missingHeaders) . "].";
                }
                if (!empty($unexpectedHeaders)) {
                    $errorMessage .= " Header yang tidak diharapkan: [" . implode(', ', $unexpectedHeaders) . "].";
                }
                $errorMessage .= " Header yang diharapkan: [" . implode(', ', self::$expectedHeaders[$dataType]) . "]. Header yang ditemukan: [" . implode(', ', $header) . "].";

                throw new \Exception($errorMessage);
            }

            // Memproses setiap baris data
            foreach ($rows as $rowString) {
                $rowString = trim($rowString);
                if (empty($rowString)) {
                    continue;
                }

                $row = str_getcsv($rowString, ';');

                if (count($row) !== count($header)) {
                    Log::warning("Baris dilewati karena jumlah kolom tidak cocok setelah parsing dari file yang disimpan: " . $rowString);
                    continue;
                }
                $parsedPayload[] = array_combine($header, $row);
            }

            if (empty($parsedPayload)) {
                throw new \Exception('File CSV kosong atau format tidak valid setelah parsing dari file yang disimpan.');
            }

            $this->clearExistingData($dataType, $uploadDate);

            foreach ($parsedPayload as $data) {
                switch ($dataType) {
                    case 'Master Product':
                        MasterProduct::create([
                            'kode_brg_metd' => $data['kode_brg_metd'] ?? null,
                            'kode_brg_ph' => $data['kode_brg_ph'] ?? null,
                            'nama_brg_metd' => $data['nama_brg_metd'] ?? null,
                            'nama_brg_ph' => $data['nama_brg_ph'] ?? null,
                            'satuan_metd' => $data['satuan_metd'] ?? null,
                            'satuan_ph' => $data['satuan_ph'] ?? null,
                            'konversi_qty' => $data['konversi_qty'] ?? null,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        break;
                    case 'Master Customer':
                        MasterCustomer::create([
                            'id_outlet' => $data['id_outlet'] ?? null,
                            'nama_outlet' => $data['nama_outlet'] ?? null,
                            'cbg_ph' => $data['cbg_ph'] ?? null,
                            'kode_cbg_ph' => $data['kode_cbg_ph'] ?? null,
                            'cbg_metd' => $data['cbg_metd'] ?? null,
                            'alamat_1' => $data['alamat_1'] ?? null,
                            'alamat_2' => $data['alamat_2'] ?? null,
                            'alamat_3' => $data['alamat_3'] ?? null,
                            'no_telp' => $data['no_telp'] ?? null,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        break;
                    case 'Stock METD':
                        StockMetd::create([
                            'kode_brg_metd' => $data['kode_brg_metd'] ?? null,
                            'kode_brg_ph' => $data['kode_brg_ph'] ?? null,
                            'nama_brg_metd' => $data['nama_brg_metd'] ?? null,
                            'nama_brg_phapros' => $data['nama_brg_phapros'] ?? null,
                            'plant' => $data['plant'] ?? null,
                            'nama_plant' => $data['nama_plant'] ?? null,
                            'suhu_gudang_penyimpanan' => $data['suhu_gudang_penyimpanan'] ?? null,
                            'batch_phapros' => $data['batch_phapros'] ?? null,
                            'expired_date' => isset($data['expired_date']) && $data['expired_date'] ? Carbon::createFromFormat('Y-m-d', $data['expired_date'])->toDateString() : null,
                            'satuan_metd' => $data['satuan_metd'] ?? null,
                            'satuan_phapros' => $data['satuan_phapros'] ?? null,
                            'harga_beli' => $data['harga_beli'] ?? null,
                            'konversi_qty' => $data['konversi_qty'] ?? null,
                            'qty_onhand_metd' => $data['qty_onhand_metd'] ?? null,
                            'qty_selleable' => $data['qty_selleable'] ?? null,
                            'qty_non_selleable' => $data['qty_non_selleable'] ?? null,
                            'qty_intransit_in' => $data['qty_intransit_in'] ?? null,
                            'nilai_intransit_in' => $data['nilai_intransit_in'] ?? null,
                            'qty_intransit_pass' => $data['qty_intransit_pass'] ?? null,
                            'nilai_intransit_pass' => $data['nilai_intransit_pass'] ?? null,
                            'tgl_terima_brg' => isset($data['tgl_terima_brg']) && $data['tgl_terima_brg'] ? Carbon::createFromFormat('Y-m-d', $data['tgl_terima_brg'])->toDateString() : null,
                            'source_beli' => $data['source_beli'] ?? null,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        break;
                    case 'Sellout Faktur':
                        SellOutFaktur::create([
                            'kode_cbg_ph' => $data['kode_cbg_ph'] ?? null,
                            'cbg_ph' => $data['cbg_ph'] ?? null,
                            'tgl_faktur' => isset($data['tgl_faktur']) && $data['tgl_faktur'] ? Carbon::createFromFormat('Y-m-d', $data['tgl_faktur'])->toDateString() : null,
                            'id_outlet' => $data['id_outlet'] ?? null,
                            'no_faktur' => $data['no_faktur'] ?? null,
                            'no_invoice' => $data['no_invoice'] ?? null,
                            'status' => $data['status'] ?? null,
                            'nama_outlet' => $data['nama_outlet'] ?? null,
                            'alamat_1' => $data['alamat_1'] ?? null,
                            'alamat_2' => $data['alamat_2'] ?? null,
                            'alamat_3' => $data['alamat_3'] ?? null,
                            'kode_brg_metd' => $data['kode_brg_metd'] ?? null,
                            'kode_brg_phapros' => $data['kode_brg_phapros'] ?? null,
                            'nama_brg_metd' => $data['nama_brg_metd'] ?? null,
                            'satuan_metd' => $data['satuan_metd'] ?? null,
                            'satuan_ph' => $data['satuan_ph'] ?? null,
                            'qty' => $data['qty'] ?? null,
                            'konversi_qty' => $data['konversi_qty'] ?? null,
                            'hna' => $data['hna'] ?? null,
                            'diskon_dimuka_persen' => $data['diskon_dimuka_persen'] ?? null,
                            'diskon_dimuka_amount' => $data['diskon_dimuka_amount'] ?? null,
                            'diskon_persen_1' => $data['diskon_persen_1'] ?? null,
                            'diskon_ammount_1' => $data['diskon_ammount_1'] ?? null,
                            'diskon_persen_2' => $data['diskon_persen_2'] ?? null,
                            'diskon_ammount_2' => $data['diskon_ammount_2'] ?? null,
                            'total_diskon_persen' => $data['total_diskon_persen'] ?? null,
                            'total_diskon_ammount' => $data['total_diskon_ammount'] ?? null,
                            'netto' => $data['netto'] ?? null,
                            'brutto' => $data['brutto'] ?? null,
                            'ppn' => $data['ppn'] ?? null,
                            'jumlah' => $data['jumlah'] ?? null,
                            'segmen' => $data['segmen'] ?? null,
                            'so_number' => $data['so_number'] ?? null,
                            'no_shipper' => $data['no_shipper'] ?? null,
                            'no_po' => $data['no_po'] ?? null,
                            'batch_ph' => $data['batch_ph'] ?? null,
                            'exp_date' => isset($data['exp_date']) && $data['exp_date'] ? Carbon::createFromFormat('Y-m-d', $data['exp_date'])->toDateString() : null,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        break;
                    case 'Sellout Nonfaktur':
                        SellOutNonfaktur::create([
                            'kode_cbg_ph' => $data['kode_cbg_ph'] ?? null,
                            'cbg_ph' => $data['cbg_ph'] ?? null,
                            'tgl_transaksi' => isset($data['tgl_transaksi']) && $data['tgl_transaksi'] ? Carbon::createFromFormat('Y-m-d', $data['tgl_transaksi'])->toDateString() : null,
                            'id_outlet' => $data['id_outlet'] ?? null,
                            'no_invoice' => $data['no_invoice'] ?? null,
                            'status' => $data['status'] ?? null,
                            'nama_outlet' => $data['nama_outlet'] ?? null,
                            'alamat_1' => $data['alamat_1'] ?? null,
                            'alamat_2' => $data['alamat_2'] ?? null,
                            'alamat_3' => $data['alamat_3'] ?? null,
                            'kode_brg_metd' => $data['kode_brg_metd'] ?? null,
                            'kode_brg_phapros' => $data['kode_brg_phapros'] ?? null,
                            'nama_brg_metd' => $data['nama_brg_metd'] ?? null,
                            'satuan_metd' => $data['satuan_metd'] ?? null,
                            'satuan_ph' => $data['satuan_ph'] ?? null,
                            'qty' => $data['qty'] ?? null,
                            'konversi_qty' => $data['konversi_qty'] ?? null,
                            'hna' => $data['hna'] ?? null,
                            'diskon_dimuka_persen' => $data['diskon_dimuka_persen'] ?? null,
                            'diskon_dimuka_amount' => $data['diskon_dimuka_amount'] ?? null,
                            'diskon_persen_1' => $data['diskon_persen_1'] ?? null,
                            'diskon_ammount_1' => $data['diskon_ammount_1'] ?? null,
                            'diskon_persen_2' => $data['diskon_persen_2'] ?? null,
                            'diskon_ammount_2' => $data['diskon_ammount_2'] ?? null,
                            'total_diskon_persen' => $data['total_diskon_persen'] ?? null,
                            'total_diskon_ammount' => $data['total_diskon_ammount'] ?? null,
                            'netto' => $data['netto'] ?? null,
                            'brutto' => $data['brutto'] ?? null,
                            'ppn' => $data['ppn'] ?? null,
                            'jumlah' => $data['jumlah'] ?? null,
                            'segmen' => $data['segmen'] ?? null,
                            'so_number' => $data['so_number'] ?? null,
                            'no_shipper' => $data['no_shipper'] ?? null,
                            'no_po' => $data['no_po'] ?? null,
                            'batch_ph' => $data['batch_ph'] ?? null,
                            'exp_date' => isset($data['exp_date']) && $data['exp_date'] ? Carbon::createFromFormat('Y-m-d', $data['exp_date'])->toDateString() : null,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        break;
                }
            }

            DB::commit();

            return response()->json(['message' => 'Data ' . $dataType . ' berhasil disimpan ke database.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan data dari API: ' . $e->getMessage() . ' at line ' . $e->getLine());

            // Deteksi pesan error duplikat entry SQL
            $duplicateEntryRegex = '/Duplicate entry \'([^\']+)\' for key \'([^\']+)\'/';
            if (preg_match($duplicateEntryRegex, $e->getMessage(), $matches)) {
                $duplicateValue = $matches[1];
                $keyName = $matches[2];
                $fieldName = 'data';
                if (str_contains($keyName, 'kode_brg_metd_unique')) {
                    $fieldName = 'Kode Barang';
                } elseif (str_contains($keyName, 'master_products')) {
                    $fieldName = 'Master Product';
                } elseif (str_contains($keyName, 'master_customers')) {
                    $fieldName = 'Master Customer';
                }
                $userMessage = "Gagal mengunggah data. Terdapat data duplikat untuk '{$duplicateValue}' pada kolom {$fieldName}. Pastikan {$fieldName} unik.";
                return response()->json(['message' => $userMessage], 500);
            }

            // Deteksi pesan error format tanggal Carbon
            if ($e instanceof InvalidFormatException) {
                $errorColumn = 'tanggal'; // Default
                // Coba identifikasi kolom yang menyebabkan error berdasarkan pesan exception atau konteks
                if (str_contains($e->getMessage(), 'expired_date')) {
                    $errorColumn = 'expired_date';
                } elseif (str_contains($e->getMessage(), 'tgl_terima_brg')) {
                    $errorColumn = 'tgl_terima_brg';
                } elseif (str_contains($e->getMessage(), 'tgl_faktur')) {
                    $errorColumn = 'tgl_faktur';
                } elseif (str_contains($e->getMessage(), 'exp_date')) {
                    $errorColumn = 'exp_date';
                } elseif (str_contains($e->getMessage(), 'tgl_transaksi')) {
                    $errorColumn = 'tgl_transaksi';
                }

                // Pesan error yang lebih jelas untuk ambiguitas YYYY-MM-DD
                $userMessage = "Format tanggal tidak valid pada kolom '{$errorColumn}'. Pastikan format yang digunakan adalah 'Tahun-Bulan-Hari' (YYYY-MM-DD), contoh: 2025-07-15. Periksa kembali urutan Bulan dan Hari.";
                return response()->json(['message' => $userMessage], 422);
            }

            // Tangani error header CSV
            if (str_contains($e->getMessage(), 'Header CSV tidak sesuai')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            // Error umum lainnya
            $userMessage = 'Terjadi kesalahan internal saat menyimpan data. Mungkin ada data yang tidak sesuai format yang diberikan.';
            return response()->json(['message' => $userMessage], 500);
        }
    }

    /**
     * Membersihkan data yang sudah ada berdasarkan tipe data dan tanggal upload.
     *
     * @param string $dataType
     * @param string $uploadDate
     * @return void
     */
    protected function clearExistingData(string $dataType, string $uploadDate): void
    {
        switch ($dataType) {
            case 'Master Product':
                MasterProduct::truncate();
                Log::info('Truncated all Master Product data.');
                break;
            case 'Master Customer':
                MasterCustomer::truncate();
                Log::info('Truncated all Master Customer data.');
                break;
            case 'Stock METD':
                StockMetd::whereDate('created_at', $uploadDate)->delete();
                Log::info('Deleted Stock METD data for date: ' . $uploadDate);
                break;
            case 'Sellout Faktur':
                SellOutFaktur::whereDate('created_at', $uploadDate)->delete();
                Log::info('Deleted Sellout Faktur data for date: ' . $uploadDate);
                break;
            case 'Sellout Nonfaktur':
                SellOutNonfaktur::whereDate('created_at', $uploadDate)->delete();
                Log::info('Deleted Sellout Nonfaktur data for date: ' . $uploadDate);
                break;
        }
    }

    /**
     * Menampilkan detail data CSV yang sudah diparse dari file yang disimpan.
     *
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showParsedData(int $id)
    {
        $fileRecord = UploadedFileRecord::find($id);

        if (!$fileRecord) {
            return redirect()->route('uploaded-files.index')->with('error', 'File tidak ditemukan.');
        }

        $fileContent = Storage::disk('public')->get($fileRecord->file_path);
        $parsedData = [];
        $headers = [];

        if ($fileContent) {
            $rows = explode("\n", $fileContent);
            if (!empty($rows)) {
                $headers = str_getcsv(array_shift($rows), ';');
            }
            foreach ($rows as $rowString) {
                $rowString = trim($rowString);
                if (empty($rowString)) {
                    continue;
                }
                $row = str_getcsv($rowString, ';');

                if (count($row) === count($headers)) {
                    $parsedData[] = array_combine($headers, $row);
                } else {
                    Log::warning("Baris dilewati saat menampilkan detail karena jumlah kolom tidak cocok: " . $rowString);
                }
            }
        }

        $originalFileName = $fileRecord->original_file_name;
        $dataType = $fileRecord->data_type;

        return view('uploaded_files.show', compact('fileRecord', 'parsedData', 'originalFileName', 'dataType', 'headers'));
    }
}
