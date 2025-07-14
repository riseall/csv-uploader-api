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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // Import Storage facade
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    /**
     * Menerima file CSV dari frontend, menyimpannya ke storage, dan memproses data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function receiveData(Request $request)
    {
        // Validasi input dari Server Public (frontend)
        $validator = Validator::make($request->all(), [
            'data_type' => 'required|in:Master Product,Master Customer,Stock METD,Sellout Faktur,Sellout Nonfaktur',
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // Validasi file yang diupload
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
            // 1. Simpan file ke storage (misalnya, folder 'public/uploads/csv')
            // Buat folder berdasarkan tipe data dan tanggal untuk organisasi yang lebih baik
            $folderPath = 'uploads/' . $dataType . '/' . $uploadDate;
            $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
            $filePath = $uploadedFile->storeAs($folderPath, $fileName, 'public'); // Simpan ke disk 'public'

            // Periksa apakah file berhasil disimpan
            if (!$filePath) {
                throw new \Exception('Gagal menyimpan file ke storage.');
            }

            // 2. Simpan record file yang diupload ke tabel uploaded_file_records
            UploadedFileRecord::create([
                'data_type' => $dataType,
                'original_file_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'file_path' => $filePath, // Simpan path file yang disimpan
                'upload_date' => $uploadDate,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 3. Baca konten file yang baru disimpan untuk diproses
            $fileContent = Storage::disk('public')->get($filePath);

            // 4. Parse konten CSV yang diterima untuk mendapatkan payload data
            $parsedPayload = [];
            $rows = explode("\n", $fileContent); // Pisahkan baris

            // Ambil header dari baris pertama
            $header = [];
            if (!empty($rows)) {
                // Perhatikan: Delimiter yang digunakan adalah ';' sesuai dengan parsing di frontend asli Anda
                // Jika CSV Anda menggunakan koma, ubah menjadi ','
                $header = str_getcsv(array_shift($rows), ';');
            }

            if (empty($header)) {
                throw new \Exception('Header CSV tidak dapat dibaca atau kosong dari file yang disimpan.');
            }

            foreach ($rows as $rowString) {
                $rowString = trim($rowString); // Hapus spasi di awal/akhir baris
                if (empty($rowString)) {
                    continue; // Lewati baris kosong
                }

                // Perhatikan: Delimiter yang digunakan adalah ';'
                $row = str_getcsv($rowString, ';');

                // Cek jika jumlah kolom tidak cocok dengan header, lewati baris ini
                if (count($row) !== count($header)) {
                    Log::warning("Baris dilewati karena jumlah kolom tidak cocok setelah parsing dari file yang disimpan: " . $rowString);
                    continue;
                }
                $parsedPayload[] = array_combine($header, $row);
            }

            if (empty($parsedPayload)) {
                throw new \Exception('File CSV kosong atau format tidak valid setelah parsing dari file yang disimpan.');
            }

            // 5. Panggil logika untuk membersihkan data lama di tabel spesifik
            $this->clearExistingData($dataType, $uploadDate);

            // 6. Loop melalui data dari payload yang diparse dan simpan ke database
            foreach ($parsedPayload as $data) {
                // Pastikan nilai null untuk kolom yang tidak ada di CSV
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

            DB::commit(); // Jika semua berhasil, simpan perubahan

            return response()->json(['message' => 'Data ' . $dataType . ' berhasil disimpan ke database.'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua query jika terjadi error
            Log::error('Gagal menyimpan data dari API: ' . $e->getMessage() . ' at line ' . $e->getLine());
            return response()->json(['message' => 'Terjadi kesalahan internal saat menyimpan data. Mungkin ada data duplicat atau tidak valid'], 500);
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
                break;
            case 'Master Customer':
                MasterCustomer::truncate();
                break;
            case 'Stock METD':
                StockMetd::whereDate('created_at', $uploadDate)->delete();
                break;
            case 'Sellout Faktur':
                SellOutFaktur::whereDate('created_at', $uploadDate)->delete();
                break;
            case 'Sellout Nonfaktur':
                SellOutNonfaktur::whereDate('created_at', $uploadDate)->delete();
                break;
        }
    }

    /**
     * Menampilkan daftar semua file yang telah diupload.
     *
     * @return \Illuminate\View\View
     */
    // public function showData()
    // {
    //     $mstrProd = MasterProduct::orderBy('created_at', 'desc')->get();
    //     $mstrCust = MasterCustomer::orderBy('created_at', 'desc')->get();
    //     $stock = StockMetd::orderBy('created_at', 'desc')->get();
    //     $faktur = SellOutFaktur::orderBy('created_at', 'desc')->get();
    //     $nonfaktur = SellOutNonfaktur::orderBy('created_at', 'desc')->get();
    //     return view('data', compact('mstrProd', 'mstrCust', 'stock', 'faktur', 'nonfaktur'));
    // }

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

        // Baca konten file dari storage
        $fileContent = Storage::disk('public')->get($fileRecord->file_path);

        $parsedData = [];
        $headers = [];

        if ($fileContent) {
            $rows = explode("\n", $fileContent);
            if (!empty($rows)) {
                // Perhatikan: Delimiter yang digunakan adalah ';'
                $headers = str_getcsv(array_shift($rows), ';');
            }

            foreach ($rows as $rowString) {
                $rowString = trim($rowString);
                if (empty($rowString)) {
                    continue;
                }
                // Perhatikan: Delimiter yang digunakan adalah ';'
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
