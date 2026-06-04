<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('locations')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4); // positive for restocks, negative for sales
            $table->decimal('unit_cost', 12, 2);
            $table->uuid('source_id')->nullable(); // referencing original transaction for FIFO tracking
            $table->timestamps();

            // Self-referential index for FIFO tracking
            $table->foreign('source_id')->references('id')->on('inventory_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
