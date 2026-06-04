<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدينة POS - سجل المبيعات والطلبات</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
            background-image: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.03) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(241, 245, 249, 1) 0%, transparent 40%);
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
            @page {
                size: auto;
                margin: 0mm;
            }
        }
    </style>
</head>
<body class="min-h-screen flex" x-data="ordersHistoryApp()">

    <!-- Unified left navigation sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace -->
    <main class="flex-grow p-4 lg:p-8 space-y-6 lg:space-y-8" dir="rtl">
        
        <!-- Header Bar -->
        <div class="flex items-center justify-between border-b border-slate-200 pb-5 text-right">
            <div>
                <h1 class="text-xl font-black text-slate-800">سجل المبيعات والطلبات</h1>
                <span class="text-xs text-slate-400 font-medium">متابعة فواتير وحالة الطلبات، الدفع، وإعادة طباعة الإيصالات</span>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-bold uppercase">عنوان IP للطابعة:</span>
                <input type="text" x-model="printerIp" placeholder="192.168.1.100" class="w-32 bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-xs rounded-xl px-3 py-2 text-slate-800 focus:outline-none transition-all duration-300 text-center" dir="ltr" />
            </div>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Completed Revenue -->
            <div class="bg-gradient-to-br from-amber-500/10 to-orange-500/10 border border-amber-500/20 rounded-3xl p-6 flex items-center justify-between shadow-sm relative overflow-hidden text-right">
                <div>
                    <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">إجمالي الإيرادات المكتملة</span>
                    <h3 class="text-2xl font-black text-amber-600 mt-1" dir="ltr">
                        {{ number_format($orders->where('status', 'completed')->sum('total_amount'), 2) }} <span class="text-xs font-bold">د.ل</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-2xl">
                    💰
                </div>
            </div>

            <!-- Completed Orders Count -->
            <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/10 border border-emerald-500/20 rounded-3xl p-6 flex items-center justify-between shadow-sm relative overflow-hidden text-right">
                <div>
                    <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">الطلبات المكتملة (المسلمة)</span>
                    <h3 class="text-2xl font-black text-emerald-650 mt-1">
                        {{ $orders->where('status', 'completed')->count() }} <span class="text-xs font-bold text-slate-400">فاتورة</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-2xl">
                    ✅
                </div>
            </div>

            <!-- Active Orders Queue -->
            <div class="bg-gradient-to-br from-blue-500/10 to-indigo-500/10 border border-blue-500/20 rounded-3xl p-6 flex items-center justify-between shadow-sm relative overflow-hidden text-right">
                <div>
                    <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">الطلبات النشطة (تحت التحضير)</span>
                    <h3 class="text-2xl font-black text-blue-600 mt-1">
                        {{ $orders->whereIn('status', ['pending', 'cooking', 'ready'])->count() }} <span class="text-xs font-bold text-slate-400">طلب نشط</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-2xl">
                    ⏳
                </div>
            </div>
        </div>

        <!-- Orders Table Grid -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
                <h2 class="text-sm font-bold text-slate-800">سجل الفواتير والمعاملات المالية</h2>
                <!-- Total active counter -->
                <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1 rounded-lg">إجمالي الطلبات: {{ count($orders) }}</span>
            </div>

            <!-- Status filter tabs -->
            <div class="flex flex-wrap gap-2 pb-2">
                <button @click="currentFilter = 'all'" 
                        :class="currentFilter === 'all' ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/10 border-amber-500' : 'bg-slate-50 hover:bg-slate-100 text-slate-650 font-medium border-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition-all duration-300 border">
                    الكل ({{ count($orders) }})
                </button>
                <button @click="currentFilter = 'pending'" 
                        :class="currentFilter === 'pending' ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/10 border-amber-500' : 'bg-slate-50 hover:bg-slate-100 text-slate-650 font-medium border-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition-all duration-300 border">
                    معلقة ({{ $orders->where('status', 'pending')->count() }})
                </button>
                <button @click="currentFilter = 'cooking'" 
                        :class="currentFilter === 'cooking' ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/10 border-amber-500' : 'bg-slate-50 hover:bg-slate-100 text-slate-650 font-medium border-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition-all duration-300 border">
                    قيد التحضير ({{ $orders->where('status', 'cooking')->count() }})
                </button>
                <button @click="currentFilter = 'ready'" 
                        :class="currentFilter === 'ready' ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/10 border-amber-500' : 'bg-slate-50 hover:bg-slate-100 text-slate-650 font-medium border-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition-all duration-300 border">
                    جاهزة للتسليم ({{ $orders->where('status', 'ready')->count() }})
                </button>
                <button @click="currentFilter = 'completed'" 
                        :class="currentFilter === 'completed' ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/10 border-amber-500' : 'bg-slate-50 hover:bg-slate-100 text-slate-650 font-medium border-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition-all duration-300 border">
                    مكتملة ({{ $orders->where('status', 'completed')->count() }})
                </button>
                <button @click="currentFilter = 'cancelled'" 
                        :class="currentFilter === 'cancelled' ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/10 border-amber-500' : 'bg-slate-50 hover:bg-slate-100 text-slate-650 font-medium border-slate-200'"
                        class="px-4 py-2 text-xs rounded-xl transition-all duration-300 border">
                    ملغية ({{ $orders->where('status', 'cancelled')->count() }})
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-right">رمز الطلب</th>
                            <th class="px-6 py-4 text-right">الفرع</th>
                            <th class="px-6 py-4 text-right">حالة الوجبة</th>
                            <th class="px-6 py-4 text-right">حالة الدفع</th>
                            <th class="px-6 py-4 text-right">ملاحظات</th>
                            <th class="px-6 py-4 text-left">عدد الأصناف</th>
                            <th class="px-6 py-4 text-left">إجمالي الفاتورة</th>
                            <th class="px-6 py-4 text-right">تاريخ الطلب</th>
                            <th class="px-6 py-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($orders as $order)
                            <tr class="hover:bg-slate-50/50 transition-colors"
                                x-show="currentFilter === 'all' || '{{ $order->status }}' === currentFilter">
                                <td class="px-6 py-4 font-mono font-bold text-slate-800">
                                    #{{ strtoupper(substr($order->id, 0, 8)) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">{{ $order->location->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-[9px] px-2.5 py-0.5 rounded font-black uppercase border
                                        @if($order->status === 'completed') bg-emerald-50 text-emerald-700 border-emerald-250
                                        @elseif($order->status === 'cooking') bg-amber-50 text-amber-700 border-amber-250
                                        @elseif($order->status === 'ready') bg-blue-50 text-blue-700 border-blue-250
                                        @else bg-rose-50 text-rose-700 border-rose-250 @endif">
                                        @if($order->status === 'completed') مكتمل
                                        @elseif($order->status === 'cooking') قيد التحضير
                                        @elseif($order->status === 'ready') جاهز للتسليم
                                        @elseif($order->status === 'pending') معلق
                                        @else ملغي @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[9px] px-2.5 py-0.5 rounded font-black uppercase border
                                        @if($order->payment_status === 'paid') bg-emerald-50 text-emerald-700 border-emerald-250
                                        @else bg-slate-100 text-slate-400 border-slate-200 @endif">
                                        {{ $order->payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-[150px] truncate" title="{{ $order->notes }}">
                                    {{ $order->notes ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-left font-bold text-slate-800">
                                    {{ $order->items->sum('quantity') }}
                                </td>
                                <td class="px-6 py-4 text-left font-extrabold text-amber-600" dir="ltr">
                                    {{ number_format($order->total_amount, 2) }} LYD
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 font-mono">
                                    {{ $order->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="reprintReceipt('{{ $order->id }}')"
                                                 class="bg-slate-50 hover:bg-amber-500 hover:text-slate-950 text-[10px] font-bold px-2 py-1.5 rounded-lg border border-slate-200 hover:border-amber-500 transition-all shadow-sm">
                                             🖨️ طباعة IP
                                         </button>
                                         <button @click="printBrowser('{{ $order->id }}')"
                                                  class="bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold px-2 py-1.5 rounded-lg border border-blue-200 hover:border-blue-600 transition-all shadow-sm">
                                             📄 طباعة المتصفح
                                         </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-slate-400">لا يوجد حركات أو فواتير مبيعات مسجلة بالدفاتر حالياً.</td>
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
                    <p class="text-[10px] text-gray-500 font-bold" x-text="selectedOrder.location ? selectedOrder.location.name : 'فرع طرابلس'"></p>
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
                    <p class="text-[8px] text-gray-550 font-mono" x-text="selectedOrder.id"></p>
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
