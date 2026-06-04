<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    private $sadadSecret = 'sadad_secret_key_123456_libya';
    private $sadadMerchantId = 'MERCHANT_MADINA_001';

    /**
     * Sadad Wallet: Initialize Checkout Session
     * POST /api/payments/sadad/checkout
     */
    public function sadadCheckout(Request $request)
    {
        $request->validate([
            'order_id' => 'required|uuid',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $orderId = $request->input('order_id');
        $amount = $request->input('amount');
        $timestamp = Carbon::now()->timestamp;

        // Generate SHA-256 signature for security
        $payload = "merchant_id={$this->sadadMerchantId}&order_id={$orderId}&amount={$amount}&timestamp={$timestamp}";
        $signature = hash_hmac('sha256', $payload, $this->sadadSecret);

        // Generate mock checkout URL
        $checkoutUrl = "https://checkout.sadad.ly/pay?session_id=" . bin2hex(random_bytes(16));

        // Create pending payment record
        Payment::updateOrCreate(
            ['order_id' => $orderId, 'payment_method' => 'sadad', 'status' => 'pending'],
            [
                'amount' => $amount,
                'transaction_id' => 'SADAD_' . Str::random(10),
            ]
        );

        return response()->json([
            'success' => true,
            'checkout_url' => $checkoutUrl,
            'signature' => $signature,
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Sadad Wallet: secure webhook instant payment notification
     * POST /api/payments/sadad/webhook
     */
    public function sadadWebhook(Request $request)
    {
        $rawPayload = $request->getContent();
        $receivedSignature = $request->header('X-Sadad-Signature');

        if (!$receivedSignature) {
            return response()->json(['success' => false, 'error' => 'Missing signature header'], 400);
        }

        // Verify SHA-256 signature
        $expectedSignature = hash_hmac('sha256', $rawPayload, $this->sadadSecret);
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Sadad Webhook: Signature verification failed.');
            return response()->json(['success' => false, 'error' => 'Signature mismatch'], 403);
        }

        $data = json_decode($rawPayload, true);
        $orderId = $data['order_id'] ?? null;
        $status = $data['status'] ?? null; // e.g., 'SUCCESS', 'FAILED'
        $amount = $data['amount'] ?? 0;
        $txnId = $data['transaction_id'] ?? null;

        if ($orderId && $status === 'SUCCESS') {
            $order = Order::find($orderId);
            if ($order) {
                // Update Order & Payment
                $order->update(['payment_status' => 'paid', 'status' => 'cooking']);
                
                Payment::where('order_id', $orderId)
                    ->where('payment_method', 'sadad')
                    ->update([
                        'status' => 'completed',
                        'transaction_id' => $txnId
                    ]);
                
                Log::info("Sadad Webhook: Order {$orderId} successfully completed payment.");
                return response()->json(['success' => true, 'message' => 'Payment received']);
            }
        }

        return response()->json(['success' => false, 'error' => 'Payment processing failed'], 400);
    }

    /**
     * MobiCash Wallet: Generate QR Payload
     * POST /api/payments/mobicash/qr
     */
    public function mobicashQr(Request $request)
    {
        $request->validate([
            'order_id' => 'required|uuid',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $orderId = $request->input('order_id');
        $amount = $request->input('amount');
        $mobicashMerchantId = 'MOBICASH_MADINA_77';
        
        // Format of payload for QR: MerchantID|OrderID|Amount|Timestamp
        $qrPayload = "{$mobicashMerchantId}|{$orderId}|{$amount}|" . Carbon::now()->timestamp;

        // Save pending payment record
        $payment = Payment::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_method' => 'mobicash',
            'transaction_id' => 'MOBI_' . Str::random(8),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'qr_payload' => $qrPayload,
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * MobiCash Wallet: Poll status
     * GET /api/payments/mobicash/status/{payment_id}
     */
    public function mobicashStatus($paymentId)
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        // Mock polling tap - Auto-approves payment for responsive UX demo
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'completed']);
            $order = Order::find($payment->order_id);
            if ($order) {
                $order->update(['payment_status' => 'paid', 'status' => 'cooking']);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $payment->status,
        ]);
    }

    /**
     * Tadawul POS Card Reader: Trigger transaction
     * POST /api/payments/tadawul/transact
     */
    public function tadawulTransact(Request $request)
    {
        $request->validate([
            'order_id' => 'required|uuid',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $orderId = $request->input('order_id');
        $amount = $request->input('amount');

        $payment = Payment::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_method' => 'tadawul',
            'transaction_id' => 'TAD_' . Str::random(8),
            'status' => 'pending',
        ]);

        // In production, open socket/http to POS terminal hardware
        // Example: Http::timeout(3)->post("http://192.168.1.200:8888/transact", ['amount' => $amount]);

        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'message' => 'Insert or tap bank card on POS reader terminal...',
        ]);
    }

    /**
     * Tadawul POS Card Reader: Poll status
     * GET /api/payments/tadawul/status/{payment_id}
     */
    public function tadawulStatus($paymentId)
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        // Auto-approve card transaction upon polling check for UI demonstration
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'completed']);
            $order = Order::find($payment->order_id);
            if ($order) {
                $order->update(['payment_status' => 'paid', 'status' => 'cooking']);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $payment->status,
        ]);
    }
}
