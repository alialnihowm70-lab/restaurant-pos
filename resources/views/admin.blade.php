@php
    $driverName = \DB::connection()->getDriverName();
    if ($driverName === 'sqlite') {
        $dateExpr = "strftime('%Y-%m-%d', created_at)";
    } elseif ($driverName === 'pgsql') {
        $dateExpr = "to_char(created_at, 'YYYY-MM-DD')";
    } else {
        $dateExpr = "date_format(created_at, '%Y-%m-%d')";
    }

    $salesTrend = \DB::table('orders')
        ->where('status', '!=', 'cancelled')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->select(\DB::raw("$dateExpr as date"), \DB::raw('SUM(total_amount) as total'))
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->pluck('total', 'date')
        ->toArray();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <!-- Immediate theme prevention flash script -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>منظومة المدينة - لوحة الإدارة والتحليلات</title>
    <!-- Chart.js CDN (async to avoid blocking page render) -->
    <script async src="https://cdn.jsdelivr.net/npm/chart.js" onload="window.__chartJsLoaded = true; window.dispatchEvent(new Event('chartjs-ready'));"></script>
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
        .dark body {
            background-color: #020617; /* slate-950 */
            color: #f8fafc;
            background-image: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.08) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(99, 102, 241, 0.08) 0%, transparent 40%);
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-animate {
            animation: pageFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-financial-statement, #printable-financial-statement * {
                visibility: visible;
            }
            #printable-financial-statement {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                background: white !important;
                color: black !important;
            }
            @page {
                size: portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body class="min-h-screen flex bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 relative overflow-x-hidden page-animate">

    <!-- Decorative Glow Circles -->
    <div class="hidden dark:block absolute top-10 right-10 w-[500px] h-[500px] bg-amber-500/8 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="hidden dark:block absolute bottom-10 left-10 w-[550px] h-[550px] bg-indigo-500/8 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- Unified Left Sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace Area -->
    <div class="flex-grow flex flex-col h-screen overflow-hidden relative" x-data="{ supplierTab: 'list', showReportModal: false, rent: '', salaries: '', utilities: '', misc: '', editingSupplier: null, editingUser: null }" dir="rtl">
        <!-- Top bar inside content area -->
        <header class="relative bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 px-5 py-3 flex flex-col lg:flex-row items-center justify-between gap-3 flex-shrink-0 text-right z-20">
            <!-- Mobile Header Row -->
            <div class="flex items-center justify-between w-full lg:w-auto">
                <div class="flex items-center gap-3">
                    <!-- Mobile Sidebar Toggle -->
                    <button @click="$dispatch('toggle-sidebar')" class="lg:hidden p-2 text-slate-755 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white focus:outline-none text-2xl leading-none">
                        ☰
                    </button>
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-lg shadow-lg shadow-orange-500/20 flex-shrink-0">
                        📊
                    </div>
                    <div>
                        <h1 class="text-sm font-black text-slate-900 dark:text-white leading-none">لوحة التحكم والتقارير</h1>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block mt-0.5">Admin Dashboard & Analytics</span>
                    </div>
                </div>
                <!-- Right side: Report button + Backup Cashier Button -->
                <div class="flex items-center gap-2">
                    <a href="/pos" class="lg:hidden bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-250 dark:border-slate-700 px-3.5 py-2 rounded-2xl text-[10px] font-black tracking-wider transition-all shadow-sm flex items-center gap-1.5">
                        🧾 الكاشير
                    </a>
                    <button @click="showReportModal = true" class="lg:hidden p-2 text-amber-650 hover:text-amber-850 text-lg leading-none">
                        💼
                    </button>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full lg:w-auto">
                <form action="/admin" method="GET" class="flex items-center justify-between sm:justify-start gap-2 bg-white border border-slate-200 p-1.5 rounded-2xl shadow-sm w-full sm:w-auto">
                    <div class="flex items-center gap-1 px-1.5">
                        <span class="text-[8px] text-slate-400 font-black uppercase">من:</span>
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="bg-transparent text-[11px] font-bold text-slate-700 focus:outline-none w-24 sm:w-auto" />
                    </div>
                    <div class="h-3 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-1 px-1.5">
                        <span class="text-[8px] text-slate-400 font-black uppercase">إلى:</span>
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="bg-transparent text-[11px] font-bold text-slate-700 focus:outline-none w-24 sm:w-auto" />
                    </div>
                    <button type="submit" class="bg-slate-900 hover:bg-slate-950 text-white text-[9px] font-black px-3.5 py-2 rounded-xl transition-all shadow">
                        تصفية
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="/admin" class="text-rose-600 hover:text-rose-700 text-[10px] font-bold px-2">إعادة</a>
                    @endif
                </form>
                <button @click="showReportModal = true" class="hidden lg:block bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 text-xs font-black px-5 py-3 rounded-2xl transition-all shadow-lg shadow-orange-550/15">
                    💼 إصدار تقرير مالي P&L
                </button>
            </div>
        </header>


        <main class="flex-grow overflow-y-auto p-5 lg:p-7 space-y-6">
        
        <!-- Flash Alert Notification -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse shadow-sm">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-250 text-rose-700 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse shadow-sm">
                <span>❌</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Low Stock Alert Banner -->
        @if(isset($lowStockIngredients) && count($lowStockIngredients) > 0)
            <div class="bg-gradient-to-r from-amber-500/10 to-orange-550/10 border border-amber-500/20 text-amber-900 p-5 rounded-[28px] text-xs font-bold flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm relative overflow-hidden">
                <div class="flex items-center gap-3.5">
                    <span class="text-3xl animate-bounce">⚠️</span>
                    <div class="text-right">
                        <span class="font-black text-slate-900 text-sm block">تنبيه: نواقص في مخزون المواد الخام!</span>
                        <span class="text-slate-500 font-bold block mt-1">يوجد عدد {{ count($lowStockIngredients) }} مكونات أساسية تحت حد الطلب الأدنى حالياً. يرجى توريدها لضمان استمرارية التشغيل.</span>
                    </div>
                </div>
                <a href="/admin/inventory" class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-slate-950 px-4.5 py-3 rounded-2xl transition-all shadow-md shadow-orange-550/15 font-black text-xs flex items-center gap-1.5 self-end md:self-auto">
                    📦 مراجعة وجرد المخزن
                </a>
            </div>
        @endif

        <!-- 1. Real-Time Accounting Analytics Cards -->
        <section class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <!-- Total Revenue Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right col-span-1">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-blue-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">إجمالي المبيعات (Gross)</span>
                    <h3 class="text-lg font-black text-blue-600 tracking-tight" dir="ltr">{{ number_format($salesTotal, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[8px] text-slate-400 block font-medium truncate">مقبوضات الفترة الحالية</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shadow-inner">💵</div>
            </div>

            <!-- Cost of Goods Sold Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right col-span-1">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-rose-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">تكلفة الأغذية (COGS)</span>
                    <h3 class="text-lg font-black text-rose-600 tracking-tight" dir="ltr">{{ number_format($totalCogs, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[8px] text-slate-400 block font-medium truncate">التكلفة التقديرية للوجبات</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-rose-550/5 text-rose-600 flex items-center justify-center text-base shadow-inner">🥩</div>
            </div>

            <!-- Inventory Loss/Waste Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right col-span-1">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-red-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">الهدر والتالف</span>
                    <h3 class="text-lg font-black text-red-650 tracking-tight" dir="ltr">{{ number_format($totalWasteCost, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[8px] text-slate-400 block font-medium truncate">تسويات فروقات الجرد</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-base shadow-inner">🗑️</div>
            </div>

            <!-- Net Profit Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right col-span-1">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-amber-500"></div>
                <div class="space-y-1">
                    <div class="flex items-center gap-1">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block truncate">صافي الربح</span>
                        <span class="text-[8px] font-black bg-amber-500/10 text-amber-600 px-1 py-0.5 rounded">{{ number_format($profitMargin, 0) }}%</span>
                    </div>
                    <h3 class="text-lg font-black text-amber-650 tracking-tight" dir="ltr">{{ number_format($grossProfit, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[8px] text-slate-400 block font-medium truncate">إجمالي صافي الأرباح</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base shadow-inner">📈</div>
            </div>

            <!-- Inventory Asset Value Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right col-span-2 md:col-span-1">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-emerald-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">أصول المستودعات</span>
                    <h3 class="text-lg font-black text-emerald-650 tracking-tight" dir="ltr">{{ number_format($inventoryAssetValue, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[8px] text-slate-400 block font-medium truncate">قيمة الأصول الحالية</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base shadow-inner">📦</div>
            </div>
        </section>

        <!-- 2. Visual Analytics Section (SVG Charts) -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Line Chart Card -->
            <div class="lg:col-span-2 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-[32px] p-6 space-y-4 shadow-sm text-right">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">مخطط المبيعات اليومي ومؤشر النشاط</h3>
                        <span class="text-xs text-slate-400 font-bold dark:text-slate-500">متابعة بيانية لحظية لسجل العمليات في الفترة المحددة</span>
                    </div>
                    <span class="text-xs bg-amber-500/10 text-amber-600 dark:text-amber-500 px-3 py-1.5 rounded-xl font-black">تحليل زمني مباشر</span>
                </div>
                <div class="w-full h-64">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>

            <!-- Top Selling Items Sidebar -->
            <div class="lg:col-span-1 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 space-y-5 shadow-sm flex flex-col justify-between text-right">
                <div>
                    <h3 class="text-sm font-black text-slate-800">الأصناف الأكثر مبيعاً ورواجاً</h3>
                    <span class="text-xs text-slate-400 font-bold">الوجبات المفضلة للزبائن استناداً للطلبات الجاهزة</span>
                </div>

                <div class="space-y-3.5 flex-grow py-3 flex flex-col justify-center">
                    @forelse($topSelling as $index => $item)
                        <div class="flex items-center justify-between bg-slate-50/60 border border-slate-200/80 p-3 rounded-2xl hover:border-amber-500/30 transition-all duration-300 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-white border border-slate-250 text-xs font-black flex items-center justify-center text-amber-700 shadow-sm"
                                      x-text="{{ $index + 1 }}"></span>
                                <span class="font-extrabold text-xs text-slate-800">{{ $item->name }}</span>
                            </div>
                            <span class="text-[10px] font-black text-amber-600 bg-amber-50 border border-amber-200/50 px-3 py-1 rounded-xl shadow-sm" x-text="'مباع: ' + '{{ $item->total_qty }}'"></span>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 text-center py-6">لا يوجد مبيعات مسجلة في الفترة الحالية.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- 3. Supplier Bank Accounts & Staff Registry Tabbed Panel -->
        <section class="bg-white/80 backdrop-blur-md border border-slate-200/85 rounded-[32px] overflow-hidden shadow-sm text-right">
            <!-- Panel Header with Tab Toggles -->
            <div class="bg-slate-50/80 border-b border-slate-250/60 px-8 py-5 flex flex-col lg:flex-row items-center justify-between gap-4">
                <div class="text-right w-full lg:w-auto">
                    <h2 class="text-sm font-black text-slate-800">بيانات الحسابات البنكية للموردين والموظفين</h2>
                    <span class="text-xs text-slate-400 font-bold block mt-1">تسجيل وتوثيق المعاملات المالية وقائمة موظفي نقاط البيع والمطابخ</span>
                </div>
                
                <!-- Tab Controls -->
                <div class="bg-slate-100 border border-slate-205 p-1.5 rounded-2xl flex flex-wrap gap-1" dir="rtl">
                    <button @click="supplierTab = 'list'"
                            class="px-4 py-2.5 rounded-xl text-xs font-black transition-all"
                            :class="supplierTab === 'list' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'">
                        سجل الموردين
                    </button>
                    <button @click="supplierTab = 'add'"
                            class="px-4 py-2.5 rounded-xl text-xs font-black transition-all"
                            :class="supplierTab === 'add' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'">
                        + إضافة مورد جديد
                    </button>
                    <button @click="supplierTab = 'users'"
                            class="px-4 py-2.5 rounded-xl text-xs font-black transition-all"
                            :class="supplierTab === 'users' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'">
                        سجل الموظفين والورديات
                    </button>
                    <button @click="supplierTab = 'add_user'"
                            class="px-4 py-2.5 rounded-xl text-xs font-black transition-all"
                            :class="supplierTab === 'add_user' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-900'">
                        + إضافة موظف جديد
                    </button>
                </div>
            </div>

            <!-- TAB 1: Supplier List -->
            <div x-show="supplierTab === 'list'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="p-6">
                <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                    <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                        <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4.5 text-right">اسم المورد</th>
                                <th class="px-6 py-4.5 text-right">اسم المصرف</th>
                                <th class="px-6 py-4.5 text-right">رقم الحساب البنكي / IBAN</th>
                                <th class="px-6 py-4.5 text-right">رمز السويفت SWIFT</th>
                                <th class="px-6 py-4.5 text-right">تاريخ التسجيل</th>
                                <th class="px-6 py-4.5 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($suppliers as $supplier)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-black text-slate-800">{{ $supplier->supplier_name }}</td>
                                    <td class="px-6 py-4 flex items-center gap-2 justify-start mt-1">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-[0_0_6px_rgba(245,158,11,0.5)]"></span>
                                        <span class="text-slate-700 font-extrabold">{{ $supplier->bank_name }}</span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-slate-800" dir="ltr">{{ $supplier->account_no }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-400 font-bold">{{ $supplier->swift_code ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-slate-400 font-bold font-mono">{{ $supplier->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="editingSupplier = {{ json_encode($supplier) }}" class="bg-amber-50 hover:bg-amber-500 text-amber-600 hover:text-white border border-amber-100 hover:border-amber-500 text-[10px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                ✏️ تعديل
                                            </button>
                                            <form action="/admin/suppliers/{{ $supplier->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف المورد نهائياً؟');" class="inline-block">
                                                @csrf
                                                <button type="submit" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 text-[10px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                    🗑️ حذف
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-450 font-bold">لا يوجد حسابات موردين مسجلة حالياً. اضغط على "+ إضافة مورد" للتسجيل.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Add Supplier Form -->
            <div x-show="supplierTab === 'add'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="p-6 max-w-2xl mx-auto py-10" style="display: none;">
                <form action="/admin/suppliers" method="POST" class="space-y-6" dir="rtl">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Supplier Name -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">اسم الشركة أو المورد</label>
                            <input type="text" name="supplier_name" required placeholder="مثال: شركة المدينة للمواد الغذائية"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                        </div>

                        <!-- Bank Name -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">اسم المصرف المحلي</label>
                            <input type="text" name="bank_name" required placeholder="مثال: مصرف الجمهورية أو الوحدة"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                        </div>

                        <!-- Account Number -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">رقم الحساب / الآيبان</label>
                            <input type="text" name="account_no" required placeholder="أدخل رقم الحساب الجاري للمورد"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                        </div>

                        <!-- SWIFT Code -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">رمز السويفت SWIFT (اختياري)</label>
                            <input type="text" name="swift_code" placeholder="مثال: JUMHLYXXX"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="submit" class="w-2/3 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-3.5 rounded-2xl shadow-lg shadow-orange-550/15 transition-all text-xs tracking-wider">
                            تسجيل الحساب البنكي للمورد
                        </button>
                        <button type="button" @click="supplierTab = 'list'" class="w-1/3 bg-slate-100 hover:bg-slate-200 font-black py-3.5 rounded-2xl transition-all text-xs text-slate-700 border border-slate-200">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 3: Users/Staff List -->
            <div x-show="supplierTab === 'users'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="p-6" style="display: none;">
                <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                    <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                        <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4.5 text-right">اسم الموظف</th>
                                <th class="px-6 py-4.5 text-right">البريد الإلكتروني</th>
                                <th class="px-6 py-4.5 text-right">الصلاحية والوظيفة</th>
                                <th class="px-6 py-4.5 text-right">تاريخ التسجيل</th>
                                <th class="px-6 py-4.5 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($users as $user)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-black text-slate-800">{{ $user->name }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-750 font-semibold" dir="ltr">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="text-[8px] font-black uppercase px-2.5 py-0.5 rounded-md border
                                            @if($user->role === 'admin') bg-rose-500/10 text-rose-550 border-rose-500/20
                                            @elseif($user->role === 'chef') bg-emerald-500/10 text-emerald-500 border-emerald-500/20
                                            @else bg-blue-500/10 text-blue-500 border-blue-500/20 @endif">
                                            @if($user->role === 'admin') مدير النظام (Admin)
                                            @elseif($user->role === 'chef') طاهي المطبخ (Chef)
                                            @else كاشير الصالة (Cashier) @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 font-bold font-mono">{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @if($user->id !== auth()->id())
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="editingUser = {{ json_encode($user) }}" class="bg-amber-50 hover:bg-amber-500 text-amber-600 hover:text-white border border-amber-100 hover:border-amber-500 text-[10px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                    ✏️ تعديل
                                                </button>
                                                <form action="/admin/users/{{ $user->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم نهائياً؟');" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 text-[10px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                        🗑️ حذف
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-slate-450 italic font-bold">حسابك الحالي</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: Add User Form -->
            <div x-show="supplierTab === 'add_user'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="p-6 max-w-2xl mx-auto py-10" style="display: none;">
                <form action="/admin/users" method="POST" class="space-y-6" dir="rtl">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Employee Name -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">اسم الموظف بالكامل</label>
                            <input type="text" name="name" required placeholder="مثال: أحمد علي"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                        </div>

                        <!-- Email -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">البريد الإلكتروني (لتسجيل الدخول)</label>
                            <input type="email" name="email" required placeholder="example@pos.ly"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">كلمة المرور</label>
                            <input type="password" name="password" required placeholder="أدخل 6 خانات على الأقل"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                        </div>

                        <!-- Role -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">الصلاحية والوظيفة</label>
                            <select name="role" required class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-sm text-slate-850 focus:outline-none transition-all duration-300 text-right shadow-sm">
                                <option value="cashier">كاشير صالة (Cashier)</option>
                                <option value="chef">طاهي المطبخ (Chef)</option>
                                <option value="admin">مدير نظام كامل (Admin)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="submit" class="w-2/3 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-3.5 rounded-2xl shadow-lg shadow-orange-550/15 transition-all text-xs tracking-wider">
                            تسجيل الموظف الجديد
                        </button>
                        <button type="button" @click="supplierTab = 'users'" class="w-1/3 bg-slate-100 hover:bg-slate-200 font-black py-3.5 rounded-2xl transition-all text-xs text-slate-700 border border-slate-200">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Accounting & Sales Breakdown -->
        <section class="space-y-6" dir="rtl">

            <!-- 2. Side-by-Side Breakdown Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Payment Distribution (Doughnut Chart + stats) -->
                <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-[32px] p-6 space-y-6 shadow-sm text-right">
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">توزيع الدخل حسب طريقة الدفع</h3>
                        <span class="text-xs text-slate-400 font-bold dark:text-slate-500">حصة المبيعات النقدية والمبيعات الإلكترونية المحلية</span>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="w-36 h-36 flex-shrink-0">
                            <canvas id="paymentMethodsChart"></canvas>
                        </div>
                        <div class="grid grid-cols-2 gap-3 flex-grow w-full">
                            <!-- Cash -->
                            <div class="bg-slate-50/80 dark:bg-slate-950/50 border border-slate-200/50 dark:border-slate-800/80 p-3 rounded-2xl flex items-center justify-between shadow-sm">
                                <span class="text-2xl">💵</span>
                                <div class="space-y-0.5 text-left">
                                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 block text-right">كاش (نقدًا)</span>
                                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-500" dir="ltr">{{ number_format($salesByPayment['cash'] ?? 0, 2) }} <span class="text-[8px]">د.ل</span></span>
                                </div>
                            </div>
                            <!-- Sadad -->
                            <div class="bg-slate-50/80 dark:bg-slate-950/50 border border-slate-200/50 dark:border-slate-800/80 p-3 rounded-2xl flex items-center justify-between shadow-sm">
                                <span class="text-2xl">📱</span>
                                <div class="space-y-0.5 text-left">
                                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 block text-right">سداد</span>
                                    <span class="text-xs font-black text-amber-600 dark:text-amber-500" dir="ltr">{{ number_format($salesByPayment['sadad'] ?? 0, 2) }} <span class="text-[8px]">د.ل</span></span>
                                </div>
                            </div>
                            <!-- MobiCash -->
                            <div class="bg-slate-50/80 dark:bg-slate-950/50 border border-slate-200/50 dark:border-slate-800/80 p-3 rounded-2xl flex items-center justify-between shadow-sm">
                                <span class="text-2xl">💳</span>
                                <div class="space-y-0.5 text-left">
                                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 block text-right">موبي كاش</span>
                                    <span class="text-xs font-black text-blue-600 dark:text-blue-500" dir="ltr">{{ number_format($salesByPayment['mobicash'] ?? 0, 2) }} <span class="text-[8px]">د.ل</span></span>
                                </div>
                            </div>
                            <!-- Tadawul -->
                            <div class="bg-slate-50/80 dark:bg-slate-950/50 border border-slate-200/50 dark:border-slate-800/80 p-3 rounded-2xl flex items-center justify-between shadow-sm">
                                <span class="text-2xl">🖥️</span>
                                <div class="space-y-0.5 text-left">
                                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 block text-right">تداول</span>
                                    <span class="text-xs font-black text-purple-650 dark:text-purple-500" dir="ltr">{{ number_format($salesByPayment['tadawul'] ?? 0, 2) }} <span class="text-[8px]">د.ل</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Locations comparison Chart -->
                <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-[32px] p-6 space-y-4 shadow-sm text-right">
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">مبيعات الفروع ونقاط البيع</h3>
                        <span class="text-xs text-slate-400 font-bold dark:text-slate-500">حجم مبيعات كل فرع ونسبة مساهمته في الإيراد العام</span>
                    </div>
                    <div class="w-full h-44">
                        <canvas id="locationsChart"></canvas>
                    </div>
                </div>
            </div>
        </section>



    <!-- 4. Professional Financial Statement Report Modal -->
    <div class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm z-50 flex items-center justify-center p-6 print:p-0 print:bg-white print:relative" 
         x-show="showReportModal" style="display: none;" x-transition>
        <!-- Modal Backdrop for exit on screen -->
        <div class="absolute inset-0 print:hidden" @click="showReportModal = false"></div>

        <!-- Modal Container -->
        <div class="bg-white border border-slate-200 rounded-[32px] max-w-3xl w-full p-8 shadow-2xl relative z-10 flex flex-col justify-between max-h-[90vh] overflow-y-auto print:max-h-none print:w-full print:border-none print:shadow-none print:p-0 print:static print:bg-white print:text-black">
            
            <!-- Modal Body (Printable Area) -->
            <div id="printable-financial-statement" class="space-y-6 print:bg-white print:text-black text-right" dir="rtl">
                <!-- Report Header -->
                <div class="flex justify-between items-start border-b border-slate-200 print:border-black pb-4 text-right">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 print:text-black">مطعم المدينة المنورة - تقرير الأرباح والخسائر</h2>
                        <p class="text-[10px] text-amber-600 print:text-gray-700 font-extrabold uppercase tracking-wider mt-1">تقرير قائمة الدخل الجردي للمبيعات والمخزن • Income Statement</p>
                    </div>
                    <div class="text-left text-[9px] text-slate-450 print:text-black font-mono" dir="ltr">
                        <p>تاريخ الاستخراج: {{ date('Y-m-d H:i:s') }}</p>
                        <p>تغطية التقرير: {{ $startDate->format('Y-m-d') }} إلى {{ $endDate->format('Y-m-d') }}</p>
                        <p>العملة الحسابية: الدينار الليبي (د.ل)</p>
                    </div>
                </div>

                <!-- Financial Statement Table -->
                <div class="space-y-4">
                    <table class="w-full text-right text-xs print:text-black" dir="rtl">
                        <tbody>
                            <!-- Revenue -->
                            <tr class="border-b border-slate-100 print:border-gray-300">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">إجمالي مبيعات الصالة والدليفري (Gross Sales)</td>
                                <td class="py-2.5 text-left font-black text-slate-800 print:text-black" dir="ltr">{{ number_format($salesTotal - $taxTotal + $discountTotal, 2) }} د.ل</td>
                            </tr>
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-500 print:text-gray-750">
                                <td class="py-2 pr-4">يخصم: الخصومات والعروض الترويجية الممنوحة (Discounts)</td>
                                <td class="py-2 text-left font-bold text-rose-600 print:text-black" dir="ltr">-{{ number_format($discountTotal, 2) }} د.ل</td>
                            </tr>
                            <tr class="border-b border-slate-200 print:border-black font-black bg-slate-50 print:bg-gray-100">
                                <td class="py-2.5 text-slate-800 print:text-black">صافي إيراد المبيعات (Net Revenue)</td>
                                <td class="py-2.5 text-left text-amber-600 print:text-black" dir="ltr">{{ number_format($salesTotal - $taxTotal, 2) }} د.ل</td>
                            </tr>

                            <!-- COGS -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-650">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">يخصم: تكلفة الأغذية المستهلكة في الوجبات (COGS)</td>
                                <td class="py-2.5 text-left font-bold text-rose-600 print:text-black" dir="ltr">-{{ number_format($totalCogs, 2) }} د.ل</td>
                            </tr>

                            <!-- Inventory Losses (Waste) -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-650">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">يخصم: تكلفة العجز وهدر وتالف المخازن (Waste)</td>
                                <td class="py-2.5 text-left font-bold text-rose-600 print:text-black" dir="ltr">-{{ number_format($totalWasteCost, 2) }} د.ل</td>
                            </tr>

                            <!-- Inventory Adjustments (Surplus) -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-650">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">يضاف: فروقات وتسويات الجرد الفائضة (Stock Adjustments)</td>
                                <td class="py-2.5 text-left font-bold text-emerald-600 print:text-black" dir="ltr">+{{ number_format($totalAdjustmentSurplus, 2) }} د.ل</td>
                            </tr>

                            <!-- Gross Profit -->
                            <tr class="border-b border-slate-200 print:border-black font-black bg-slate-50 print:bg-gray-100">
                                <td class="py-2.5 text-slate-800 print:text-black">إجمالي الأرباح التشغيلية للمبيعات (Gross Profit)</td>
                                <td class="py-2.5 text-left text-amber-600 print:text-black" dir="ltr">{{ number_format($grossProfit, 2) }} د.ل</td>
                            </tr>

                            <!-- Operational Expenses Header -->
                            <tr class="bg-slate-100/50 print:bg-gray-200 font-black border-b border-slate-200">
                                <td colspan="2" class="py-2 text-[10px] text-slate-500 uppercase tracking-wider">المصاريف النثرية والتشغيلية الإضافية الشهرية (Expenses)</td>
                            </tr>

                            <!-- Rent Input -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-650">
                                <td class="py-2 pr-4">🏢 إيجار مقار الفروع والمطاعم الشهري</td>
                                <td class="py-2 text-left font-semibold print:hidden">
                                    <input type="number" x-model.number="rent" min="0" placeholder="0.00" class="w-28 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-center text-slate-800 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 text-xs" />
                                </td>
                                <td class="py-2 text-left font-semibold hidden print:table-cell" x-text="(parseFloat(rent) || 0).toFixed(2) + ' د.ل'" dir="ltr"></td>
                            </tr>

                            <!-- Salaries Input -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-650">
                                <td class="py-2 pr-4">👥 مرتبات ومكافآت العاملين والموظفين</td>
                                <td class="py-2 text-left font-semibold print:hidden">
                                    <input type="number" x-model.number="salaries" min="0" placeholder="0.00" class="w-28 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-center text-slate-800 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 text-xs" />
                                </td>
                                <td class="py-2 text-left font-semibold hidden print:table-cell" x-text="(parseFloat(salaries) || 0).toFixed(2) + ' د.ل'" dir="ltr"></td>
                            </tr>

                            <!-- Utilities Input -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-650">
                                <td class="py-2 pr-4">⚡ فواتير المرافق (كهرباء، مياه، اتصالات وإنترنت)</td>
                                <td class="py-2 text-left font-semibold print:hidden">
                                    <input type="number" x-model.number="utilities" min="0" placeholder="0.00" class="w-28 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-center text-slate-800 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 text-xs" />
                                </td>
                                <td class="py-2 text-left font-semibold hidden print:table-cell" x-text="(parseFloat(utilities) || 0).toFixed(2) + ' د.ل'" dir="ltr"></td>
                            </tr>

                            <!-- Misc Input -->
                            <tr class="border-b border-slate-250 print:border-black text-slate-650">
                                <td class="py-2 pr-4">📦 مصاريف أخرى وصيانة نثرية دورية</td>
                                <td class="py-2 text-left font-semibold print:hidden">
                                    <input type="number" x-model.number="misc" min="0" placeholder="0.00" class="w-28 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-center text-slate-800 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 text-xs" />
                                </td>
                                <td class="py-2 text-left font-semibold hidden print:table-cell" x-text="(parseFloat(misc) || 0).toFixed(2) + ' د.ل'" dir="ltr"></td>
                            </tr>

                            <!-- Net Profit/Loss -->
                            <tr class="border-b-2 border-slate-400 print:border-black font-black bg-amber-500/10 text-amber-900 print:bg-gray-200 print:text-black">
                                <td class="py-4 text-xs font-black">صافي الربح المالي النهائي المطابق (Net Income)</td>
                                <td class="py-4 text-left text-xs font-black" x-text="({{ $grossProfit }} - (parseFloat(rent) || 0) - (parseFloat(salaries) || 0) - (parseFloat(utilities) || 0) - (parseFloat(misc) || 0)).toFixed(2) + ' د.ل'" dir="ltr"></td>
                            </tr>
                            
                            <!-- Additional items (Tax, Assets) -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-slate-450 print:text-gray-700">
                                <td class="py-2.5 pr-4">الضرائب المحصلة الممسوكة بالعهدة (Sales Taxes)</td>
                                <td class="py-2.5 text-left font-bold" dir="ltr">{{ number_format($taxTotal, 2) }} د.ل</td>
                            </tr>
                            <tr class="border-b border-slate-200 print:border-black text-slate-450 print:text-gray-700">
                                <td class="py-2.5 pr-4">قيمة المواد والأغذية المتبقية بمخازن الفروع (End Assets)</td>
                                <td class="py-2.5 text-left font-bold text-emerald-600 print:text-black" dir="ltr">{{ number_format($inventoryAssetValue, 2) }} د.ل</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary Signature -->
                <div class="grid grid-cols-2 gap-6 pt-6 border-t border-slate-200 print:border-black text-[9px] text-slate-400 print:text-black text-right">
                    <div class="space-y-1">
                        <p class="font-bold">أعد بواسطة / Prepared By:</p>
                        <p class="text-xs font-black text-slate-800 print:text-black">{{ auth()->user()->name }} ({{ auth()->user()->role }})</p>
                    </div>
                    <div class="space-y-1 text-left">
                        <p class="font-bold">التوقيع والختم المعتمد / Authorized Stamp:</p>
                        <div class="h-8 border-b border-slate-350 border-dashed w-36 ml-auto print:border-black"></div>
                    </div>
                </div>
            </div>

            <!-- Action buttons (Screen Only) -->
            <div class="flex gap-3 mt-8 print:hidden">
                <button @click="showReportModal = false" class="w-1/4 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-black py-3.5 rounded-2xl text-xs tracking-wider transition-all">
                    إغلاق النافذة
                </button>
                <button @click="exportAdminReportToCSV(rent, salaries, utilities, misc)" class="w-2/5 bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3.5 rounded-2xl text-xs tracking-wider transition-all shadow-lg shadow-emerald-500/15">
                    📥 تصدير ملف Excel/CSV
                </button>
                <button @click="window.print()" class="w-2/5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-3.5 rounded-2xl text-xs tracking-wider transition-all shadow-lg shadow-orange-550/15">
                    🖨️ طباعة وتصدير كشف الأرباح والخسائر
                </button>
            </div>
        </div> <!-- closes Modal Container -->
    </div> <!-- closes fixed outer -->
        <!-- Edit Supplier Modal -->
        <div x-show="editingSupplier !== null"
             x-transition.opacity
             class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="editingSupplier = null"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-md p-6 shadow-2xl relative">
                
                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-4">تعديل بيانات المورد</h3>
                
                <form :action="'/admin/suppliers/' + editingSupplier?.id + '/update'" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">اسم المورد</label>
                        <input type="text" name="supplier_name" :value="editingSupplier?.supplier_name" required
                               class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none" />
                    </div>

                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">اسم المصرف</label>
                        <input type="text" name="bank_name" :value="editingSupplier?.bank_name" required
                               class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none" />
                    </div>

                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">رقم الحساب</label>
                        <input type="text" name="account_no" :value="editingSupplier?.account_no" required
                               class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none" />
                    </div>

                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">رمز السويفت</label>
                        <input type="text" name="swift_code" :value="editingSupplier?.swift_code"
                               class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none" />
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-white font-black py-3 rounded-xl transition-all">حفظ التعديلات</button>
                        <button type="button" @click="editingSupplier = null" class="w-1/3 bg-slate-200 hover:bg-slate-300 text-slate-800 font-black py-3 rounded-xl transition-all">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div x-show="editingUser !== null"
             x-transition.opacity
             class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="editingUser = null"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-md p-6 shadow-2xl relative">
                
                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-4">تعديل بيانات الموظف</h3>
                
                <form :action="'/admin/users/' + editingUser?.id + '/update'" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">اسم الموظف</label>
                        <input type="text" name="name" :value="editingUser?.name" required
                               class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none" />
                    </div>

                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">البريد الإلكتروني</label>
                        <input type="email" name="email" :value="editingUser?.email" required
                               class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none" />
                    </div>

                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">كلمة المرور (اختياري)</label>
                        <input type="password" name="password" placeholder="اتركه فارغاً لعدم التغيير"
                               class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none" />
                    </div>

                    <div class="space-y-1.5 text-right">
                        <label class="text-xs text-slate-500 font-bold">الصلاحية</label>
                        <select name="role" :value="editingUser?.role" required
                                class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none">
                            <option value="cashier">كاشير صالة (Cashier)</option>
                            <option value="chef">طاهي المطبخ (Chef)</option>
                            <option value="admin">مدير نظام كامل (Admin)</option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-white font-black py-3 rounded-xl transition-all">حفظ التعديلات</button>
                        <button type="button" @click="editingUser = null" class="w-1/3 bg-slate-200 hover:bg-slate-300 text-slate-800 font-black py-3 rounded-xl transition-all">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
</main>
</div>
    <!-- Chart.js rendering scripts -->
    <script>
        function initAdminCharts() {
            try {
                const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.03)';
                const textColor = isDark ? '#94a3b8' : '#64748b';

                // Generate trend gradient
                const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 250);
                trendGradient.addColorStop(0, 'rgba(245, 158, 11, 0.25)'); // Amber glow
                trendGradient.addColorStop(1, 'rgba(245, 158, 11, 0.0)');

                const salesTrendData = @json($salesTrend);
                const trendLabels = Object.keys(salesTrendData).length > 0 ? Object.keys(salesTrendData) : ['لا يوجد بيانات'];
                const trendValues = Object.keys(salesTrendData).length > 0 ? Object.values(salesTrendData) : [0];

                const trendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'المبيعات اليومية',
                            data: trendValues,
                            borderColor: '#f59e0b',
                            borderWidth: 3,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#ffffff',
                            pointHoverRadius: 6,
                            pointRadius: 4,
                            fill: true,
                            backgroundColor: trendGradient,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                rtl: true,
                                backgroundColor: isDark ? '#1e293b' : '#ffffff',
                                titleColor: isDark ? '#ffffff' : '#0f172a',
                                bodyColor: isDark ? '#ffffff' : '#0f172a',
                                borderColor: '#f59e0b',
                                borderWidth: 1,
                                padding: 10,
                                bodyFont: { family: 'Cairo' },
                                titleFont: { family: 'Cairo' },
                                callbacks: {
                                    label: function(context) {
                                        return ' ' + context.raw.toFixed(2) + ' د.ل';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor, font: { family: 'Cairo', size: 10 } }
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: { color: textColor, font: { family: 'Cairo', size: 10 } }
                            }
                        }
                    }
                });

                // 2. Payment Methods Doughnut Chart
                const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
                const paymentValues = [
                    {{ $salesByPayment['cash'] ?? 0 }},
                    {{ $salesByPayment['sadad'] ?? 0 }},
                    {{ $salesByPayment['mobicash'] ?? 0 }},
                    {{ $salesByPayment['tadawul'] ?? 0 }}
                ];
                
                const paymentChart = new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['نقدًا (كاش)', 'سداد', 'موبي كاش', 'تداول'],
                        datasets: [{
                            data: paymentValues,
                            backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6'],
                            borderWidth: isDark ? 2 : 0,
                            borderColor: '#1e293b',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                rtl: true,
                                bodyFont: { family: 'Cairo' },
                                callbacks: {
                                    label: function(context) {
                                        return ' ' + context.label + ': ' + context.raw.toFixed(2) + ' د.ل';
                                    }
                                }
                            }
                        }
                    }
                });

                // 3. Locations Bar Chart
                const locCtx = document.getElementById('locationsChart').getContext('2d');
                const locData = @json($salesByLocation);
                const locLabels = Object.keys(locData).length > 0 ? Object.keys(locData) : ['لا يوجد فروع'];
                const locValues = Object.keys(locData).length > 0 ? Object.values(locData) : [0];

                const locChart = new Chart(locCtx, {
                    type: 'bar',
                    data: {
                        labels: locLabels,
                        datasets: [{
                            data: locValues,
                            backgroundColor: '#f59e0b',
                            borderRadius: 8,
                            hoverBackgroundColor: '#d97706'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                rtl: true,
                                bodyFont: { family: 'Cairo' },
                                callbacks: {
                                    label: function(context) {
                                        return ' ' + context.raw.toFixed(2) + ' د.ل';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor },
                                ticks: { color: textColor, font: { family: 'Cairo', size: 10 } }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: textColor, font: { family: 'Cairo', size: 10 } }
                            }
                        }
                    }
                });

                // Listen to theme change to update colors dynamically
                window.addEventListener('theme-changed', (e) => {
                    const isDark = e.detail.isDark;
                    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.03)';
                    const textColor = isDark ? '#94a3b8' : '#64748b';

                    [trendChart, locChart].forEach(chart => {
                        chart.options.scales.x.ticks.color = textColor;
                        chart.options.scales.y.ticks.color = textColor;
                        if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor;
                        if (chart.options.scales.x.grid) chart.options.scales.x.grid.color = gridColor;
                    });
                    
                    paymentChart.data.datasets[0].borderWidth = isDark ? 2 : 0;
                    
                    trendChart.update();
                    paymentChart.update();
                    locChart.update();
                });
            } catch (err) {
                console.error("Failed to render Chart.js analytics: ", err);
            }
        }

        // Wait for Chart.js to load (it's async now)
        if (window.__chartJsLoaded || typeof Chart !== 'undefined') {
            // Chart.js already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAdminCharts);
            } else {
                initAdminCharts();
            }
        } else {
            // Wait for the async script to finish loading
            window.addEventListener('chartjs-ready', () => {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initAdminCharts);
                } else {
                    initAdminCharts();
                }
            });
        }

        function exportAdminReportToCSV(rentVal, salariesVal, utilitiesVal, miscVal) {
            const rent = parseFloat(rentVal) || 0;
            const salaries = parseFloat(salariesVal) || 0;
            const utilities = parseFloat(utilitiesVal) || 0;
            const misc = parseFloat(miscVal) || 0;
            
            const grossSales = {{ $salesTotal - $taxTotal + $discountTotal }};
            const discounts = {{ $discountTotal }};
            const netRevenue = {{ $salesTotal - $taxTotal }};
            const cogs = {{ $totalCogs }};
            const waste = {{ $totalWasteCost }};
            const adjustments = {{ $totalAdjustmentSurplus }};
            const grossProfit = {{ $grossProfit }};
            const tax = {{ $taxTotal }};
            const assets = {{ $inventoryAssetValue }};
            
            const totalExpenses = rent + salaries + utilities + misc;
            const netIncome = grossProfit - totalExpenses;
            
            const rows = [
                ["البند", "القيمة (د.ل)"],
                ["إجمالي مبيعات الصالة والدليفري (Gross Sales)", grossSales.toFixed(2)],
                ["الخصومات والعروض الترويجية الممنوحة (Discounts)", (-discounts).toFixed(2)],
                ["صافي إيراد المبيعات (Net Revenue)", netRevenue.toFixed(2)],
                ["تكلفة الأغذية المستهلكة (COGS)", (-cogs).toFixed(2)],
                ["تكلفة عجز وهدر وتالف المخازن (Waste)", (-waste).toFixed(2)],
                ["تسويات الجرد الفائضة (Stock Adjustments)", adjustments.toFixed(2)],
                ["إجمالي الأرباح التشغيلية (Gross Profit)", grossProfit.toFixed(2)],
                ["إيجار مقار الفروع والمطاعم الشهري (Rent)", (-rent).toFixed(2)],
                ["مرتبات ومكافآت العاملين والموظفين (Salaries)", (-salaries).toFixed(2)],
                ["فواتير المرافق (Utilities)", (-utilities).toFixed(2)],
                ["مصاريف أخرى وصيانة نثرية (Misc)", (-misc).toFixed(2)],
                ["إجمالي المصاريف التشغيلية (Total Expenses)", (-totalExpenses).toFixed(2)],
                ["صافي الربح المالي النهائي (Net Income)", netIncome.toFixed(2)],
                ["الضرائب المحصلة بالعهدة (Sales Taxes)", tax.toFixed(2)],
                ["قيمة المواد والأغذية المتبقية بمخازن الفروع (End Assets)", assets.toFixed(2)],
                [],
                ["تاريخ استخراج التقرير", "{{ date('Y-m-d H:i:s') }}"],
                ["فترة التقرير", "من {{ $startDate->format('Y-m-d') }} إلى {{ $endDate->format('Y-m-d') }}"]
            ];
            
            let csvContent = "\uFEFF";
            rows.forEach(row => {
                const rowStr = row.map(val => {
                    if (typeof val === "string") {
                        return `"${val.replace(/"/g, '""')}"`;
                    }
                    return val;
                }).join(",");
                csvContent += rowStr + "\r\n";
            });
            
            const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `تقرير_الأرباح_والخسائر_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
