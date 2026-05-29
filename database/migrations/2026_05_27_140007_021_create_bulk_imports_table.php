<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bulk_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('records_imported')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulk_imports');
    }
};
