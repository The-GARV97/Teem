<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['org_id', 'name']);
            $table->index('org_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
