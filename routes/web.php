<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Location;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplierBankAccount;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;

// Root redirect
Route::get('/', function () {
    return redirect('/pos');
});

// Database Connection Test Route
Route::get('/db-test', function () {
    try {
        $dbName = DB::connection()->getDatabaseName();
        $driver = DB::connection()->getDriverName();
        
        // Try a simple query
        $now = DB::select('SELECT NOW() as current_time');
        
        // Try creating a temporary table or insert/update to test write capability
        DB::beginTransaction();
        DB::statement('CREATE TEMPORARY TABLE test_write_table (id serial PRIMARY KEY, val varchar(50))');
        DB::insert('insert into test_write_table (val) values (?)', ['test_write']);
        $testResult = DB::select('select * from test_write_table');
        DB::rollBack();
        
        return response()->json([
            'status' => 'success',
            'driver' => $driver,
            'database' => $dbName,
            'current_time' => $now[0]->current_time ?? null,
            'write_test' => 'successful',
            'records' => $testResult,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'driver' => DB::connection()->getDriverName() ?? 'unknown',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── JSON API Endpoints (authenticated users only, any role) ──
Route::middleware(['auth'])->group(function () {
    // Real-time active orders polling (JSON) – used by POS, Active Orders board, KDS
    Route::get('/api/orders/active.json', function () {
        $orders = Order::with('items.product', 'location')
            ->whereIn('status', ['pending', 'cooking', 'ready'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id'             => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'status'         => $order->status,
                    'notes'          => $order->notes,
                    'total_amount'   => $order->total_amount,
                    'created_at'     => $order->created_at->toISOString(),
                    'location'       => ['id' => $order->location->id, 'name' => $order->location->name],
                    'items'          => $order->items->map(fn($i) => [
                        'id'       => $i->id,
                        'quantity' => $i->quantity,
                        'price'    => $i->price,
                        'product'  => $i->product ? ['id' => $i->product->id, 'name' => $i->product->name] : null,
                    ]),
                ];
            });
        return response()->json(['orders' => $orders, 'timestamp' => now()->toISOString()]);
    });

    // Completed/ready orders count for cashier notification badge
    Route::get('/api/orders/ready-count.json', function () {
        $count = Order::whereIn('status', ['ready'])->count();
        return response()->json(['ready_count' => $count]);
    });

    // Offline Syncing API endpoints
    Route::get('/api/products.json', function () {
        return response()->json(['products' => \App\Models\Product::all()]);
    });

    Route::get('/api/locations.json', function () {
        return response()->json(['locations' => \App\Models\Location::all()]);
    });

    Route::post('/api/orders/sync', [\App\Http\Controllers\SyncController::class, 'push']);
});

// ── Desktop POS Sync API (no auth required - for local network desktop app) ──
Route::prefix('api/desktop')->group(function () {

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
                if ($locationId && !\App\Models\Location::find($locationId)) {
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
                if (empty($txData['product_id']) || empty($txData['location_id'])) {
                    continue;
                }

                $tx = !empty($txData['id']) ? \App\Models\InventoryTransaction::find($txData['id']) : null;
                $tx ??= new \App\Models\InventoryTransaction();
                $tx->id = $txData['id'] ?? $tx->id ?? (string)\Illuminate\Support\Str::uuid();
                $tx->forceFill([
                    'product_id' => $txData['product_id'],
                    'location_id' => $txData['location_id'],
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

// 1. Cashier POS Routes (Restricted to Cashier and Admin)
Route::middleware(['role:cashier'])->group(function () {
    Route::get('/pos', function () {
        $products = Product::all();
        $locations = Location::all();
        $categories = Product::select('category')->distinct()->pluck('category');
        return view('pos', compact('products', 'locations', 'categories'));
    });

    Route::get('/api/active-tables', function () {
        $occupied = Order::whereIn('status', ['pending', 'cooking', 'ready'])
            ->where(function ($query) {
                $query->where('notes', 'like', '%[محلي - طاولة%')
                      ->orWhere('notes', 'like', '%[في المطعم - طاولة%');
            })
            ->pluck('notes')
            ->map(function ($note) {
                if (preg_match('/\[(?:محلي|في المطعم) - طاولة (\d+)\]/', $note, $matches)) {
                    return (int)$matches[1];
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        return response()->json(['occupied' => $occupied]);
    });

    // View Daily Orders (Today's only)
    Route::get('/admin/orders', function () {
        $today = \Illuminate\Support\Carbon::today();
        $orders = Order::with('location', 'payments', 'items.product')
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'desc')
            ->get();
        $products  = Product::all();
        $isArchive = false;
        $filterStart = $today->format('Y-m-d');
        $filterEnd   = $today->format('Y-m-d');
        return view('orders', compact('orders', 'products', 'isArchive', 'filterStart', 'filterEnd'));
    });

    // View Archive Orders (Based on Date Filter via GET query params)
    Route::get('/admin/orders/archive', function () {
        // Accept from query string; fall back to session; fall back to today
        $startRaw = request('start_date') ?? session('start_date') ?? \Illuminate\Support\Carbon::today()->toDateString();
        $endRaw   = request('end_date')   ?? session('end_date')   ?? \Illuminate\Support\Carbon::today()->toDateString();

        $startDate = \Illuminate\Support\Carbon::parse($startRaw)->startOfDay();
        $endDate   = \Illuminate\Support\Carbon::parse($endRaw)->endOfDay();

        // Persist to session for subsequent page loads
        session(['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]);

        $orders = Order::with('location', 'payments', 'items.product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
        $products = Product::all();
        $isArchive = true;
        $filterStart = $startDate->format('Y-m-d');
        $filterEnd   = $endDate->format('Y-m-d');
        return view('orders', compact('orders', 'products', 'isArchive', 'filterStart', 'filterEnd'));
    });

    // Edit Order Action
    Route::post('/pos/orders/{order}/update', function (Order $order) {
        request()->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($order) {
            $itemsData = request('items');
            $discount = (float)request('discount');
            $tax = (float)request('tax');
            
            $subtotal = 0;
            $itemsToCreate = [];
            
            foreach ($itemsData as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (int)$item['quantity'];
                $price = (float)$product->base_price;
                $subtotal += $qty * $price;
                
                $itemsToCreate[] = [
                    'id' => (string)\Illuminate\Support\Str::uuid(),
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'created_at' => $order->created_at,
                    'updated_at' => \Illuminate\Support\Carbon::now(),
                ];
            }
            
            $totalAmount = max(0, $subtotal - $discount + $tax);
            
            // Update order details
            $order->update([
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'sync_status' => 'synced', // mark as synced
            ]);
            
            // Rebuild OrderItems
            $order->items()->delete();
            OrderItem::insert($itemsToCreate);
            
            // Recreate Inventory Transactions for this order
            InventoryTransaction::where('order_id', $order->id)->delete();
            
            foreach ($itemsToCreate as $item) {
                InventoryTransaction::create([
                    'product_id' => $item['product_id'],
                    'location_id' => $order->location_id,
                    'quantity' => -$item['quantity'],
                    'unit_cost' => $item['price'] * 0.40, // standard cost
                    'type' => 'sale',
                    'order_id' => $order->id,
                    'source_id' => null,
                ]);
            }
        });

        return redirect()->back()->with('success', 'تم تعديل الفاتورة بنجاح وتحديث الجرد المرتبط بها!');
    });
});

// 2. Shared Cashier & Chef Routes (Active Orders Board, Print, Status Updates)
Route::middleware(['role:cashier,chef'])->group(function () {
    // Active Orders Board View (HTML page – initial server-render only)
    Route::get('/admin/orders/active', function () {
        $orders = Order::with('items.product', 'location')
            ->whereIn('status', ['pending', 'cooking', 'ready'])
            ->orderBy('created_at', 'asc')
            ->get();
        return view('active_orders', compact('orders'));
    });

    Route::post('/pos/orders/{order}/status', function (Order $order) {
        request()->validate([
            'status' => 'required|in:pending,cooking,ready,completed,cancelled'
        ]);

        $order->update(['status' => request('status')]);
        return response()->json(['success' => true]);
    });

    Route::post('/pos/orders/{order}/print', function (Order $order) {
        $printService = new \App\Services\ThermalPrintService();
        $ip = request('ip');

        if ($ip) {
            $success = $printService->printToNetwork($ip, $order);
            return response()->json(['success' => $success]);
        }

        $bytes = $printService->generateReceiptBytes($order);
        return response()->json([
            'success' => true,
            'base64' => base64_encode($bytes)
        ]);
    });
});

// 2. Kitchen Display System (KDS) Routes (Restricted to Chef and Admin)
Route::middleware(['role:chef'])->group(function () {
    Route::get('/kds', function () {
        $orders = Order::with('items.product', 'location')
            ->whereIn('status', ['pending', 'cooking', 'ready'])
            ->orderBy('created_at', 'asc')
            ->get();
        return view('kds', compact('orders'));
    });

    Route::post('/kds/orders/{order}/status', function (Order $order) {
        request()->validate([
            'status' => 'required|in:pending,cooking,ready,completed,cancelled'
        ]);

        $order->update(['status' => request('status')]);
        return response()->json(['success' => true]);
    });
});

// 3. Admin Management Dashboard Routes (Restricted to Admin only)
Route::middleware(['role:admin'])->group(function () {
    // Stats Dashboard
    Route::get('/admin', function () {
        $startDate = \Illuminate\Support\Carbon::parse(session('start_date'));
        $endDate = \Illuminate\Support\Carbon::parse(session('end_date'));

        $todayRevenue = (float)Order::where('status', '!=', 'cancelled')
            ->whereDate('created_at', \Illuminate\Support\Carbon::today())
            ->sum('total_amount');

        $monthlyRevenue = (float)Order::where('status', '!=', 'cancelled')
            ->whereMonth('created_at', \Illuminate\Support\Carbon::now()->month)
            ->whereYear('created_at', \Illuminate\Support\Carbon::now()->year)
            ->sum('total_amount');

        $yearlyRevenue = (float)Order::where('status', '!=', 'cancelled')
            ->whereYear('created_at', \Illuminate\Support\Carbon::now()->year)
            ->sum('total_amount');

        $suppliers = SupplierBankAccount::all();
        $products = Product::all();
        $locations = Location::all();
        $users = User::all();

        $salesCount = Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $salesTotal = (float)Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
        $taxTotal = (float)Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('tax');
        $discountTotal = (float)Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('discount');
        
        $stockLevel = InventoryTransaction::where('created_at', '<=', $endDate)->sum('quantity');

        // Cost of Goods Sold (COGS)
        $totalCogs = (float)DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->sum(DB::raw('order_items.quantity * products.base_price * 0.40'));

        // Inventory Losses (type = 'waste')
        $totalWasteCost = (float)InventoryTransaction::where('type', 'waste')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('SUM(ABS(quantity) * unit_cost) as total')
            ->value('total') ?? 0.0;

        // Inventory Adjustments (type = 'adjustment')
        $totalAdjustmentSurplus = (float)InventoryTransaction::where('type', 'adjustment')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('SUM(quantity * unit_cost) as total')
            ->value('total') ?? 0.0;

        $netSales = $salesTotal - $taxTotal;
        $grossProfit = $netSales - $totalCogs - $totalWasteCost + $totalAdjustmentSurplus;
        $profitMargin = $netSales > 0 ? ($grossProfit / $netSales) * 100 : 0;

        // Inventory Asset Value
        $inventoryAssetValue = (float)DB::table('inventory_transactions')
            ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
            ->where('inventory_transactions.created_at', '<=', $endDate)
            ->sum(DB::raw('inventory_transactions.quantity * products.base_price * 0.40'));

        // Sales by payment method
        $salesByPayment = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.payment_method')
            ->pluck('total', 'payments.payment_method')
            ->toArray();

        // Sales by location
        $salesByLocation = DB::table('orders')
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('locations.name', DB::raw('SUM(orders.total_amount) as total'))
            ->groupBy('locations.name')
            ->pluck('total', 'locations.name')
            ->toArray();

        $topSelling = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->orderBy('total_qty', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $product = Product::find($item->product_id);
                $item->name = $product ? $product->name : 'Deleted Product';
                return $item;
            });

        // Dynamic Low Stock Ingredients List
        $lowStockIngredients = [];
        $ingredients = Ingredient::all();
        foreach ($ingredients as $ing) {
            $stock = 0;
            foreach ($ing->products as $product) {
                $productStock = (float)DB::table('inventory_transactions')
                    ->where('product_id', $product->id)
                    ->sum('quantity') ?? 0;
                $stock += $productStock * $product->pivot->quantity_needed;
            }
            if ($stock <= $ing->alert_threshold) {
                $lowStockIngredients[] = [
                    'id' => $ing->id,
                    'name' => $ing->name,
                    'unit' => $ing->unit,
                    'alert_threshold' => (float)$ing->alert_threshold,
                    'current_stock' => (float)$stock
                ];
            }
        }

        return view('admin', compact(
            'suppliers', 'products', 'locations', 'salesCount', 'salesTotal', 'taxTotal', 'discountTotal',
            'stockLevel', 'totalCogs', 'grossProfit', 'profitMargin', 'inventoryAssetValue',
            'salesByPayment', 'salesByLocation', 'topSelling', 'startDate', 'endDate',
            'totalWasteCost', 'totalAdjustmentSurplus', 'users', 'lowStockIngredients',
            'todayRevenue', 'monthlyRevenue', 'yearlyRevenue'
        ));
    });

    // Supplier Management
    Route::post('/admin/suppliers', function () {
        request()->validate([
            'supplier_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_no' => 'required|string|max:255',
            'swift_code' => 'nullable|string|max:255',
        ]);

        SupplierBankAccount::create(request()->only('supplier_name', 'bank_name', 'account_no', 'swift_code'));
        return redirect('/admin')->with('success', 'Supplier bank account registered successfully.');
    });

    Route::post('/admin/suppliers/{supplier}/update', function (SupplierBankAccount $supplier) {
        request()->validate([
            'supplier_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_no' => 'required|string|max:255',
            'swift_code' => 'nullable|string|max:255',
        ]);

        $supplier->update(request()->only('supplier_name', 'bank_name', 'account_no', 'swift_code'));
        return redirect('/admin')->with('success', 'تم تحديث بيانات المورد بنجاح.');
    });

    Route::post('/admin/suppliers/{supplier}/delete', function (SupplierBankAccount $supplier) {
        $supplier->delete();
        return redirect('/admin')->with('success', 'تم حذف المورد بنجاح.');
    });

    // User CRUD Management
    Route::post('/admin/users', function () {
        request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,cashier,chef',
        ]);

        User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => bcrypt(request('password')),
            'role' => request('role'),
        ]);

        return redirect('/admin')->with('success', 'تم تسجيل حساب الموظف الجديد بنجاح!');
    });

    Route::post('/admin/users/{user}/update', function (User $user) {
        request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,cashier,chef',
        ]);

        $data = [
            'name' => request('name'),
            'email' => request('email'),
            'role' => request('role'),
        ];

        if (request('password')) {
            $data['password'] = bcrypt(request('password'));
        }

        $user->update($data);

        return redirect('/admin')->with('success', 'تم تحديث بيانات الموظف بنجاح!');
    });

    Route::post('/admin/users/{user}/delete', function (User $user) {
        if ($user->id === auth()->id()) {
            return redirect('/admin')->with('error', 'لا يمكنك حذف حسابك الشخصي الذي تستخدمه حالياً!');
        }

        $user->delete();
        return redirect('/admin')->with('success', 'تم حذف حساب الموظف بنجاح!');
    });

    // Catalog & Inventory Manager
    Route::get('/admin/inventory', function () {
        $startDate = \Illuminate\Support\Carbon::parse(session('start_date'));
        $endDate = \Illuminate\Support\Carbon::parse(session('end_date'));

        $products = Product::with('ingredients')->get();
        $locations = Location::all();
        $ingredients = Ingredient::with('products')->get();
        $transactions = InventoryTransaction::with('product', 'location')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $stockLevels = DB::table('inventory_transactions')
            ->select('product_id', DB::raw('SUM(quantity) as stock'))
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        // Stocks per location for the Reconciliation/Stocktake UI
        $locationStocks = DB::table('inventory_transactions')
            ->select('product_id', 'location_id', DB::raw('SUM(quantity) as stock'))
            ->groupBy('product_id', 'location_id')
            ->get()
            ->groupBy('location_id')
            ->map(function ($items) {
                return $items->pluck('stock', 'product_id');
            });

        // Dynamic Low Stock Ingredients List & attach current stock
        $lowStockIngredients = [];
        foreach ($ingredients as $ing) {
            $stock = 0;
            foreach ($ing->products as $product) {
                $productStock = (float)($stockLevels[$product->id] ?? 0);
                $stock += $productStock * $product->pivot->quantity_needed;
            }
            $ing->current_stock = $stock;

            if ($stock <= $ing->alert_threshold) {
                $lowStockIngredients[] = [
                    'id' => $ing->id,
                    'name' => $ing->name,
                    'unit' => $ing->unit,
                    'alert_threshold' => (float)$ing->alert_threshold,
                    'current_stock' => (float)$stock
                ];
            }
        }

        return view('inventory', compact('products', 'locations', 'ingredients', 'transactions', 'stockLevels', 'locationStocks', 'lowStockIngredients'));
    });

    // Inventory Reconciliation Action (POST)
    Route::post('/admin/inventory/reconcile', function () {
        request()->validate([
            'location_id' => 'required|uuid',
            'counts' => 'required|array',
            'counts.*' => 'nullable|numeric|min:0',
        ]);

        $locationId = request('location_id');
        $counts = request('counts');

        DB::transaction(function () use ($locationId, $counts) {
            foreach ($counts as $productId => $physicalCount) {
                if ($physicalCount === null || $physicalCount === '') {
                    continue; // Skip products with no physical count entered
                }

                $systemStock = (float)InventoryTransaction::where('product_id', $productId)
                    ->where('location_id', $locationId)
                    ->sum('quantity');

                $variance = $physicalCount - $systemStock;

                if ($variance != 0) {
                    $product = Product::findOrFail($productId);
                    InventoryTransaction::create([
                        'product_id' => $productId,
                        'location_id' => $locationId,
                        'quantity' => $variance,
                        'unit_cost' => $product->base_price * 0.40, // standard cost
                        'type' => $variance < 0 ? 'waste' : 'adjustment',
                        'source_id' => null,
                    ]);
                }
            }
        });

        return redirect('/admin/inventory')->with('success', 'تمت عملية جرد المخزن وتسوية الفروقات بنجاح!');
    });

    // Products Management
    Route::post('/admin/products', function () {
        request()->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'category' => 'required|string|max:255',
            'image_url' => 'nullable|url|max:2048',
        ]);

        Product::create(request()->only('name', 'base_price', 'category', 'image_url'));
        return redirect('/admin/inventory')->with('success', 'Menu product registered successfully.');
    });

    Route::post('/admin/products/{product}/update', function (Product $product) {
        request()->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'category' => 'required|string|max:255',
            'image_url' => 'nullable|url|max:2048',
        ]);

        $product->update(request()->only('name', 'base_price', 'category', 'image_url'));
        return redirect('/admin/inventory')->with('success', 'Menu product updated successfully.');
    });

    Route::post('/admin/products/{product}/delete', function (Product $product) {
        $product->delete();
        return redirect('/admin/inventory')->with('success', 'Menu product deleted successfully.');
    });

    // Restock Management
    Route::post('/admin/inventory/restock', function () {
        request()->validate([
            'product_id' => 'required|uuid',
            'location_id' => 'required|uuid',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0.01',
        ]);

        InventoryTransaction::create([
            'product_id' => request('product_id'),
            'location_id' => request('location_id'),
            'quantity' => request('quantity'),
            'unit_cost' => request('unit_cost'),
            'source_id' => null
        ]);

        return redirect('/admin/inventory')->with('success', 'Product stock restocked successfully.');
    });

    Route::post('/admin/inventory/transactions/{transaction}/delete', function (InventoryTransaction $transaction) {
        $transaction->delete();
        return redirect('/admin/inventory')->with('success', 'تم حذف عملية التوريد بنجاح.');
    });

    // Branch Locations CRUD
    Route::post('/admin/locations', function () {
        request()->validate([
            'name' => 'required|string|max:255',
        ]);
        Location::create(request()->only('name'));
        return redirect('/admin/inventory')->with('success', 'Branch location registered successfully.');
    });

    Route::post('/admin/locations/{location}/update', function (Location $location) {
        request()->validate([
            'name' => 'required|string|max:255',
        ]);
        $location->update(request()->only('name'));
        return redirect('/admin/inventory')->with('success', 'تم تحديث بيانات الفرع بنجاح.');
    });

    Route::post('/admin/locations/{location}/delete', function (Location $location) {
        $location->delete();
        return redirect('/admin/inventory')->with('success', 'Branch location deleted successfully.');
    });

    // Ingredients CRUD
    Route::post('/admin/ingredients', function () {
        request()->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'alert_threshold' => 'required|numeric|min:0',
        ]);
        Ingredient::create(request()->only('name', 'unit', 'alert_threshold'));
        return redirect('/admin/inventory')->with('success', 'Ingredient registered successfully.');
    });

    Route::post('/admin/ingredients/{ingredient}', function (Ingredient $ingredient) {
        request()->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'alert_threshold' => 'required|numeric|min:0',
        ]);
        $ingredient->update(request()->only('name', 'unit', 'alert_threshold'));
        return redirect('/admin/inventory')->with('success', 'Ingredient updated successfully.');
    });

    Route::post('/admin/ingredients/{ingredient}/delete', function (Ingredient $ingredient) {
        $ingredient->delete();
        return redirect('/admin/inventory')->with('success', 'Ingredient deleted successfully.');
    });

    // Recipes Mapping CRUD
    Route::post('/admin/recipes', function () {
        request()->validate([
            'product_id' => 'required|uuid',
            'ingredient_id' => 'required|uuid',
            'quantity_needed' => 'required|numeric|min:0.0001',
        ]);
        
        $product = Product::findOrFail(request('product_id'));
        $product->ingredients()->syncWithoutDetaching([
            request('ingredient_id') => ['quantity_needed' => request('quantity_needed')]
        ]);
        
        return redirect('/admin/inventory')->with('success', 'Recipe ingredient linked successfully.');
    });

    Route::post('/admin/recipes/{product}/{ingredient}/delete', function (Product $product, Ingredient $ingredient) {
        $product->ingredients()->detach($ingredient->id);
        return redirect('/admin/inventory')->with('success', 'Recipe ingredient removed successfully.');
    });

    // Category Deletion
    Route::post('/admin/categories/delete', function () {
        request()->validate([
            'category' => 'required|string',
        ]);

        $category = request('category');

        DB::transaction(function () use ($category) {
            $products = Product::where('category', $category)->get();
            foreach ($products as $product) {
                // Remove recipe associations
                $product->ingredients()->detach();
                // Delete inventory transactions
                InventoryTransaction::where('product_id', $product->id)->delete();
                // Delete the product
                $product->delete();
            }
        });

        return redirect('/admin/inventory')->with('success', 'تم حذف الفئة وجميع وجباتها بنجاح!');
    });

    // Edit Inventory Transaction
    Route::post('/admin/inventory/transactions/{transaction}/update', function (InventoryTransaction $transaction) {
        request()->validate([
            'quantity' => 'required|numeric',
            'unit_cost' => 'required|numeric|min:0',
            'type' => 'required|in:restock,sale,waste,adjustment',
            'created_at' => 'required|date',
            'location_id' => 'required|uuid|exists:locations,id',
        ]);

        $transaction->update([
            'quantity' => (float)request('quantity'),
            'unit_cost' => (float)request('unit_cost'),
            'type' => request('type'),
            'created_at' => \Illuminate\Support\Carbon::parse(request('created_at')),
            'location_id' => request('location_id'),
        ]);

        return redirect('/admin/inventory')->with('success', 'تم تحديث حركة المخزن بنجاح!');
    });
});
