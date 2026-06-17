<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerMenuController extends Controller
{
    public function menu()
    {
        $products = Product::with('ingredients')
            ->whereRaw('is_available IS TRUE')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $categories = $products->pluck('category')->unique()->filter()->values();

        return view('customer-menu', compact('products', 'categories'));
    }

    public function submitOrder(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'table_number'  => 'nullable|integer',
            'notes'         => 'nullable|string|max:1000',
            'order_type'    => 'required|in:table,takeaway',
            'items'         => 'required|array|min:1',
            'items.*.id'       => 'required',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $location = Location::first();
        if (!$location) {
            return response()->json(['success' => false, 'error' => 'No branch location configured'], 500);
        }

        $order = Order::create([
            'location_id'    => $location->id,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
            'total_amount'   => $data['total_amount'],
            'discount'       => 0,
            'tax'            => 0,
            'sync_status'    => 'pending',
            'source'         => 'web',
            'customer_name'  => $data['customer_name'],
            'order_type'     => $data['order_type'],
            'table_number'   => $data['table_number'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'invoice_number' => 'WEB-' . strtoupper(Str::random(8)),
        ]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $item['id'],
                'product_name' => $item['name'],
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'message' => 'تم استلام طلبك بنجاح!',
        ]);
    }

    public function pendingOrders()
    {
        $orders = Order::with('items')
            ->where('source', 'web')
            ->where('sync_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($o) {
                return [
                    'id' => (string) $o->id,
                    'customer_name' => $o->customer_name,
                    'table_number' => $o->table_number,
                    'order_type' => $o->order_type,
                    'notes' => $o->notes,
                    'total_amount' => (float) $o->total_amount,
                    'created_at' => $o->created_at,
                    'items' => $o->items->map(fn($i) => [
                        'product_id' => $i->product_id,
                        'product_name' => $i->product_name ?? $i->product?->name ?? 'Unknown',
                        'quantity' => $i->quantity,
                        'price' => (float) $i->price,
                    ]),
                ];
            });

        // Return plain array for desktop app polling
        return response()->json($orders);
    }

    public function claimOrder(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'status' => 'nullable|string|in:approved,rejected',
        ]);

        $order = Order::where('source', 'web')->find($data['id']);

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found'], 404);
        }

        $status = $data['status'] ?? 'approved';
        $order->update([
            'sync_status' => $status === 'rejected' ? 'rejected' : 'synced',
        ]);

        return response()->json(['success' => true]);
    }
}
