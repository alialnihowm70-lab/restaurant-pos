<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'source')) {
                $table->string('source')->default('pos')->after('sync_status');
            }
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('source');
            }
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->default('takeaway')->after('customer_name');
            }
            if (!Schema::hasColumn('orders', 'table_number')) {
                $table->integer('table_number')->nullable()->after('order_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source', 'customer_name', 'order_type', 'table_number']);
        });
    }
};
