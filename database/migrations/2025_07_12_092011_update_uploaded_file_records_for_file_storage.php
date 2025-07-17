<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uploaded_file_records_metd', function (Blueprint $table) {
            $table->id();
            $table->string('data_type'); // Tipe data (master_product, stock_metd, dll.)
            $table->string('original_file_name'); // Nama file asli
            $table->string('mime_type'); // Tipe MIME file (e.g., text/csv)
            $table->string('file_path'); // Path file yang disimpan di storage
            $table->date('upload_date'); // Tanggal upload file
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_file_records_metd');
    }
};
