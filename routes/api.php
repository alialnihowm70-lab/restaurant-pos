<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
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

// Customer Self-Ordering API (public, no auth)
Route::post('/customer/orders', [App\Http\Controllers\CustomerMenuController::class, 'submitOrder']);

// ── Desktop POS Sync API (secured via sync_token middleware) ──
Route::prefix('desktop')->middleware(['sync_token'])->group(function () {
    // Web order polling for desktop app
    Route::get('/pending-web-orders', [App\Http\Controllers\CustomerMenuController::class, 'pendingOrders']);
    Route::post('/claim-web-order', [App\Http\Controllers\CustomerMenuController::class, 'claimOrder']);

    // GET /api/desktop/logs - Temporary route to view Laravel logs on Render for debugging
    Route::get('/logs', function () {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return response('No log file found.', 200, ['Content-Type' => 'text/plain']);
        }
        $logs = file_get_contents($logPath);
        return response(substr($logs, -20000), 200, ['Content-Type' => 'text/plain']);
    });

    // GET /api/desktop/catalog - Pull all products & locations for desktop app
    Route::get('/catalog', function () {
        $products = \App\Models\Product::select('id', 'name', 'base_price', 'category', 'image_url', 'created_at', 'updated_at')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $locations = \App\Models\Location::select('id', 'name', 'created_at', 'updated_at')->orderBy('name')->get();
        $ingredients = \App\Models\Ingredient::select('id', 'name', 'unit', 'alert_threshold', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();
        $recipes = DB::table('product_ingredient')
            ->select('product_id', 'ingredient_id', 'quantity_needed')
            ->get()
            ->map(function ($recipe) {
                $recipe->id = $recipe->product_id . ':' . $recipe->ingredient_id;
                return $recipe;
            });
        $transactions = \App\Models\InventoryTransaction::select(
                'id',
                'product_id',
                'location_id',
                'quantity',
                'unit_cost',
                'source_id',
                'order_id',
                'type',
                'created_at',
                'updated_at'
            )
            ->latest()
            ->limit(2000)
            ->get();

        return response()->json([
            'success' => true,
            'products' => $products,
            'locations' => $locations,
            'ingredients' => $ingredients,
            'recipes' => $recipes,
            'inventory_transactions' => $transactions,
            'synced_at' => now()->toISOString(),
        ]);
    });

    // POST /api/desktop/sync - Push offline orders from desktop app to server
    Route::post('/sync', function () {
        $orders = request()->input('orders', []);
        $products = request()->input('products', []);
        $locations = request()->input('locations', []);
        $ingredients = request()->input('ingredients', []);
        $recipes = request()->input('recipes', []);
        $transactions = request()->input('inventory_transactions', []);

        if (
            empty($orders) &&
            empty($products) &&
            empty($locations) &&
            empty($ingredients) &&
            empty($recipes) &&
            empty($transactions)
        ) {
            return response()->json(['success' => true, 'inserted' => 0, 'message' => 'No desktop changes to sync']);
        }

        $inserted = 0;
        $skipped = 0;
        $errors = [];
        $syncedIds = [];
        $syncedTransactionIds = [];
        $syncedProductIds = [];
        $syncedLocationIds = [];
        $syncedIngredientIds = [];
        $syncedRecipeIds = [];

        DB::beginTransaction();
        try {
            foreach ($locations as $locationData) {
                if (empty($locationData['name'])) {
                    continue;
                }

                $location = !empty($locationData['id'])
                    ? \App\Models\Location::withTrashed()->find($locationData['id'])
                    : null;
                $location ??= new \App\Models\Location();
                if (!empty($locationData['id'])) {
                    $location->id = $locationData['id'];
                }
                if (method_exists($location, 'restore') && $location->trashed()) {
                    $location->restore();
                }
                $location->forceFill([
                    'name' => $locationData['name'],
                    'updated_at' => now(),
                ])->save();
                $syncedLocationIds[] = $location->id;
            }

            foreach ($products as $productData) {
                if (empty($productData['name'])) {
                    continue;
                }

                $product = !empty($productData['id'])
                    ? \App\Models\Product::withTrashed()->find($productData['id'])
                    : null;
                $product ??= new \App\Models\Product();
                if (!empty($productData['id'])) {
                    $product->id = $productData['id'];
                }
                if (method_exists($product, 'restore') && $product->trashed()) {
                    $product->restore();
                }
                $product->forceFill([
                    'name' => $productData['name'],
                    'base_price' => (float)($productData['base_price'] ?? 0),
                    'category' => $productData['category'] ?? 'عام',
                    'image_url' => $productData['image_url'] ?? null,
                    'updated_at' => now(),
                ])->save();
                $syncedProductIds[] = $product->id;
            }

            foreach ($ingredients as $ingredientData) {
                if (empty($ingredientData['name'])) {
                    continue;
                }

                $ingredient = !empty($ingredientData['id'])
                    ? \App\Models\Ingredient::withTrashed()->find($ingredientData['id'])
                    : null;
                $ingredient ??= new \App\Models\Ingredient();
                if (!empty($ingredientData['id'])) {
                    $ingredient->id = $ingredientData['id'];
                }
                if (method_exists($ingredient, 'restore') && $ingredient->trashed()) {
                    $ingredient->restore();
                }
                $ingredient->forceFill([
                    'name' => $ingredientData['name'],
                    'unit' => $ingredientData['unit'] ?? 'unit',
                    'alert_threshold' => (float)($ingredientData['alert_threshold'] ?? 0),
                    'updated_at' => now(),
                ])->save();
                $syncedIngredientIds[] = $ingredient->id;
            }

            foreach ($recipes as $recipeData) {
                if (empty($recipeData['product_id']) || empty($recipeData['ingredient_id'])) {
                    continue;
                }

                DB::table('product_ingredient')->updateOrInsert(
                    [
                        'product_id' => $recipeData['product_id'],
                        'ingredient_id' => $recipeData['ingredient_id'],
                    ],
                    ['quantity_needed' => (float)($recipeData['quantity_needed'] ?? 0)]
                );

                $syncedRecipeIds[] = $recipeData['id'] ?? ($recipeData['product_id'] . ':' . $recipeData['ingredient_id']);
            }

            foreach ($orders as $orderData) {
                $locationId = $orderData['location_id'] ?? null;
                if (!$locationId || !\App\Models\Location::find($locationId)) {
                    $locationId = \App\Models\Location::first()?->id;
                }

                if (!$locationId) {
                    throw new \RuntimeException('No branch location exists for desktop order sync.');
                }

                $order = !empty($orderData['id']) ? \App\Models\Order::withTrashed()->find($orderData['id']) : null;
                if (!$order && !empty($orderData['invoice_number'])) {
                    $order = \App\Models\Order::withTrashed()->where('invoice_number', $orderData['invoice_number'])->first();
                }
                $isNewOrder = !$order;
                $order ??= new \App\Models\Order();
                $order->id = $orderData['id'] ?? $order->id ?? (string)\Illuminate\Support\Str::uuid();
                if (method_exists($order, 'restore') && $order->trashed()) {
                    $order->restore();
                }
                $order->forceFill([
                    'invoice_number' => $orderData['invoice_number'] ?? $order->invoice_number,
                    'location_id'    => $locationId,
                    'status'         => $orderData['status'] ?? 'completed',
                    'payment_status' => $orderData['payment_status'] ?? 'paid',
                    'total_amount'   => (float)($orderData['total_amount'] ?? 0),
                    'discount'       => (float)($orderData['discount'] ?? 0),
                    'tax'            => (float)($orderData['tax'] ?? 0),
                    'notes'          => $orderData['notes'] ?? null,
                    'sync_status'    => 'synced',
                    'created_at'     => $orderData['created_at'] ?? $order->created_at ?? now(),
                    'updated_at'     => now(),
                ])->save();

                \App\Models\Payment::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'payment_method' => $orderData['payment_method'] ?? 'cash',
                    ],
                    [
                        'amount' => (float)($orderData['total_amount'] ?? 0),
                        'transaction_id' => $orderData['transaction_id'] ?? null,
                        'status' => 'completed',
                    ]
                );

                $order->items()->delete();
                foreach (($orderData['items'] ?? []) as $item) {
                    $product = isset($item['product_id']) ? \App\Models\Product::find($item['product_id']) : null;
                    if ($product) {
                        \App\Models\OrderItem::create([
                            'id'         => (string)\Illuminate\Support\Str::uuid(),
                            'order_id'   => $order->id,
                            'product_id' => $product->id,
                            'quantity'   => (int)($item['quantity'] ?? 1),
                            'price'      => (float)($item['price'] ?? 0),
                        ]);
                    }
                }

                \App\Models\InventoryTransaction::where('order_id', $order->id)->delete();
                $syncedIds[] = $order->id;
                $isNewOrder ? $inserted++ : $skipped++;
            }

            foreach ($transactions as $txData) {
                // Acknowledge ingredient transactions since server doesn't track ingredient transactions directly
                if (empty($txData['product_id']) && !empty($txData['ingredient_id'])) {
                    if (!empty($txData['id'])) {
                        $syncedTransactionIds[] = $txData['id'];
                    }
                    continue;
                }

                if (empty($txData['product_id'])) {
                    continue;
                }

                $txLocationId = $txData['location_id'] ?? null;
                if (!$txLocationId || !\App\Models\Location::find($txLocationId)) {
                    $txLocationId = \App\Models\Location::first()?->id;
                }

                if (!$txLocationId) {
                    continue;
                }

                $tx = !empty($txData['id']) ? \App\Models\InventoryTransaction::find($txData['id']) : null;
                $tx ??= new \App\Models\InventoryTransaction();
                $tx->id = $txData['id'] ?? $tx->id ?? (string)\Illuminate\Support\Str::uuid();
                $tx->forceFill([
                    'product_id' => $txData['product_id'],
                    'location_id' => $txLocationId,
                    'quantity' => (float)($txData['quantity'] ?? 0),
                    'unit_cost' => (float)($txData['unit_cost'] ?? 0),
                    'source_id' => $txData['source_id'] ?? null,
                    'order_id' => $txData['order_id'] ?? null,
                    'type' => $txData['type'] ?? ((float)($txData['quantity'] ?? 0) < 0 ? 'sale' : 'restock'),
                    'created_at' => $txData['created_at'] ?? $tx->created_at ?? now(),
                    'updated_at' => now(),
                ])->save();
                $syncedTransactionIds[] = $tx->id;
            }

            DB::commit();

            return response()->json([
                'success'  => count($errors) === 0,
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors,
                'synced_ids' => $syncedIds,
                'synced_transaction_ids' => $syncedTransactionIds,
                'synced_product_ids' => $syncedProductIds,
                'synced_location_ids' => $syncedLocationIds,
                'synced_ingredient_ids' => $syncedIngredientIds,
                'synced_recipe_ids' => $syncedRecipeIds,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = $e->getMessage();
            return response()->json([
                'success'  => false,
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ], 500);
        }
    });
});

