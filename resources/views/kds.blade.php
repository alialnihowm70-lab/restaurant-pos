<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدينة KDS - شاشة عرض المطبخ</title>
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
        /* Grid height adjustment */
        .kds-grid {
            height: calc(100vh - 80px);
        }
    </style>
</head>
<body class="h-screen overflow-hidden flex" x-data="kdsApp()">

    <!-- Unified left navigation sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-grow flex flex-col overflow-hidden h-screen">

        <!-- Top Header -->
        <header class="bg-white border-b border-slate-200 px-4 lg:px-6 py-3 lg:py-4 flex items-center justify-between flex-shrink-0 text-right">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🍳</span>
                <div>
                    <h1 class="text-lg font-bold leading-none text-slate-800">شاشة عرض المطبخ (KDS)</h1>
                    <span class="text-xs text-slate-400 font-medium">طابور التحضير والطهي المباشر للطلبات</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Active order counter -->
                <div class="text-xs bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg text-slate-700">
                    قيد التحضير: <span class="font-bold text-amber-600" x-text="orders.length"></span>
                </div>
                
                <button @click="window.location.reload()" class="bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-semibold px-4 py-2 rounded-lg text-slate-700 transition-colors">
                    تحديث الشاشة
                </button>
                <a href="/pos" class="bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold px-4 py-2 rounded-lg transition-colors shadow shadow-amber-500/10">
                    كاشير الصالة (POS)
                </a>
            </div>
        </header>

        <!-- KDS Card Grid Area -->
        <main class="flex-grow p-4 lg:p-6 overflow-y-auto" dir="rtl">
            <template x-if="orders.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-slate-400 gap-4 py-20">
                    <span class="text-6xl animate-bounce">👍</span>
                    <h2 class="text-lg font-bold">تم الانتهاء من جميع الطلبات! لا يوجد وجبات معلقة.</h2>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <template x-for="order in orders" :key="order.id">
                    <div x-data="{ checkedItems: [] }"
                         class="bg-white border rounded-3xl flex flex-col justify-between overflow-hidden shadow-sm transition-all duration-300 hover:scale-[1.01] hover:shadow-md"
                         :class="getBorderClass(order)">
                        
                        <!-- Top time indicator strip -->
                        <div class="h-1.5 w-full" :class="getStripClass(order)"></div>

                        <!-- Card Header -->
                        <div class="p-4 border-b flex justify-between items-start text-right" :class="getBorderClass(order)">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-extrabold text-slate-800" x-text="'#' + order.id.substring(0, 8).toUpperCase()"></span>
                                    <span class="text-[9px] px-2 py-0.5 rounded-lg font-black uppercase tracking-wider border"
                                          :class="order.status === 'cooking' ? 'bg-amber-50 text-amber-700 border-amber-200' : (order.status === 'ready' ? 'bg-emerald-50 text-emerald-750 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200')"
                                          x-text="order.status === 'cooking' ? 'قيد الطهي' : (order.status === 'ready' ? 'جاهز للتسليم' : 'قيد الانتظار')"></span>
                                </div>
                                <span class="text-[10px] text-slate-400 font-bold block mt-1" x-text="'الفرع: ' + order.location.name"></span>
                            </div>
                            
                            <!-- Cooking Timer Display -->
                            <div class="text-left" dir="ltr">
                                <span class="font-mono font-black text-sm block tracking-wider" :class="getElapsedSeconds(order) > 600 ? 'text-red-600' : (getElapsedSeconds(order) > 300 ? 'text-amber-600' : 'text-emerald-600')" x-text="getElapsedTime(order)"></span>
                                <span class="text-[8px] text-slate-400 uppercase tracking-widest font-bold block text-left">الوقت المنقضي</span>
                            </div>
                        </div>

                        <!-- Card Body: Interactive Item Checklist -->
                        <div class="flex-grow p-4 bg-slate-50/50 space-y-2.5">
                            <template x-for="item in order.items" :key="item.id">
                                <div @click="checkedItems.includes(item.id) ? checkedItems = checkedItems.filter(id => id !== item.id) : checkedItems.push(item.id)"
                                     class="flex justify-between items-center text-xs cursor-pointer hover:bg-slate-100 p-2 rounded-xl border border-transparent hover:border-slate-200 transition-all group" dir="rtl">
                                    <div class="min-w-0 flex items-center gap-2.5">
                                        <span class="w-4.5 h-4.5 rounded-lg border flex items-center justify-center text-[9px] transition-all"
                                              :class="checkedItems.includes(item.id) ? 'bg-emerald-500 border-emerald-550 text-white font-black' : 'border-slate-350 bg-white group-hover:border-slate-400 text-transparent'">
                                            ✓
                                        </span>
                                        <span class="font-black text-amber-600" x-text="item.quantity + 'x'"></span>
                                        <span class="text-slate-700 font-semibold transition-all mr-1" :class="checkedItems.includes(item.id) ? 'line-through text-slate-400' : 'group-hover:text-amber-650'" x-text="item.product.name"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- Order Notes Display -->
                            <template x-if="order.notes">
                                <div class="mt-3 p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl text-xs space-y-1 font-semibold text-right">
                                    <div class="text-[9px] uppercase tracking-wider text-amber-700 font-extrabold flex items-center gap-1">
                                        <span>📝</span> ملاحظات خاصة بالتحضير
                                    </div>
                                    <div class="text-slate-800 mt-1 font-sans text-[11px]" x-text="order.notes"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="p-3 bg-slate-50 border-t flex gap-2" :class="getBorderClass(order)">
                            <!-- Transition pending to cooking -->
                            <button x-show="order.status === 'pending'" @click="updateStatus(order, 'cooking')"
                                    class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs py-3 rounded-xl transition-all shadow-md shadow-amber-500/10">
                                بدء الطهي / التحضير
                            </button>
                            <!-- Transition cooking to ready -->
                            <button x-show="order.status === 'cooking'" @click="updateStatus(order, 'ready')"
                                    class="w-full bg-emerald-650 hover:bg-emerald-700 text-white font-black text-xs py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10">
                                تجهيز الوجبة (جاهز)
                            </button>
                            <!-- Transition ready to completed -->
                            <button x-show="order.status === 'ready'" @click="updateStatus(order, 'completed')"
                                    class="w-full bg-blue-600 hover:bg-blue-750 text-white font-black text-xs py-3 rounded-xl transition-all shadow-md shadow-blue-500/10">
                                تسليم وإتمام الطلب
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </main>

        <!-- Script Block for KDS Polling and Timers -->
        <script>
            function kdsApp() {
                return {
                    orders: @json($orders),
                    currentTime: Date.now(),
                    
                    init() {
                        // Update current timestamp every second to drive reactive timers
                        setInterval(() => {
                            this.currentTime = Date.now();
                        }, 1000);

                        // Polling for new orders every 10 seconds
                        setInterval(() => {
                            this.pollNewOrders();
                        }, 10000);
                    },

                    pollNewOrders() {
                        fetch('/kds')
                            .then(res => res.text())
                            .then(html => {
                                // Simple parsing of new orders json embedded in page from backend
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const scriptEl = doc.querySelector('script');
                                if (scriptEl) {
                                    // Match JSON signature
                                    const match = html.match(/orders":\s*(\[.*?\]),/s);
                                    if (match && match[1]) {
                                        try {
                                            this.orders = JSON.parse(match[1]);
                                        } catch (e) {
                                            console.error("KDS refresh parsing failed", e);
                                        }
                                    }
                                }
                            });
                    },

                    getElapsedSeconds(order) {
                        const created = new Date(order.created_at).getTime();
                        return Math.max(0, Math.floor((this.currentTime - created) / 1000));
                    },

                    getElapsedTime(order) {
                        const secs = this.getElapsedSeconds(order);
                        const m = Math.floor(secs / 60);
                        const s = secs % 60;
                        return `${m} د ${s} ث`;
                    },

                    getStripClass(order) {
                        const secs = this.getElapsedSeconds(order);
                        if (secs < 300) {
                            return 'bg-gradient-to-r from-emerald-500 to-teal-500';
                        } else if (secs < 600) {
                            return 'bg-gradient-to-r from-amber-500 to-orange-500';
                        } else {
                            return 'bg-gradient-to-r from-red-500 to-rose-500 animate-pulse';
                        }
                    },

                    getBorderClass(order) {
                        const secs = this.getElapsedSeconds(order);
                        if (secs < 300) {
                            return 'border-slate-200';
                        } else if (secs < 600) {
                            return 'border-amber-500/30';
                        } else {
                            return 'border-red-500/40';
                        }
                    },

                    updateStatus(order, newStatus) {
                        fetch(`/kds/orders/${order.id}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                if (newStatus === 'completed' || newStatus === 'cancelled') {
                                    // Remove from list
                                    this.orders = this.orders.filter(o => o.id !== order.id);
                                } else {
                                    // Update status locally
                                    order.status = newStatus;
                                }
                            }
                        });
                    }
                };
            }
        </script>
    </div>
</body>
</html>
