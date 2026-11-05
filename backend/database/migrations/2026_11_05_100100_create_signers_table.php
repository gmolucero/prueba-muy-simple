<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('rut');
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('token_expires_at');
            $table->string('state')->default('pendiente');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            // Un mismo RUT no puede repetirse dentro del mismo documento.
            $table->unique(['document_id', 'rut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signers');
    }
};
