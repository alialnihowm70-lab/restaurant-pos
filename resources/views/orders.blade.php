<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدينة POS - سجل المبيعات والطلبات</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;650;750;850;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Compiled Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="manifest" href="/manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered successfully!', reg))
                    .catch(err => console.log('Service Worker registration failed: ', err));
            });
        }
    </script>
    <style>
        body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            background-image: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.04) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(99, 102, 241, 0.04) 0%, transparent 40%);
        }
        /* Paper receipt styling for high-end preview */
        .paper-receipt {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            font-family: monospace;
            position: relative;
            border: 1px solid #e2e8f0;
        }
        .paper-receipt::before {
            content: "";
            position: absolute;
            top: -8px;
            left: 0;
            right: 0;
            height: 8px;
            background-size: 16px 8px;
            background-repeat: repeat-x;
            background-image: linear-gradient(45deg, transparent 33.333%, #ffffff 33.333%, #ffffff 66.667%, transparent 66.667%),
                              linear-gradient(-45deg, transparent 33.333%, #ffffff 33.333%, #ffffff 66.667%, transparent 66.667%);
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-receipt-card, #printable-receipt-card * {
                visibility: visible;
            }
            #printable-receipt-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 80mm;
                margin: 0;
                padding: 10px;
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                border: none !important;
            }
            #printable-receipt-card::before {
                display: none !important;
            }
            @page {
                size: auto;
                margin: 0mm;
            }
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-animate {
            animation: pageFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="min-h-screen flex relative overflow-x-hidden page-animate" x-data="ordersHistoryApp()">

    <!-- Decorative Glow Circles -->
    <div class="absolute top-10 right-10 w-[500px] h-[500px] bg-amber-500/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-10 left-10 w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- Unified left navigation sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace -->
    <main class="flex-grow p-6 lg:p-8 space-y-6 relative z-10" dir="rtl">
        
        <!-- Header Bar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between border-b border-slate-200/80 pb-5 gap-4 text-right">
            <div>
                <h1 class="text-base font-black text-slate-900">سجل الفواتير والمبيعات اليومية</h1>
                <span class="text-[10px] text-slate-400 font-extrabold mt-1 block">استعراض أرشيف الفواتير المصدرة، إعادة طباعة الإيصالات الورقية، ومراقبة حالة المطبخ</span>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-bold">طابعة IP:</span>
                <input type="text" x-model="printerIp" placeholder="192.168.1.100" class="w-36 bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-xs rounded-2xl px-4 py-2.5 text-slate-800 text-center font-mono focus:outline-none shadow-sm transition-all" dir="ltr" />
            </div>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Completed Revenue -->
            <div class="bg-gradient-to-br from-amber-500/10 to-orange-550/10 border border-amber-500/20 rounded-[28px] p-6 flex items-center justify-between shadow-sm relative overflow-hidden text-right">
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-500 tracking-wider">إجمالي الإيرادات المكتملة</span>
                    <h3 class="text-2xl font-black text-amber-650 mt-1.5" dir="ltr">
                        {{ number_format($orders->where('status', 'completed')->sum('total_amount'), 2) }} <span class="text-xs font-black">د.ل</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-[20px] bg-amber-550/10 border border-amber-500/20 flex items-center justify-center text-2xl shadow-inner">
                    💰
                </div>
            </div>

            <!-- Completed Orders Count -->
            <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/10 border border-emerald-500/20 rounded-[28px] p-6 flex items-center justify-between shadow-sm relative overflow-hidden text-right">
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-500 tracking-wider">الفواتير المكتملة المسلمة</span>
                    <h3 class="text-2xl font-black text-emerald-650 mt-1.5">
                        {{ $orders->where('status', 'completed')->count() }} <span class="text-xs font-bold text-slate-400">فاتورة</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-[20px] bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-2xl shadow-inner">
                    ✅
                </div>
            </div>

            <!-- Active Orders Queue -->
            <div class="bg-gradient-to-br from-blue-500/10 to-indigo-550/10 border border-blue-500/20 rounded-[28px] p-6 flex items-center justify-between shadow-sm relative overflow-hidden text-right">
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-500 tracking-wider">الطلبات النشطة في المطبخ</span>
                    <h3 class="text-2xl font-black text-blue-650 mt-1.5">
                        {{ $orders->whereIn('status', ['pending', 'cooking', 'ready'])->count() }} <span class="text-xs font-bold text-slate-400">طلب نشط</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-[20px] bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-2xl shadow-inner">
                    ⏳
                </div>
            </div>
        </div>

        <!-- Orders Table Grid -->
        <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-150 pb-3 flex-wrap gap-2">
                <h2 class="text-sm font-black text-slate-800">أرشيف الفواتير والمعاملات</h2>
                <span class="text-xs font-black text-amber-700 bg-amber-50 border border-amber-200/50 px-3.5 py-1.5 rounded-xl shadow-sm">إجمالي الفواتير: {{ count($orders) }}</span>
            </div>

            <!-- Status filter tabs -->
            <div class="bg-slate-100 border border-slate-205 p-1.5 rounded-2xl flex flex-wrap gap-1" dir="rtl">
                <button @click="currentFilter = 'all'" 
                        :class="currentFilter === 'all' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 font-black shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">
                    الكل ({{ count($orders) }})
                </button>
                <button @click="currentFilter = 'pending'" 
                        :class="currentFilter === 'pending' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 font-black shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">
                    معلقة ({{ $orders->where('status', 'pending')->count() }})
                </button>
                <button @click="currentFilter = 'cooking'" 
                        :class="currentFilter === 'cooking' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 font-black shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">
                    قيد التحضير ({{ $orders->where('status', 'cooking')->count() }})
                </button>
                <button @click="currentFilter = 'ready'" 
                        :class="currentFilter === 'ready' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 font-black shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">
                    جاهزة للتسليم ({{ $orders->where('status', 'ready')->count() }})
                </button>
                <button @click="currentFilter = 'completed'" 
                        :class="currentFilter === 'completed' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 font-black shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">
                    مكتملة ({{ $orders->where('status', 'completed')->count() }})
                </button>
                <button @click="currentFilter = 'cancelled'" 
                        :class="currentFilter === 'cancelled' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 font-black shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">
                    ملغية ({{ $orders->where('status', 'cancelled')->count() }})
                </button>
            </div>

            <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                    <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4.5 text-right">رقم الفاتورة</th>
                            <th class="px-6 py-4.5 text-right">الفرع</th>
                            <th class="px-6 py-4.5 text-right">حالة التحضير</th>
                            <th class="px-6 py-4.5 text-right">حالة السداد</th>
                            <th class="px-6 py-4.5 text-right">ملاحظات التحضير</th>
                            <th class="px-6 py-4.5 text-left">عدد الوجبات</th>
                            <th class="px-6 py-4.5 text-left">إجمالي القيمة</th>
                            <th class="px-6 py-4.5 text-right">تاريخ المعاملة</th>
                            <th class="px-6 py-4.5 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($orders as $order)
                            <tr class="hover:bg-slate-50/50 transition-colors"
                                x-show="currentFilter === 'all' || '{{ $order->status }}' === currentFilter">
                                <td class="px-6 py-4 font-mono font-bold text-slate-800 text-[11px]">
                                    #{{ strtoupper(substr($order->id, 0, 8)) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 font-extrabold">{{ $order->location->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-[8px] px-2.5 py-0.5 rounded-md font-black uppercase border
                                        @if($order->status === 'completed') bg-emerald-500/10 text-emerald-600 border-emerald-500/20
                                        @elseif($order->status === 'cooking') bg-amber-500/10 text-amber-600 border-amber-500/20
                                        @elseif($order->status === 'ready') bg-blue-500/10 text-blue-600 border-blue-500/20
                                        @else bg-rose-500/10 text-rose-600 border-rose-500/20 @endif">
                                        @if($order->status === 'completed') مكتمل
                                        @elseif($order->status === 'cooking') قيد التحضير
                                        @elseif($order->status === 'ready') جاهز للتسليم
                                        @elseif($order->status === 'pending') معلق
                                        @else ملغي @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[8px] px-2.5 py-0.5 rounded-md font-black uppercase border
                                        @if($order->payment_status === 'paid') bg-emerald-500/10 text-emerald-600 border-emerald-500/20
                                        @else bg-slate-100 text-slate-400 border-slate-200 @endif">
                                        {{ $order->payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-[150px] truncate" title="{{ $order->notes }}">
                                    {{ $order->notes ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-left font-extrabold text-slate-800">
                                    {{ $order->items->sum('quantity') }}
                                </td>
                                <td class="px-6 py-4 text-left font-black text-amber-600" dir="ltr">
                                    {{ number_format($order->total_amount, 2) }} د.ل
                                </td>
                                <td class="px-6 py-4 text-[10px] text-slate-400 font-bold font-mono">
                                    {{ $order->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="reprintReceipt('{{ $order->id }}')"
                                                 class="bg-slate-50 hover:bg-amber-500 hover:text-slate-950 text-[9px] font-black px-3 py-2 rounded-xl border border-slate-250 hover:border-amber-500 transition-all shadow-sm">
                                             🖨️ طباعة IP
                                         </button>
                                         <button @click="printBrowser('{{ $order->id }}')"
                                                  class="bg-blue-500/10 hover:bg-blue-650 text-blue-600 hover:text-white text-[9px] font-black px-3 py-2 rounded-xl border border-blue-500/20 hover:border-blue-600 transition-all shadow-sm">
                                             📄 طباعة المتصفح
                                         </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-450 font-bold">لا يوجد فواتير مبيعات مسجلة في المنظومة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Hidden print ticket for browser printing (reprint) -->
    <div id="printable-receipt-card" class="hidden print:block bg-white text-gray-900 p-6 space-y-4 text-right w-[80mm]" style="font-family: monospace;" dir="rtl">
        <template x-if="selectedOrder">
            <div>
                <div class="text-center space-y-1">
                    <h2 class="text-base font-extrabold tracking-tight">مطعم المدينة المنورة</h2>
                    <p class="text-[10px] text-gray-550 font-bold" x-text="selectedOrder.location ? selectedOrder.location.name : 'فرع طرابلس'"></p>
                    <p class="text-[9px] text-gray-400">الهاتف: 091-0000000</p>
                </div>

                <div class="border-t border-dashed border-gray-400 my-2"></div>

                <div class="text-[10px] space-y-1">
                    <div class="flex justify-between">
                        <span>رقم الفاتورة:</span>
                        <span class="font-bold" x-text="selectedOrder.id.substring(0, 8).toUpperCase()"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>تاريخ العملية:</span>
                        <span x-text="new Date(selectedOrder.created_at).toLocaleString('ar-LY')"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>طريقة الدفع:</span>
                        <span class="font-bold" x-text="selectedOrder.payments && selectedOrder.payments.length ? translatePaymentMethod(selectedOrder.payments[0].payment_method) : 'نقداً (كاش)'"></span>
                    </div>
                </div>

                <template x-if="selectedOrder.notes">
                    <div class="bg-gray-100 border border-gray-300 p-2 rounded-xl text-[9px] font-sans font-bold flex flex-col gap-0.5 mt-2 text-right">
                        <span class="text-[8px] text-gray-600 uppercase">ملاحظات التحضير بالمطبخ:</span>
                        <span x-text="selectedOrder.notes"></span>
                    </div>
                </template>

                <div class="border-t border-dashed border-gray-400 my-2"></div>

                <div class="text-[10px] space-y-1">
                    <div class="flex justify-between font-bold text-gray-700 mb-1">
                        <span class="w-1/2 text-right">الصنف الوجبة</span>
                        <span class="w-1/4 text-center">الكمية</span>
                        <span class="w-1/4 text-left">السعر</span>
                    </div>
                    <template x-for="item in selectedOrder.items" :key="item.id">
                        <div class="flex justify-between">
                            <span class="w-1/2 text-right truncate" x-text="item.product ? item.product.name : 'صنف وجبة'"></span>
                            <span class="w-1/4 text-center" x-text="item.quantity"></span>
                            <span class="w-1/4 text-left" x-text="parseFloat(item.price * item.quantity).toFixed(2) + ' د.ل'"></span>
                        </div>
                    </template>
                </div>

                <div class="border-t border-dashed border-gray-400 my-2"></div>

                <div class="text-[10px] space-y-1">
                    <div class="flex justify-between">
                        <span>المجموع الفرعي:</span>
                        <span x-text="parseFloat(selectedOrder.total_amount - selectedOrder.tax + selectedOrder.discount).toFixed(2) + ' د.ل'"></span>
                    </div>
                    <template x-if="selectedOrder.discount > 0">
                        <div class="flex justify-between">
                            <span>الخصم الممنوح:</span>
                            <span x-text="'-' + parseFloat(selectedOrder.discount).toFixed(2) + ' د.ل'"></span>
                        </div>
                    </template>
                    <div class="flex justify-between">
                        <span>الضريبة المضافة:</span>
                        <span x-text="parseFloat(selectedOrder.tax).toFixed(2) + ' د.ل'"></span>
                    </div>
                    <div class="flex justify-between font-bold text-sm pt-1.5 border-t border-gray-300">
                        <span>إجمالي الفاتورة (LYD):</span>
                        <span x-text="parseFloat(selectedOrder.total_amount).toFixed(2) + ' د.ل'"></span>
                    </div>
                </div>

                <div class="border-t border-dashed border-gray-400 my-2"></div>

                <div class="text-center space-y-2">
                    <p class="text-[10px] font-bold">شكراً لزيارتكم • صحتين وعافية!</p>
                    <div class="flex justify-center items-center gap-0.5 h-8 w-44 mx-auto bg-gray-100 p-1 rounded">
                        <div class="w-1 bg-gray-900 h-full"></div>
                        <div class="w-0.5 bg-gray-900 h-full"></div>
                        <div class="w-1.5 bg-gray-900 h-full"></div>
                        <div class="w-0.5 bg-gray-900 h-full"></div>
                        <div class="w-1 bg-gray-900 h-full"></div>
                        <div class="w-2 bg-gray-900 h-full"></div>
                        <div class="w-0.5 bg-gray-900 h-full"></div>
                        <div class="w-1 bg-gray-900 h-full"></div>
                        <div class="w-1.5 bg-gray-900 h-full"></div>
                        <div class="w-0.5 bg-gray-900 h-full"></div>
                    </div>
                    <p class="text-[8px] text-gray-555 font-mono" x-text="selectedOrder.id"></p>
                </div>
            </div>
        </template>
    </div>

    <script>
        function ordersHistoryApp() {
            return {
                currentFilter: 'all',
                printerIp: localStorage.getItem('printerIp') || '',
                selectedOrder: null,
                orders: @json($orders->load('items.product', 'location')),
                
                init() {
                    this.$watch('printerIp', val => localStorage.setItem('printerIp', val));
                },

                translatePaymentMethod(method) {
                    const dict = {
                        'cash': 'نقداً (كاش)',
                        'sadad': 'سداد (Sadad)',
                        'mobicash': 'موبي كاش',
                        'tadawul': 'تداول (Tadawul)'
                    };
                    return dict[method.toLowerCase()] || method;
                },

                printBrowser(orderId) {
                    const order = this.orders.find(o => o.id === orderId);
                    if (!order) return;
                    this.selectedOrder = order;
                    this.$nextTick(() => {
                        window.print();
                    });
                },

                reprintReceipt(orderId) {
                    if (!this.printerIp) {
                        alert("يرجى تهيئة وإدخال عنوان IP الخاص بالطابعة الشبكية في الشريط العلوي أولاً!");
                        return;
                    }
                    
                    fetch(`/pos/orders/${orderId}/print`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ ip: this.printerIp })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("تم إرسال أمر الطباعة بنجاح للطابعة الشبكية!");
                        } else {
                            alert("فشل الاتصال بالطابعة الشبكية. يرجى التحقق من اتصالها بالشبكة.");
                        }
                    });
                }
            };
        }
    </script>
</body>
</html>
