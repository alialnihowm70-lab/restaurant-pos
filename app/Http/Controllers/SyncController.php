<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\InventoryTransaction;
use App\Models\SupplierBankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    /**
     * Pull delta changes from the server.
     * GET /api/sync/pull
     */
    public function pull(Request $request)
    {
        $lastSyncStr = $request->query('last_sync');
        $forceReset = false;
        
        if ($lastSyncStr) {
            try {
                $lastSync = Carbon::parse($lastSyncStr);
                // Force sync if the last_sync timestamp is older than 15 days
                if ($lastSync->diffInDays(Carbon::now()) > 15) {
                    $forceReset = true;
                }
            } catch (\Exception $e) {
                $lastSync = Carbon::createFromTimestamp(0);
                $forceReset = true;
            }
        } else {
            // Null or empty last_sync triggers full initial pull
            $lastSync = Carbon::createFromTimestamp(0);
        }

        // If force reset is active, ignore last_sync and pull all records
        $queryTime = $forceReset ? Carbon::createFromTimestamp(0) : $lastSync;

        return response()->json([
            'force_reset' => $forceReset,
            'server_time' => Carbon::now()->toIso8601String(),
            'data' => [
                'locations' => Location::withTrashed()->where('updated_at', '>', $queryTime)->get(),
                'products' => Product::withTrashed()->where('updated_at', '>', $queryTime)->get(),
                'ingredients' => Ingredient::withTrashed()->where('updated_at', '>', $queryTime)->get(),
                'supplier_bank_accounts' => SupplierBankAccount::withTrashed()->where('updated_at', '>', $queryTime)->get(),
                'orders' => Order::withTrashed()->where('updated_at', '>', $queryTime)->get(),
                'inventory_transactions' => InventoryTransaction::where('updated_at', '>', $queryTime)->get(),
            ]
        ]);
    }

    /**
     * Push locally created records to the server.
     * POST /api/sync/push
     */
    public function push(Request $request)
    {
        $orders = $request->input('orders', []);
        $orderItems = $request->input('order_items', []);
        $payments = $request->input('payments', []);
        $inventoryTransactions = $request->input('inventory_transactions', []);

        $syncedOrderIds = [];
        $syncedTransactionIds = [];

        // Wrap operations in a database transaction to prevent N+1 or partial sync states
        DB::beginTransaction();
        try {
            // 1. Reconcile Orders
            foreach ($orders as $orderData) {
                $order = Order::withTrashed()->find($orderData['id']);
                if (!$order) {
                    $order = new Order();
                    $order->id = $orderData['id'];
                }
                $order->forceFill([
                    'location_id' => $orderData['location_id'],
                    'status' => $orderData['status'],
                    'payment_status' => $orderData['payment_status'],
                    'total_amount' => $orderData['total_amount'],
                    'discount' => $orderData['discount'],
                    'tax' => $orderData['tax'],
                    'notes' => $orderData['notes'] ?? null,
                    'invoice_number' => $orderData['invoice_number'] ?? null,
                    'sync_status' => 'synced', // mark as synced on the server
                    'created_at' => $orderData['created_at'] ?? Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])->save();
                
                $syncedOrderIds[] = $orderData['id'];
            }

            // 2. Reconcile Order Items
            foreach ($orderItems as $itemData) {
                $item = OrderItem::find($itemData['id']);
                if (!$item) {
                    $item = new OrderItem();
                    $item->id = $itemData['id'];
                }
                $item->forceFill([
                    'order_id' => $itemData['order_id'],
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'created_at' => $itemData['created_at'] ?? Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])->save();
            }

            // 3. Reconcile Payments
            foreach ($payments as $paymentData) {
                $payment = Payment::find($paymentData['id']);
                if (!$payment) {
                    $payment = new Payment();
                    $payment->id = $paymentData['id'];
                }
                $payment->forceFill([
                    'order_id' => $paymentData['order_id'],
                    'amount' => $paymentData['amount'],
                    'payment_method' => $paymentData['payment_method'],
                    'transaction_id' => $paymentData['transaction_id'] ?? null,
                    'status' => $paymentData['status'],
                    'created_at' => $paymentData['created_at'] ?? Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])->save();
            }

            // 4. Reconcile Inventory Transactions
            foreach ($inventoryTransactions as $txData) {
                $tx = InventoryTransaction::find($txData['id']);
                if (!$tx) {
                    $tx = new InventoryTransaction();
                    $tx->id = $txData['id'];
                }
                $tx->forceFill([
                    'product_id' => $txData['product_id'],
                    'location_id' => $txData['location_id'],
                    'quantity' => $txData['quantity'],
                    'unit_cost' => $txData['unit_cost'],
                    'source_id' => $txData['source_id'] ?? null,
                    'order_id' => $txData['order_id'] ?? null,
                    'type' => $txData['type'] ?? ($txData['quantity'] >= 0 ? 'restock' : 'sale'),
                    'created_at' => $txData['created_at'] ?? Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])->save();
                
                $syncedTransactionIds[] = $txData['id'];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'synced_orders' => $syncedOrderIds,
                'synced_transactions' => $syncedTransactionIds,
                'server_time' => Carbon::now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sync push failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
