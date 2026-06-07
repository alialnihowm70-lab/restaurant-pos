<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>المدينة POS - {{ $isArchive ? 'أرشيف الفواتير' : 'سجل الفواتير اليومية' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="manifest" href="/manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
    <style>
        body { font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp .3s cubic-bezier(.16,1,.3,1) both; }

        /* Table row hover */
        .orders-row:hover { background: rgba(148,163,184,.04); }
        .dark .orders-row:hover { background: rgba(255,255,255,.03); }

        /* Status badges */
        .badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:900; padding:3px 9px; border-radius:8px; border:1px solid; letter-spacing:.04em; white-space:nowrap; }
        .badge-completed  { background:rgba(16,185,129,.1);  color:#059669; border-color:rgba(16,185,129,.25); }
        .badge-cooking    { background:rgba(245,158,11,.1);   color:#d97706; border-color:rgba(245,158,11,.25); }
        .badge-ready      { background:rgba(99,102,241,.1);   color:#6366f1; border-color:rgba(99,102,241,.25); }
        .badge-pending    { background:rgba(251,191,36,.1);   color:#ca8a04; border-color:rgba(251,191,36,.25); }
        .badge-cancelled  { background:rgba(239,68,68,.1);    color:#dc2626; border-color:rgba(239,68,68,.25); }
        .badge-paid       { background:rgba(16,185,129,.1);   color:#059669; border-color:rgba(16,185,129,.2); }
        .badge-unpaid     { background:rgba(148,163,184,.1);  color:#64748b; border-color:rgba(148,163,184,.2); }

        .dark .badge-completed { color:#34d399; }
        .dark .badge-cooking   { color:#fbbf24; }
        .dark .badge-ready     { color:#a5b4fc; }
        .dark .badge-pending   { color:#fde68a; }
        .dark .badge-cancelled { color:#f87171; }
        .dark .badge-paid      { color:#34d399; }
        .dark .badge-unpaid    { color:#94a3b8; }

        /* Print */
        @media print {
            body * { visibility: hidden; }
            #printable-receipt-card, #printable-receipt-card * { visibility: visible; }
            #printable-receipt-card {
                position: absolute; left: 0; top: 0;
                width: 100%; max-width: 80mm;
                margin: 0; padding: 10px;
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                border: none !important;
                font-size: 12px;
            }
            @page { size: auto; margin: 0mm; }
        }
    </style>
</head>
<body class="min-h-screen flex bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100" x-data="ordersHistoryApp()">

    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content -->
    <div class="flex-grow flex flex-col overflow-hidden h-screen">

        <!-- ── Top Header ── -->
        <header class="relative bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 px-5 py-3 flex flex-col lg:flex-row items-center justify-between gap-4 flex-shrink-0 z-20">
            <div class="flex items-center justify-between w-full lg:w-auto">
                <div class="flex items-center gap-3">
                    <button @click="$dispatch('toggle-sidebar')" class="lg:hidden w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-900 dark:hover:text-white rounded-lg transition-colors text-lg">☰</button>
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-lg shadow-lg shadow-orange-500/20 flex-shrink-0">🗄️</div>
                    <div>
                        <h1 class="text-sm font-black text-slate-900 dark:text-white leading-none">
                            {{ $isArchive ? 'أرشيف الفواتير العام' : 'سجل الفواتير اليومية' }}
                        </h1>
                        <span class="text-[10px] text-slate-450 dark:text-slate-500 font-bold block mt-0.5 leading-none">
                            {{ $isArchive ? 'Historical Archives' : 'Today\'s Orders & Billings' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="/pos" class="lg:hidden bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-250 dark:border-slate-700 px-3.5 py-2 rounded-2xl text-[10px] font-black tracking-wider transition-all shadow-sm flex items-center gap-1.5 flex-shrink-0">
                        🧾 الكاشير
                    </a>
                    <div class="bg-amber-500/10 border border-amber-500/20 px-4 py-2 rounded-xl hover-float">
                        <span class="text-[11px] text-amber-600 dark:text-amber-400 font-black">
                            الإيرادات: <span x-text="formatCurrency(animatedRevenue)"></span> د.ل
                        </span>
                    </div>
                </div>
            </div>

            <!-- Date filter only for Archive -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full lg:w-auto justify-end">
                @if($isArchive)
                <form action="/admin/orders/archive" method="GET" class="flex items-center justify-between sm:justify-start gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-1.5 rounded-2xl shadow-sm w-full sm:w-auto">
                    <div class="flex items-center gap-1 px-1.5">
                        <span class="text-[8px] text-slate-400 font-black uppercase">من:</span>
                        <input type="date" name="start_date" value="{{ session('start_date') ? \Illuminate\Support\Carbon::parse(session('start_date'))->format('Y-m-d') : \Illuminate\Support\Carbon::today()->format('Y-m-d') }}" class="bg-transparent text-[11px] font-bold text-slate-750 dark:text-slate-200 focus:outline-none w-24 sm:w-auto" />
                    </div>
                    <div class="h-3 w-px bg-slate-250 dark:bg-slate-700"></div>
                    <div class="flex items-center gap-1 px-1.5">
                        <span class="text-[8px] text-slate-400 font-black uppercase">إلى:</span>
                        <input type="date" name="end_date" value="{{ session('end_date') ? \Illuminate\Support\Carbon::parse(session('end_date'))->format('Y-m-d') : \Illuminate\Support\Carbon::today()->format('Y-m-d') }}" class="bg-transparent text-[11px] font-bold text-slate-750 dark:text-slate-200 focus:outline-none w-24 sm:w-auto" />
                    </div>
                    <button type="submit" class="bg-slate-900 hover:bg-slate-950 dark:bg-slate-800 dark:hover:bg-slate-755 text-white text-[9px] font-black px-3.5 py-2 rounded-xl transition-all shadow">
                        تصفية
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="/admin/orders/archive" class="text-rose-600 hover:text-rose-700 text-[10px] font-bold px-2">إعادة</a>
                    @endif
                </form>
                @endif

                <div class="hidden md:flex items-center gap-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3.5 py-2 rounded-xl">
                    <span class="text-[10px] text-slate-550 dark:text-slate-400 font-black uppercase tracking-wider whitespace-nowrap">🖨️ IP:</span>
                    <input type="text" x-model="printerIp" placeholder="192.168.1.100"
                           class="w-28 bg-transparent text-xs font-mono text-slate-800 dark:text-slate-200 focus:outline-none text-center" dir="ltr" />
                </div>
            </div>
        </header>

        <!-- ── Body ── -->
        <main class="flex-grow overflow-y-auto p-5 lg:p-6 space-y-5 fade-up" dir="rtl">

            @if(session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-250 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse shadow-sm text-right">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- ── KPI Cards ── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Revenue -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex items-center justify-between shadow-sm hover-float">
                    <div>
                        <p class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider mb-1">إجمالي الإيرادات</p>
                        <p class="text-xl font-black text-amber-600 dark:text-amber-400" dir="ltr">
                            <span x-text="formatCurrency(animatedRevenue)"></span>
                            <span class="text-xs font-bold">د.ل</span>
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center text-2xl">💰</div>
                </div>
                <!-- Completed count -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex items-center justify-between shadow-sm hover-float">
                    <div>
                        <p class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider mb-1">الفواتير المكتملة</p>
                        <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                            <span x-text="animatedCompleted"></span>
                            <span class="text-xs font-bold text-slate-400">فاتورة</span>
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center text-2xl">✅</div>
                </div>
                <!-- Active -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex items-center justify-between shadow-sm hover-float">
                    <div>
                        <p class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider mb-1">الطلبات النشطة</p>
                        <p class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                            <span x-text="animatedActive"></span>
                            <span class="text-xs font-bold text-slate-400">طلب</span>
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/15 flex items-center justify-center text-2xl">⏳</div>
                </div>
            </div>

            <!-- ── Orders Table ── -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">

                <!-- Table header toolbar -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 flex-wrap">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white">
                        {{ $isArchive ? 'أرشيف الفواتير والمعاملات التاريخية' : 'سجل الفواتير والمعاملات لليوم' }}
                    </h2>

                    <!-- Printer IP (mobile) -->
                    <div class="md:hidden flex items-center gap-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-1.5 rounded-xl">
                        <span class="text-[10px] text-slate-500 font-black">🖨️</span>
                        <input type="text" x-model="printerIp" placeholder="192.168.1.100"
                               class="w-28 bg-transparent text-xs font-mono text-slate-800 dark:text-slate-200 focus:outline-none text-center" dir="ltr" />
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30">
                    <div class="flex gap-1.5 overflow-x-auto flex-nowrap whitespace-nowrap pb-0.5" dir="rtl">
                        <button @click="currentFilter='all'"
                                :class="currentFilter==='all' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800'"
                                class="px-4 py-1.5 rounded-xl text-xs font-black transition-all flex-shrink-0">
                            الكل (<span x-text="orders.length"></span>)
                        </button>
                        <button @click="currentFilter='pending'"
                                :class="currentFilter==='pending' ? 'bg-amber-400 text-slate-950 shadow-md shadow-amber-400/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800'"
                                class="px-4 py-1.5 rounded-xl text-xs font-black transition-all flex-shrink-0">
                            ⏳ معلقة (<span x-text="orders.filter(o => o.status === 'pending').length"></span>)
                        </button>
                        <button @click="currentFilter='cooking'"
                                :class="currentFilter==='cooking' ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800'"
                                class="px-4 py-1.5 rounded-xl text-xs font-black transition-all flex-shrink-0">
                            🔥 قيد التحضير (<span x-text="orders.filter(o => o.status === 'cooking').length"></span>)
                        </button>
                        <button @click="currentFilter='ready'"
                                :class="currentFilter==='ready' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800'"
                                class="px-4 py-1.5 rounded-xl text-xs font-black transition-all flex-shrink-0">
                            🟢 جاهزة (<span x-text="orders.filter(o => o.status === 'ready').length"></span>)
                        </button>
                        <button @click="currentFilter='completed'"
                                :class="currentFilter==='completed' ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800'"
                                class="px-4 py-1.5 rounded-xl text-xs font-black transition-all flex-shrink-0">
                            ✅ مكتملة (<span x-text="orders.filter(o => o.status === 'completed').length"></span>)
                        </button>
                        <button @click="currentFilter='cancelled'"
                                :class="currentFilter==='cancelled' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800'"
                                class="px-4 py-1.5 rounded-xl text-xs font-black transition-all flex-shrink-0">
                            ❌ ملغية (<span x-text="orders.filter(o => o.status === 'cancelled').length"></span>)
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs" dir="rtl">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 text-[10px] uppercase font-black text-slate-400 dark:text-slate-600 tracking-wider">
                                <th class="px-5 py-3.5 text-right">رقم الفاتورة</th>
                                <th class="px-5 py-3.5 text-right">الفرع</th>
                                <th class="px-5 py-3.5 text-right">حالة الطلب</th>
                                <th class="px-5 py-3.5 text-right">الدفع</th>
                                <th class="px-5 py-3.5 text-right hidden lg:table-cell">الملاحظات</th>
                                <th class="px-5 py-3.5 text-center">الوجبات</th>
                                <th class="px-5 py-3.5 text-left">القيمة</th>
                                <th class="px-5 py-3.5 text-right hidden md:table-cell">التاريخ</th>
                                <th class="px-5 py-3.5 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/60">
                            @forelse($orders as $order)
                                <tr class="orders-row transition-colors"
                                    x-show="currentFilter === 'all' || getOrderStatus('{{ $order->id }}') === currentFilter">
                                    <td class="px-5 py-3.5">
                                        <span class="font-mono font-black text-slate-700 dark:text-slate-300 text-[11px]">{{ $order->invoice_number ?? '#' . strtoupper(substr($order->id, 0, 8)) }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 font-bold text-slate-600 dark:text-slate-400">{{ $order->location->name ?? 'فرع محذوف' }}</td>
                                    <td class="px-5 py-3.5">
                                        <span class="badge" :class="getBadgeClass('{{ $order->id }}')" x-text="getStatusText('{{ $order->id }}')">
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="badge {{ $order->payment_status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                                            {{ $order->payment_status === 'paid' ? '💳 مدفوع' : '○ غير مدفوع' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 hidden lg:table-cell max-w-[160px]">
                                        <span class="truncate block text-slate-500 dark:text-slate-500 font-semibold" title="{{ $order->notes }}">
                                            {{ $order->notes ? Str::limit($order->notes, 35) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-black text-slate-700 dark:text-slate-300">
                                        {{ $order->items->sum('quantity') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-left font-black text-amber-600 dark:text-amber-400" dir="ltr">
                                        {{ number_format($order->total_amount, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span>
                                    </td>
                                    <td class="px-5 py-3.5 hidden md:table-cell">
                                        <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <!-- Status actions -->
                                            <template x-if="getOrderStatus('{{ $order->id }}') === 'pending'">
                                                <div class="flex items-center gap-1">
                                                    <button @click="updateOrderStatus('{{ $order->id }}', 'cooking')"
                                                            class="bg-amber-400/10 hover:bg-amber-400 text-amber-700 dark:text-amber-400 dark:hover:text-slate-900 border border-amber-400/20 text-[10px] font-black px-2.5 py-1.5 rounded-lg transition-all">
                                                        🔥 بدء التحضير
                                                    </button>
                                                    <button @click="updateOrderStatus('{{ $order->id }}', 'cancelled')"
                                                            class="bg-rose-500/10 hover:bg-rose-500 text-rose-600 dark:text-rose-400 dark:hover:text-white border border-rose-500/20 text-[10px] font-black px-2 py-1.5 rounded-lg transition-all" title="إلغاء الفاتورة">
                                                        ❌
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="getOrderStatus('{{ $order->id }}') === 'cooking'">
                                                <div class="flex items-center gap-1">
                                                    <button @click="updateOrderStatus('{{ $order->id }}', 'ready')"
                                                            class="bg-indigo-600/10 hover:bg-indigo-600 text-indigo-600 dark:text-indigo-400 dark:hover:text-white border border-indigo-500/20 text-[10px] font-black px-2.5 py-1.5 rounded-lg transition-all">
                                                        🟢 جاهز للتسليم
                                                    </button>
                                                    <button @click="updateOrderStatus('{{ $order->id }}', 'cancelled')"
                                                            class="bg-rose-500/10 hover:bg-rose-500 text-rose-600 dark:text-rose-400 dark:hover:text-white border border-rose-500/20 text-[10px] font-black px-2 py-1.5 rounded-lg transition-all" title="إلغاء الفاتورة">
                                                        ❌
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="getOrderStatus('{{ $order->id }}') === 'ready'">
                                                <div class="flex items-center gap-1">
                                                    <button @click="updateOrderStatus('{{ $order->id }}', 'completed')"
                                                            class="bg-emerald-500/10 hover:bg-emerald-500 text-emerald-600 dark:text-emerald-400 dark:hover:text-white border border-emerald-500/20 text-[10px] font-black px-2.5 py-1.5 rounded-lg transition-all">
                                                        🍽️ تسليم وإتمام
                                                    </button>
                                                    <button @click="updateOrderStatus('{{ $order->id }}', 'cancelled')"
                                                            class="bg-rose-500/10 hover:bg-rose-500 text-rose-600 dark:text-rose-400 dark:hover:text-white border border-rose-500/20 text-[10px] font-black px-2 py-1.5 rounded-lg transition-all" title="إلغاء الفاتورة">
                                                        ❌
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="getOrderStatus('{{ $order->id }}') === 'completed'">
                                                <button @click="updateOrderStatus('{{ $order->id }}', 'ready')"
                                                        class="bg-slate-500/10 hover:bg-slate-500 text-slate-600 dark:text-slate-400 dark:hover:text-white border border-slate-500/20 text-[10px] font-black px-2.5 py-1.5 rounded-lg transition-all">
                                                    ↺ إرجاع
                                                </button>
                                            </template>

                                            <!-- Edit order button (only if not cancelled) -->
                                            <template x-if="getOrderStatus('{{ $order->id }}') !== 'cancelled'">
                                                <button @click="openEditModal('{{ $order->id }}')"
                                                        class="bg-amber-500/10 dark:bg-amber-550/10 hover:bg-amber-505 hover:text-slate-950 dark:hover:bg-amber-505 dark:hover:text-slate-950 text-amber-700 dark:text-amber-400 text-[10px] font-black px-2.5 py-1.5 rounded-lg border border-amber-500/20 hover:border-amber-600 transition-all">
                                                    ✏️ تعديل
                                                </button>
                                            </template>

                                            <button @click="reprintReceipt('{{ $order->id }}')"
                                                    class="bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 dark:hover:bg-amber-500 dark:hover:text-slate-950 text-slate-600 dark:text-slate-400 text-[10px] font-black px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-amber-500 transition-all">
                                                🖨️ IP
                                            </button>
                                            <button @click="printBrowser('{{ $order->id }}')"
                                                    class="bg-indigo-500/10 dark:bg-indigo-500/10 hover:bg-indigo-600 hover:text-white text-indigo-600 dark:text-indigo-400 text-[10px] font-black px-2.5 py-1.5 rounded-lg border border-indigo-500/20 hover:border-indigo-600 transition-all">
                                                📄 طباعة
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-16 text-center">
                                        <div class="flex flex-col items-center gap-3 opacity-40">
                                            <span class="text-4xl">📋</span>
                                            <span class="text-sm font-black text-slate-400">لا يوجد فواتير مسجلة حالياً</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Order Modal -->
    <div x-show="showEditModal"
         x-transition.opacity
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 text-right"
         style="display: none;">
        <div @click.away="showEditModal = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-2xl p-6 shadow-2xl relative max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white leading-none">تعديل الفاتورة رقم: <span x-text="editingOrder?.invoice_number || ''"></span></h3>
                    <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold block mt-1">تعديل الوجبات، الكميات، الخصومات والضرائب</span>
                </div>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-650 text-xl font-bold">×</button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="flex-grow overflow-y-auto space-y-4 pr-1 pl-1">
                <!-- Items Table -->
                <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950/50 text-[10px] font-black text-slate-400 dark:text-slate-600 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-3">الوجبة</th>
                                <th class="px-4 py-3 text-center">الكمية</th>
                                <th class="px-4 py-3 text-left">السعر</th>
                                <th class="px-4 py-3 text-left">الإجمالي</th>
                                <th class="px-4 py-3 text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <template x-for="(item, index) in (editingOrder ? editingOrder.items : [])" :key="item.id">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20">
                                    <td class="px-4 py-3 font-bold text-slate-800 dark:text-slate-200" x-text="item.product ? item.product.name : 'منتج غير معروف'"></td>
                                    <td class="px-4 py-2 text-center">
                                        <div class="inline-flex items-center gap-2 bg-slate-100 dark:bg-slate-850 px-2 py-1 rounded-xl">
                                            <button type="button" @click="decrementEditQty(item)" class="w-6 h-6 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">-</button>
                                            <span class="font-mono font-bold text-xs w-6 text-center" x-text="item.quantity"></span>
                                            <button type="button" @click="incrementEditQty(item)" class="w-6 h-6 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">+</button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400" x-text="parseFloat(item.price).toFixed(2) + ' د.ل'"></td>
                                    <td class="px-4 py-3 text-left font-black text-slate-800 dark:text-slate-200" x-text="parseFloat(item.price * item.quantity).toFixed(2) + ' د.ل'"></td>
                                    <td class="px-4 py-2 text-center">
                                        <button type="button" @click="removeEditItem(index)" class="text-rose-600 hover:text-rose-800 font-black text-sm p-1">🗑️</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Add New Item Section -->
                <div class="bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-4 rounded-2xl">
                    <h4 class="text-xs font-black text-slate-800 dark:text-slate-350 mb-3">إضافة صنف جديد للفاتورة</h4>
                    <div class="flex items-end gap-3 flex-wrap sm:flex-nowrap">
                        <!-- Product selection -->
                        <div class="space-y-1.5 flex-grow text-right">
                            <label class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">الوجبة / الصنف</label>
                            <select x-model="selectedAddProductId" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:outline-none">
                                <option value="">اختر وجبة...</option>
                                <template x-for="prod in allProducts" :key="prod.id">
                                    <option :value="prod.id" x-text="prod.name + ' (' + parseFloat(prod.base_price).toFixed(2) + ' د.ل)'"></option>
                                </template>
                            </select>
                        </div>
                        <!-- Quantity -->
                        <div class="space-y-1.5 w-24 text-right flex-shrink-0">
                            <label class="text-[10px] text-slate-400 dark:text-slate-550 font-bold block">الكمية</label>
                            <input type="number" x-model="selectedAddQty" min="1" step="1" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 focus:border-amber-500 rounded-xl px-3 py-2 text-center text-xs text-slate-800 dark:text-slate-200 focus:outline-none font-bold" />
                        </div>
                        <!-- Add Button -->
                        <button type="button" @click="addEditItem()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow h-9 flex items-center justify-center">
                            ＋ إضافة صنف
                        </button>
                    </div>
                </div>

                <!-- Discount and Tax Fields -->
                <div class="grid grid-cols-2 gap-4" x-if="editingOrder">
                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">الخصم المباشر (د.ل)</label>
                        <input type="number" step="0.01" min="0" x-model="editingOrder.discount" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 dark:text-slate-200 focus:outline-none font-bold text-right" />
                    </div>
                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">الضرائب (د.ل)</label>
                        <input type="number" step="0.01" min="0" x-model="editingOrder.tax" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 dark:text-slate-200 focus:outline-none font-bold text-right" />
                    </div>
                </div>

                <!-- Live Totals Summary -->
                <div class="bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 flex justify-between items-center shadow-inner">
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block font-bold">المجموع الفرعي للأصناف</span>
                        <span class="text-xs font-black text-slate-700 dark:text-slate-350" x-text="parseFloat(getEditingSubtotal()).toFixed(2) + ' د.ل'"></span>
                    </div>
                    <div class="text-left">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block font-bold text-left">صافي إجمالي الفاتورة الجديد</span>
                        <span class="text-lg font-black text-amber-600 dark:text-amber-450" x-text="parseFloat(getEditingTotal()).toFixed(2) + ' د.ل'"></span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-4 flex-shrink-0">
                <button type="button" @click="submitOrderEdits()" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black py-3.5 rounded-xl transition-all shadow-lg shadow-amber-500/10">حفظ وتعديل الفاتورة</button>
                <button type="button" @click="showEditModal = false" class="w-1/3 bg-slate-250 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-755 text-slate-800 dark:text-slate-200 font-black py-3.5 rounded-xl transition-all">إلغاء</button>
            </div>
        </div>
    </div>

    <!-- Hidden printable receipt -->
    <div id="printable-receipt-card" class="hidden print:block bg-white text-gray-900 p-4 text-right w-full" style="font-family:monospace;" dir="rtl">
        <template x-if="selectedOrder">
            <div>
                <div class="text-center space-y-0.5 mb-3">
                    <h2 class="text-base font-extrabold">مطعم المدينة المنورة</h2>
                    <p class="text-[11px] text-gray-500 font-bold" x-text="selectedOrder.location ? selectedOrder.location.name : ''"></p>
                    <p class="text-[10px] text-gray-400">الهاتف: 091-0000000</p>
                </div>
                <div class="border-t border-dashed border-gray-400 my-2"></div>
                <div class="text-[11px] space-y-1">
                    <div class="flex justify-between">
                        <span>رقم الفاتورة:</span>
                        <span class="font-bold" x-text="selectedOrder.invoice_number || selectedOrder.id.substring(0,8).toUpperCase()"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>التاريخ:</span>
                        <span x-text="new Date(selectedOrder.created_at).toLocaleString('ar-LY')"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>طريقة الدفع:</span>
                        <span class="font-bold" x-text="selectedOrder.payments && selectedOrder.payments.length ? translatePaymentMethod(selectedOrder.payments[0].payment_method) : 'نقداً'"></span>
                    </div>
                </div>
                <template x-if="selectedOrder.notes">
                    <div class="bg-gray-100 border border-gray-300 p-2 rounded text-[10px] mt-2">
                        <span class="text-gray-600 font-bold block mb-1">ملاحظات:</span>
                        <span x-text="selectedOrder.notes"></span>
                    </div>
                </template>
                <div class="border-t border-dashed border-gray-400 my-2"></div>
                <div class="text-[11px] space-y-1 mb-2">
                    <template x-for="item in selectedOrder.items" :key="item.id">
                        <div class="flex justify-between">
                            <span x-text="item.product ? item.product.name : ''"></span>
                            <span class="font-bold" x-text="item.quantity + 'x ' + parseFloat(item.price * item.quantity).toFixed(2) + ' د.ل'"></span>
                        </div>
                    </template>
                </div>
                <div class="border-t border-dashed border-gray-400 my-2"></div>
                <div class="flex justify-between font-bold text-sm">
                    <span>الإجمالي:</span>
                    <span x-text="parseFloat(selectedOrder.total_amount).toFixed(2) + ' د.ل'"></span>
                </div>
                <div class="text-center mt-3 text-[10px] text-gray-600 font-bold">شكراً لزيارتكم • صحتين وعافية!</div>
            </div>
        </template>
    </div>

    <script>
        function ordersHistoryApp() {
            return {
                currentFilter: 'all',
                printerIp: localStorage.getItem('printerIp') || '',
                selectedOrder: null,
                orders: @json($orders->load('items.product', 'location', 'payments')),

                // Animated KPI stats
                animatedRevenue: 0,
                animatedCompleted: 0,
                animatedActive: 0,

                // Edit order Alpine state
                showEditModal: false,
                editingOrder: null,
                allProducts: @json($products),
                selectedAddProductId: '',
                selectedAddQty: 1,

                init() {
                    this.$watch('printerIp', val => localStorage.setItem('printerIp', val));
                    this.animateKPIs();
                    // Watch orders for deep changes to keep KPIs synchronized instantly
                    this.$watch('orders', () => {
                        const revTarget = this.orders.filter(o => o.status !== 'cancelled').reduce((acc, o) => acc + parseFloat(o.total_amount), 0);
                        const compTarget = this.orders.filter(o => o.status === 'completed').length;
                        const activeTarget = this.orders.filter(o => ['pending','cooking','ready'].includes(o.status)).length;
                        this.animatedRevenue = revTarget;
                        this.animatedCompleted = compTarget;
                        this.animatedActive = activeTarget;
                    }, { deep: true });
                },

                animateKPIs() {
                    const revTarget = this.orders.filter(o => o.status !== 'cancelled').reduce((acc, o) => acc + parseFloat(o.total_amount), 0);
                    const compTarget = this.orders.filter(o => o.status === 'completed').length;
                    const activeTarget = this.orders.filter(o => ['pending','cooking','ready'].includes(o.status)).length;
                    
                    let startTimestamp = null;
                    const duration = 1200; // 1.2s
                    
                    const step = (timestamp) => {
                        if (!startTimestamp) startTimestamp = timestamp;
                        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                        const ease = progress * (2 - progress);
                        
                        this.animatedRevenue = ease * revTarget;
                        this.animatedCompleted = Math.floor(ease * compTarget);
                        this.animatedActive = Math.floor(ease * activeTarget);
                        
                        if (progress < 1) {
                            window.requestAnimationFrame(step);
                        } else {
                            this.animatedRevenue = revTarget;
                            this.animatedCompleted = compTarget;
                            this.animatedActive = activeTarget;
                        }
                    };
                    window.requestAnimationFrame(step);
                },

                openEditModal(orderId) {
                    const order = this.orders.find(o => o.id === orderId);
                    if (!order) return;
                    this.editingOrder = JSON.parse(JSON.stringify(order));
                    this.selectedAddProductId = '';
                    this.selectedAddQty = 1;
                    this.showEditModal = true;
                },

                getEditingSubtotal() {
                    if (!this.editingOrder) return 0;
                    return this.editingOrder.items.reduce((sum, item) => {
                        const price = parseFloat(item.price || (item.product ? item.product.base_price : 0));
                        return sum + (price * item.quantity);
                    }, 0);
                },

                getEditingTotal() {
                    const subtotal = this.getEditingSubtotal();
                    if (!this.editingOrder) return 0;
                    const discount = parseFloat(this.editingOrder.discount || 0);
                    const tax = parseFloat(this.editingOrder.tax || 0);
                    return Math.max(0, subtotal - discount + tax);
                },

                incrementEditQty(item) {
                    item.quantity++;
                },

                decrementEditQty(item) {
                    if (item.quantity > 1) {
                        item.quantity--;
                    }
                },

                removeEditItem(index) {
                    this.editingOrder.items.splice(index, 1);
                },

                addEditItem() {
                    if (!this.selectedAddProductId) return;
                    const product = this.allProducts.find(p => p.id === this.selectedAddProductId);
                    if (!product) return;

                    const existingItem = this.editingOrder.items.find(i => i.product_id === product.id);
                    if (existingItem) {
                        existingItem.quantity += parseInt(this.selectedAddQty || 1);
                    } else {
                        this.editingOrder.items.push({
                            id: 'NEW_' + Math.random().toString(36).substr(2, 9),
                            product_id: product.id,
                            product: product,
                            quantity: parseInt(this.selectedAddQty || 1),
                            price: parseFloat(product.base_price)
                        });
                    }

                    this.selectedAddProductId = '';
                    this.selectedAddQty = 1;
                },

                submitOrderEdits() {
                    if (!this.editingOrder || this.editingOrder.items.length === 0) {
                        alert('يجب أن تحتوي الفاتورة على صنف واحد على الأقل.');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/pos/orders/${this.editingOrder.id}/update`;

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);

                    this.editingOrder.items.forEach((item, index) => {
                        const pidInput = document.createElement('input');
                        pidInput.type = 'hidden';
                        pidInput.name = `items[${index}][product_id]`;
                        pidInput.value = item.product_id;
                        form.appendChild(pidInput);

                        const qtyInput = document.createElement('input');
                        qtyInput.type = 'hidden';
                        qtyInput.name = `items[${index}][quantity]`;
                        qtyInput.value = item.quantity;
                        form.appendChild(qtyInput);
                    });

                    const discInput = document.createElement('input');
                    discInput.type = 'hidden';
                    discInput.name = 'discount';
                    discInput.value = this.editingOrder.discount;
                    form.appendChild(discInput);

                    const taxInput = document.createElement('input');
                    taxInput.type = 'hidden';
                    taxInput.name = 'tax';
                    taxInput.value = this.editingOrder.tax;
                    form.appendChild(taxInput);

                    document.body.appendChild(form);
                    form.submit();
                },

                formatCurrency(val) {
                    return parseFloat(val).toLocaleString('ar-LY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                getOrderStatus(orderId) {
                    const order = this.orders.find(o => o.id === orderId);
                    return order ? order.status : 'pending';
                },

                getStatusText(orderId) {
                    const status = this.getOrderStatus(orderId);
                    const dict = {
                        completed: '✅ مكتمل',
                        cooking: '🔥 قيد التحضير',
                        ready: '🟢 جاهز',
                        pending: '⏳ معلق',
                        cancelled: '❌ ملغي'
                    };
                    return dict[status] || status;
                },

                getBadgeClass(orderId) {
                    const status = this.getOrderStatus(orderId);
                    const dict = {
                        completed: 'badge-completed',
                        cooking: 'badge-cooking',
                        ready: 'badge-ready',
                        pending: 'badge-pending',
                        cancelled: 'badge-cancelled'
                    };
                    return dict[status] || 'badge-cancelled';
                },

                updateOrderStatus(orderId, newStatus) {
                    fetch(`/pos/orders/${orderId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            const order = this.orders.find(o => o.id === orderId);
                            if (order) {
                                order.status = newStatus;
                            }
                        } else {
                            alert('حدث خطأ أثناء تحديث حالة الطلب.');
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        alert('حدث خطأ في الشبكة.');
                    });
                },

                translatePaymentMethod(method) {
                    const dict = { cash:'نقداً', sadad:'سداد', mobicash:'موبي كاش', tadawul:'تداول' };
                    return dict[(method||'').toLowerCase()] || method;
                },

                printBrowser(orderId) {
                    const order = this.orders.find(o => o.id === orderId);
                    if (!order) return;
                    this.selectedOrder = order;
                    this.$nextTick(() => window.print());
                },

                reprintReceipt(orderId) {
                    if (!this.printerIp) {
                        alert('يرجى إدخال عنوان IP الطابعة أولاً!');
                        return;
                    }
                    fetch(`/pos/orders/${orderId}/print`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ ip: this.printerIp })
                    })
                    .then(r => r.json())
                    .then(d => alert(d.success ? 'تم إرسال أمر الطباعة!' : 'فشل الاتصال بالطابعة.'));
                }
            };
        }
    </script>
</body>
</html>
