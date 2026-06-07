<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Location;
use App\Models\Ingredient;
use App\Models\Order;
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

// 1. Cashier POS Routes (Restricted to Cashier and Admin)
Route::middleware(['role:cashier'])->group(function () {
    Route::get('/pos', function () {
        $products = Product::all();
        $locations = Location::all();
        $categories = Product::select('category')->distinct()->pluck('category');
        return view('pos', compact('products', 'locations', 'categories'));
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

    Route::get('/api/active-tables', function () {
        $occupied = Order::whereIn('status', ['pending', 'cooking', 'ready'])
            ->where('notes', 'like', '%[محلي - طاولة%')
            ->pluck('notes')
            ->map(function ($note) {
                if (preg_match('/\[محلي - طاولة (\d+)\]/', $note, $matches)) {
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

    Route::post('/pos/orders/{order}/status', function (Order $order) {
        request()->validate([
            'status' => 'required|in:pending,cooking,ready,completed,cancelled'
        ]);

        $order->update(['status' => request('status')]);
        return response()->json(['success' => true]);
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
        $startStr = request('start_date');
        $endStr = request('end_date');

        if ($startStr) {
            $startDate = \Illuminate\Support\Carbon::parse($startStr)->startOfDay();
        } else {
            $startDate = \Illuminate\Support\Carbon::now()->startOfMonth();
        }

        if ($endStr) {
            $endDate = \Illuminate\Support\Carbon::parse($endStr)->endOfDay();
        } else {
            $endDate = \Illuminate\Support\Carbon::now()->endOfMonth();
        }

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
            'totalWasteCost', 'totalAdjustmentSurplus', 'users', 'lowStockIngredients'
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
        $products = Product::with('ingredients')->get();
        $locations = Location::all();
        $ingredients = Ingredient::all();
        $transactions = InventoryTransaction::with('product', 'location')->orderBy('created_at', 'desc')->get();
        
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

        // Dynamic Low Stock Ingredients List
        $lowStockIngredients = [];
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

    // Order History View
    Route::get('/admin/orders', function () {
        $orders = Order::with('location', 'payments', 'items.product')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('orders', compact('orders'));
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
});
