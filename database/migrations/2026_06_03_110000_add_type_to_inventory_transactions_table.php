<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->string('type', 20)->default('restock'); // restock, sale, waste, adjustment
        });

        // Migrate existing rows: if quantity < 0, it's a sale, else restock
        DB::table('inventory_transactions')->where('quantity', '<', 0)->update(['type' => 'sale']);
        DB::table('inventory_transactions')->where('quantity', '>=', 0)->update(['type' => 'restock']);
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
