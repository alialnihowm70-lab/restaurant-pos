<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\PaymentGatewayController;

// Delta Sync Engine Routes
Route::prefix('sync')->group(function () {
    Route::get('/pull', [SyncController::class, 'pull']);
    Route::post('/push', [SyncController::class, 'push']);
});

// Libyan Local Payment Gateways API
Route::prefix('payments')->group(function () {
    // Sadad API
    Route::post('/sadad/checkout', [PaymentGatewayController::class, 'sadadCheckout']);
    Route::post('/sadad/webhook', [PaymentGatewayController::class, 'sadadWebhook']);

    // MobiCash API
    Route::post('/mobicash/qr', [PaymentGatewayController::class, 'mobicashQr']);
    Route::get('/mobicash/status/{payment_id}', [PaymentGatewayController::class, 'mobicashStatus']);

    // Tadawul POS API
    Route::post('/tadawul/transact', [PaymentGatewayController::class, 'tadawulTransact']);
    Route::get('/tadawul/status/{payment_id}', [PaymentGatewayController::class, 'tadawulStatus']);
});

// Low Stock Ingredients Checker API
Route::get('/ingredients/low-stock', function () {
    $ingredients = \App\Models\Ingredient::all();
    $lowStock = [];
    foreach ($ingredients as $ing) {
        $stock = 0;
        foreach ($ing->products as $product) {
            $productStock = \Illuminate\Support\Facades\DB::table('inventory_transactions')
                ->where('product_id', $product->id)
                ->sum('quantity') ?? 0;
            $stock += $productStock * $product->pivot->quantity_needed;
        }
        if ($stock <= $ing->alert_threshold) {
            $lowStock[] = [
                'id' => $ing->id,
                'name' => $ing->name,
                'unit' => $ing->unit,
                'alert_threshold' => (float)$ing->alert_threshold,
                'current_stock' => (float)$stock
            ];
        }
    }
    return response()->json($lowStock);
});

