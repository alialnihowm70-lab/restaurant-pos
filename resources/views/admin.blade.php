<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Madina POS - Admin Dashboard</title>
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
<body class="min-h-screen flex">

    <!-- Unified Left Sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace Area -->
    <div class="flex-grow flex flex-col min-h-screen overflow-y-auto">
        <!-- Top bar inside content area -->
        <header class="bg-white border-b border-slate-200 px-8 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 flex-shrink-0">
            <div>
                <h1 class="text-xl font-black text-slate-800 flex items-center gap-2">
                    <span>📊</span> Management Dashboard (لوحة الإدارة والتحليلات)
                </h1>
                <span class="text-xs text-slate-400 font-medium mt-1 block">Real-time statistics, top-selling items, and supplier bank records</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <form action="/admin" method="GET" class="flex items-center gap-3 bg-slate-50 border border-slate-200 p-1.5 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-1.5 px-2">
                        <span class="text-[10px] text-slate-450 font-bold uppercase">From:</span>
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="bg-transparent text-xs font-bold text-slate-700 focus:outline-none" />
                    </div>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-1.5 px-2">
                        <span class="text-[10px] text-slate-450 font-bold uppercase">To:</span>
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="bg-transparent text-xs font-bold text-slate-700 focus:outline-none" />
                    </div>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-[10px] font-bold px-3 py-1.5 rounded-xl transition-colors">
                        Filter (تصفية)
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="/admin" class="text-rose-600 hover:text-rose-700 text-[10px] font-bold px-2">Clear</a>
                    @endif
                </form>
                <button @click="showReportModal = true" class="bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-lg shadow-amber-500/10 flex items-center gap-1.5">
                    💼 GENERATE REPORT (إصدار تقرير مالي)
                </button>
            </div>
        </header>

        <main class="p-8 space-y-10" x-data="{ supplierTab: 'list', showReportModal: false, rent: '', salaries: '', utilities: '', misc: '' }">
        
        <!-- Flash Alert Notification -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-750 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse">
                <span>❌</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Low Stock Alert Banner -->
        @if(isset($lowStockIngredients) && count($lowStockIngredients) > 0)
            <div class="bg-amber-50 border border-amber-250 text-amber-900 p-5 rounded-3xl text-xs font-bold flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="text-2xl animate-bounce">⚠️</span>
                    <div>
                        <span class="font-extrabold text-slate-800 text-sm block">تنبيه: نواقص في مخزون المواد الخام!</span>
                        <span class="text-slate-500 font-semibold block mt-1">يوجد عدد {{ count($lowStockIngredients) }} مكونات أساسية تحت حد الطلب الأدنى حالياً. يرجى توريدها لضمان استمرارية التشغيل.</span>
                    </div>
                </div>
                <a href="/admin/inventory" class="bg-amber-500 hover:bg-amber-600 text-slate-950 px-4 py-2.5 rounded-xl transition-all shadow-md shadow-amber-500/10 font-extrabold flex items-center gap-1.5 self-end md:self-auto">
                    📦 مراجعة وجرد المخزن
                </a>
            </div>
        @endif

        <!-- 1. Real-Time Accounting Analytics Cards -->
        <section class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <!-- Total Revenue Card -->
            <div class="bg-white border border-slate-200 rounded-3xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="space-y-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Sales Revenue (المبيعات)</span>
                    <h3 class="text-lg font-black text-blue-600 tracking-tight">{{ number_format($salesTotal, 2) }} <span class="text-[9px] font-bold text-slate-400">LYD</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">Gross receipts in period</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-inner">💵</div>
            </div>

            <!-- Cost of Goods Sold Card -->
            <div class="bg-white border border-slate-200 rounded-3xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-tr from-red-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="space-y-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Food Cost (التكلفة COGS)</span>
                    <h3 class="text-lg font-black text-red-650 tracking-tight">{{ number_format($totalCogs, 2) }} <span class="text-[9px] font-bold text-slate-400">LYD</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">Derived from recipe utilization</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg shadow-inner">🥩</div>
            </div>

            <!-- Inventory Loss/Waste Card -->
            <div class="bg-white border border-slate-200 rounded-3xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-tr from-rose-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="space-y-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Waste / Losses (الفقد والتالف)</span>
                    <h3 class="text-lg font-black text-rose-650 tracking-tight">{{ number_format($totalWasteCost, 2) }} <span class="text-[9px] font-bold text-slate-400">LYD</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">Stocktake differences cost</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shadow-inner">🗑️</div>
            </div>

            <!-- Net Profit Card -->
            <div class="bg-white border border-slate-200 rounded-3xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-tr from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="space-y-2">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Net Profit (الربح)</span>
                        <span class="text-[9px] font-bold bg-amber-550 text-amber-700 border border-amber-250 px-1 py-0.25 rounded">{{ number_format($profitMargin, 1) }}%</span>
                    </div>
                    <h3 class="text-lg font-black text-amber-600 tracking-tight">{{ number_format($grossProfit, 2) }} <span class="text-[9px] font-bold text-slate-400">LYD</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">Revenues minus costs</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-inner">📈</div>
            </div>

            <!-- Inventory Asset Value Card -->
            <div class="bg-white border border-slate-200 rounded-3xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="space-y-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Inventory Value (أصول المخازن)</span>
                    <h3 class="text-lg font-black text-emerald-600 tracking-tight">{{ number_format($inventoryAssetValue, 2) }} <span class="text-[9px] font-bold text-slate-400">LYD</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">Remaining stock asset valuation</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-inner">📦</div>
            </div>
        </section>

        <!-- 2. Visual Analytics Section (SVG Charts) -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Line Chart Card -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-3xl p-6 space-y-4 shadow-sm">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Sales Trend & Peak Activity</h3>
                    <span class="text-xs text-slate-400 font-medium">Monitored transaction logs for the current session</span>
                </div>
                <!-- Clean Inline SVG Chart -->
                <div class="w-full h-44 bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-center relative">
                    <svg viewBox="0 0 500 150" class="w-full h-full overflow-visible">
                        <defs>
                            <linearGradient id="salesGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.15"/>
                                <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <!-- Grid Lines -->
                        <line x1="0" y1="30" x2="500" y2="30" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>
                        <line x1="0" y1="75" x2="500" y2="75" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>
                        <line x1="0" y1="120" x2="500" y2="120" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>
                        
                        <!-- Gradient Area -->
                        <path d="M 0 130 Q 80 80 150 110 T 300 40 T 450 70 L 500 70 L 500 140 L 0 140 Z" fill="url(#salesGrad)"/>
                        
                        <!-- Trend Line -->
                        <path d="M 0 130 Q 80 80 150 110 T 300 40 T 450 70 L 500 70" fill="none" stroke="#f59e0b" stroke-width="3.5" stroke-linecap="round"/>
                        
                        <!-- Chart points -->
                        <circle cx="150" cy="110" r="5" fill="#f59e0b" stroke="#ffffff" stroke-width="2"/>
                        <circle cx="300" cy="40" r="5" fill="#f59e0b" stroke="#ffffff" stroke-width="2"/>
                        <circle cx="450" cy="70" r="5" fill="#f59e0b" stroke="#ffffff" stroke-width="2"/>
                    </svg>
                    <!-- Floating Labels -->
                    <div class="absolute bottom-2 left-6 text-[10px] font-bold text-slate-400">Morning</div>
                    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 text-[10px] font-bold text-amber-600">Lunch Peak</div>
                    <div class="absolute bottom-2 right-6 text-[10px] font-bold text-slate-400">Evening</div>
                </div>
            </div>

            <!-- Top Selling Items Sidebar -->
            <div class="lg:col-span-1 bg-white border border-slate-200 rounded-3xl p-6 space-y-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Top-Selling Menu Items</h3>
                    <span class="text-xs text-slate-400 font-medium">Popular dishes matching KDS status completed</span>
                </div>

                <div class="space-y-4 flex-grow py-4 flex flex-col justify-center">
                    @forelse($topSelling as $index => $item)
                        <div class="flex items-center justify-between bg-slate-50 border border-slate-200 p-3 rounded-2xl hover:border-amber-500/30 transition-all duration-300">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-xl bg-slate-100 border border-slate-200 text-xs font-black flex items-center justify-center text-amber-700"
                                      x-text="{{ $index + 1 }}"></span>
                                <span class="font-bold text-sm text-slate-800">{{ $item->name }}</span>
                            </div>
                            <span class="text-xs font-black text-slate-500 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg" x-text="'{{ $item->total_qty }} sold'"></span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400 text-center py-6">No sales recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- 3. Supplier Bank Accounts & Staff Registry Tabbed Panel -->
        <section class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
            <!-- Panel Header with Tab Toggles -->
            <div class="bg-slate-50 border-b border-slate-200 px-8 py-5 flex flex-col lg:flex-row items-center justify-between gap-4">
                <div class="text-right w-full lg:w-auto">
                    <h2 class="text-base font-bold text-slate-800">بيانات الحسابات والموظفين بالمنظومة</h2>
                    <span class="text-xs text-slate-400 font-medium block mt-1">إدارة الحسابات البنكية للموردين، وقائمة حسابات وصلاحيات الموظفين</span>
                </div>
                
                <!-- Tab Controls -->
                <div class="bg-slate-100 border border-slate-200 p-1.5 rounded-2xl flex flex-wrap gap-1">
                    <button @click="supplierTab = 'list'"
                            class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="supplierTab === 'list' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-650 hover:text-slate-900'">
                        سجل الموردين
                    </button>
                    <button @click="supplierTab = 'add'"
                            class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="supplierTab === 'add' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-650 hover:text-slate-900'">
                        + إضافة مورد
                    </button>
                    <button @click="supplierTab = 'users'"
                            class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="supplierTab === 'users' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-650 hover:text-slate-900'">
                        إدارة الموظفين
                    </button>
                    <button @click="supplierTab = 'add_user'"
                            class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="supplierTab === 'add_user' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-650 hover:text-slate-900'">
                        + إضافة موظف
                    </button>
                </div>
            </div>

            <!-- TAB 1: Supplier List -->
            <div x-show="supplierTab === 'list'" class="p-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-right">اسم المورد</th>
                                <th class="px-6 py-4 text-right">اسم المصرف</th>
                                <th class="px-6 py-4 text-right">رقم الحساب البنكي / IBAN</th>
                                <th class="px-6 py-4 text-right">رمز السويفت SWIFT</th>
                                <th class="px-6 py-4 text-right">تاريخ التسجيل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($suppliers as $supplier)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ $supplier->supplier_name }}</td>
                                    <td class="px-6 py-4 flex items-center gap-2 justify-start">
                                        <span class="w-2 h-2 rounded-full bg-amber-500 shadow shadow-amber-500/30"></span>
                                        <span class="text-slate-700 font-medium">{{ $supplier->bank_name }}</span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-slate-800">{{ $supplier->account_no }}</td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-400">{{ $supplier->swift_code ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-xs text-slate-400 font-mono">{{ $supplier->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-450">لا يوجد حسابات موردين مسجلة حالياً. اضغط على "+ إضافة مورد" للتسجيل.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Add Supplier Form -->
            <div x-show="supplierTab === 'add'" class="p-6 max-w-2xl mx-auto py-10" style="display: none;">
                <form action="/admin/suppliers" method="POST" class="space-y-6" dir="rtl">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Supplier Name -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">اسم الشركة أو المورد</label>
                            <input type="text" name="supplier_name" required placeholder="مثال: شركة المدينة للمواد الغذائية"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <!-- Bank Name -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">اسم المصرف (المحلي)</label>
                            <input type="text" name="bank_name" required placeholder="مثال: مصرف الجمهورية أو الوحدة"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <!-- Account Number -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">رقم الحساب / الآيبان</label>
                            <input type="text" name="account_no" required placeholder="أدخل رقم الحساب الجاري للمورد"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <!-- SWIFT Code -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">رمز السويفت SWIFT (اختياري)</label>
                            <input type="text" name="swift_code" placeholder="مثال: JUMHLYXXX"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="submit" class="w-2/3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold py-3 rounded-xl transition-all text-sm shadow-md shadow-amber-500/10">
                            تسجيل الحساب البنكي للمورد
                        </button>
                        <button type="button" @click="supplierTab = 'list'" class="w-1/3 bg-slate-100 hover:bg-slate-200 font-bold py-3 rounded-xl transition-all text-sm text-slate-700 border border-slate-200">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 3: Users/Staff List -->
            <div x-show="supplierTab === 'users'" class="p-6" style="display: none;">
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-right">اسم الموظف</th>
                                <th class="px-6 py-4 text-right">البريد الإلكتروني</th>
                                <th class="px-6 py-4 text-right">الصلاحية والوظيفة</th>
                                <th class="px-6 py-4 text-right">تاريخ التسجيل</th>
                                <th class="px-6 py-4 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($users as $user)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ $user->name }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-700 text-xs">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="text-[9px] font-black uppercase px-2.5 py-0.5 rounded-lg border
                                            @if($user->role === 'admin') bg-red-50 text-red-700 border-red-200
                                            @elseif($user->role === 'chef') bg-emerald-50 text-emerald-700 border-emerald-200
                                            @else bg-blue-50 text-blue-700 border-blue-200 @endif">
                                            @if($user->role === 'admin') مدير النظام (Admin)
                                            @elseif($user->role === 'chef') طاهي المطبخ (Chef)
                                            @else كاشير الصالة (Cashier) @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-400 font-mono">{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @if($user->id !== auth()->id())
                                            <form action="/admin/users/{{ $user->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم نهائياً؟');" class="inline-block">
                                                @csrf
                                                <button type="submit" class="bg-red-50 hover:bg-red-650 text-red-600 hover:text-white border border-red-100 hover:border-red-600 text-[10px] font-bold px-3 py-1.5 rounded-lg transition-all shadow-sm">
                                                    🗑️ حذف الموظف
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[10px] text-slate-400 italic">حسابك الحالي</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: Add User Form -->
            <div x-show="supplierTab === 'add_user'" class="p-6 max-w-2xl mx-auto py-10" style="display: none;">
                <form action="/admin/users" method="POST" class="space-y-6" dir="rtl">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Employee Name -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">اسم الموظف بالكامل</label>
                            <input type="text" name="name" required placeholder="مثال: أحمد علي"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <!-- Email -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">البريد الإلكتروني (لتسجيل الدخول)</label>
                            <input type="email" name="email" required placeholder="example@pos.ly"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">كلمة المرور</label>
                            <input type="password" name="password" required placeholder="أدخل 6 خانات على الأقل"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <!-- Role -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">الصلاحية والوظيفة</label>
                            <select name="role" required class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right">
                                <option value="cashier">كاشير صالة (Cashier)</option>
                                <option value="chef">طاهي المطبخ (Chef)</option>
                                <option value="admin">مدير نظام كامل (Admin)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="submit" class="w-2/3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold py-3 rounded-xl transition-all text-sm shadow-md shadow-amber-500/10">
                            تسجيل الموظف الجديد
                        </button>
                        <button type="button" @click="supplierTab = 'users'" class="w-1/3 bg-slate-100 hover:bg-slate-200 font-bold py-3 rounded-xl transition-all text-sm text-slate-700 border border-slate-200">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Accounting & Sales Breakdown -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6" dir="rtl">
            <!-- Payment Methods Stats -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 space-y-4 shadow-sm text-right">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">الإيرادات حسب طريقة الدفع</h3>
                    <span class="text-xs text-slate-400 font-medium">توزيع دخل المبيعات على قنوات الدفع الإلكترونية والنقدية المحلية</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Cash -->
                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex items-center justify-between">
                        <span class="text-2xl">💵</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block text-right">كاش (نقدًا)</span>
                            <span class="text-base font-black text-emerald-600">{{ number_format($salesByPayment['cash'] ?? 0, 2) }} <span class="text-[9px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>

                    <!-- Sadad -->
                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex items-center justify-between">
                        <span class="text-2xl">📱</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block text-right">سداد (Sadad)</span>
                            <span class="text-base font-black text-amber-650">{{ number_format($salesByPayment['sadad'] ?? 0, 2) }} <span class="text-[9px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>

                    <!-- MobiCash -->
                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex items-center justify-between">
                        <span class="text-2xl">💳</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block text-right">موبي كاش</span>
                            <span class="text-base font-black text-blue-600">{{ number_format($salesByPayment['mobicash'] ?? 0, 2) }} <span class="text-[9px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>

                    <!-- Tadawul POS -->
                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex items-center justify-between">
                        <span class="text-2xl">🖥️</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block text-right">تداول (Tadawul)</span>
                            <span class="text-base font-black text-purple-600">{{ number_format($salesByPayment['tadawul'] ?? 0, 2) }} <span class="text-[9px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branch Location Sales Performance -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 space-y-4 shadow-sm text-right">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">مبيعات الفروع ونقاط البيع</h3>
                    <span class="text-xs text-slate-400 font-medium">مساهمة الإيرادات لكل فرع نشط بالمنظومة</span>
                </div>
                
                <div class="space-y-3">
                    @forelse($salesByLocation as $locName => $total)
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-slate-700">{{ $locName }}</span>
                                <span class="text-amber-600 font-extrabold">{{ number_format($total, 2) }} د.ل</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden border border-slate-200/50">
                                <div class="bg-amber-500 h-full rounded-full" style="width: {{ $salesTotal > 0 ? ($total / $salesTotal) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-450 text-center py-6">لا يوجد مبيعات فروع مسجلة في هذه الفترة.</div>
                    @endforelse
                </div>
            </div>
        </section>

        </main>
    </div>

    <!-- 4. Professional Financial Statement Report Modal -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-6 print:p-0 print:bg-white print:relative" 
         x-show="showReportModal" style="display: none;">
        <!-- Modal Backdrop for exit on screen -->
        <div class="absolute inset-0 print:hidden" @click="showReportModal = false"></div>

        <!-- Modal Container -->
        <div class="bg-white border border-slate-200 rounded-3xl max-w-3xl w-full p-8 shadow-2xl relative z-10 flex flex-col justify-between max-h-[90vh] overflow-y-auto print:max-h-none print:w-full print:border-none print:shadow-none print:p-0 print:static print:bg-white print:text-black">
            
            <!-- Modal Body (Printable Area) -->
            <div id="printable-financial-statement" class="space-y-6 print:bg-white print:text-black">
                <!-- Report Header -->
                <div class="flex justify-between items-start border-b border-slate-200 print:border-black pb-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-800 print:text-black">AL-MADINA INTEGRATED POS SYSTEM</h2>
                        <p class="text-xs text-amber-600 print:text-gray-700 font-bold uppercase tracking-wider mt-1">Monthly Profit & Loss Report • تقرير الأرباح والخسائر الجردي</p>
                    </div>
                    <div class="text-right text-[10px] text-slate-450 print:text-black font-mono">
                        <p>Date: {{ date('Y-m-d H:i:s') }}</p>
                        <p>Scope: {{ $startDate->format('Y-m-d') }} to {{ $endDate->format('Y-m-d') }}</p>
                        <p>Currency: Libyan Dinar (LYD)</p>
                    </div>
                </div>

                <!-- Financial Statement Table -->
                <div class="space-y-4">
                    <table class="w-full text-left text-sm print:text-black">
                        <tbody>
                            <!-- Revenue -->
                            <tr class="border-b border-slate-100 print:border-gray-300">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">Gross Sales Revenue (إجمالي المبيعات)</td>
                                <td class="py-2.5 text-right font-bold text-slate-800 print:text-black">{{ number_format($salesTotal - $taxTotal + $discountTotal, 2) }} LYD</td>
                            </tr>
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-450 print:text-gray-700">
                                <td class="py-2 pl-4">Less: Promotional Discounts Offered (الخصومات)</td>
                                <td class="py-2 text-right font-semibold text-red-650 print:text-black">-{{ number_format($discountTotal, 2) }} LYD</td>
                            </tr>
                            <tr class="border-b border-slate-200 print:border-black font-bold bg-slate-50 print:bg-gray-100">
                                <td class="py-2.5 text-slate-800 print:text-black">Net Sales Revenue (صافي إيرادات المبيعات)</td>
                                <td class="py-2.5 text-right text-amber-600 print:text-black">{{ number_format($salesTotal - $taxTotal, 2) }} LYD</td>
                            </tr>

                            <!-- COGS -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-650">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">Less: Cost of Goods Sold (تكلفة المبيعات COGS)</td>
                                <td class="py-2.5 text-right font-bold text-red-650 print:text-black">-{{ number_format($totalCogs, 2) }} LYD</td>
                            </tr>

                            <!-- Inventory Losses (Waste) -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-650">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">Less: Inventory Loss / Waste Expenses (تكلفة هدر وتالف المخزن)</td>
                                <td class="py-2.5 text-right font-bold text-red-650 print:text-black">-{{ number_format($totalWasteCost, 2) }} LYD</td>
                            </tr>

                            <!-- Inventory Adjustments (Surplus) -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-650">
                                <td class="py-2.5 font-bold text-slate-700 print:text-black">Add: Stock Reconciliation Adjustments (فائض تسويات الجرد)</td>
                                <td class="py-2.5 text-right font-bold text-emerald-650 print:text-black">+{{ number_format($totalAdjustmentSurplus, 2) }} LYD</td>
                            </tr>

                            <!-- Gross Profit -->
                            <tr class="border-b border-slate-200 print:border-black font-bold bg-slate-50 print:bg-gray-100">
                                <td class="py-2.5 text-slate-800 print:text-black">Gross Financial Profit (إجمالي الأرباح المالية)</td>
                                <td class="py-2.5 text-right text-amber-600 print:text-black">{{ number_format($grossProfit, 2) }} LYD</td>
                            </tr>

                            <!-- Operational Expenses Header -->
                            <tr class="bg-slate-100/50 print:bg-gray-200 font-bold border-b border-slate-200">
                                <td colspan="2" class="py-2 text-xs text-slate-550 uppercase tracking-wider">Other Monthly Operating Expenses (المصاريف التشغيلية الإضافية)</td>
                            </tr>

                            <!-- Rent Input -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-650">
                                <td class="py-2 pl-4">🏢 Monthly Shop Rent (إيجار المقر)</td>
                                <td class="py-2 text-right font-semibold print:hidden">
                                    <input type="number" x-model.number="rent" min="0" placeholder="0.00" class="w-24 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-right text-slate-800 font-extrabold focus:outline-none focus:border-amber-500" />
                                </td>
                                <td class="py-2 text-right font-semibold hidden print:table-cell" x-text="(parseFloat(rent) || 0).toFixed(2) + ' LYD'"></td>
                            </tr>

                            <!-- Salaries Input -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-650">
                                <td class="py-2 pl-4">👥 Staff Salaries (رواتب الموظفين)</td>
                                <td class="py-2 text-right font-semibold print:hidden">
                                    <input type="number" x-model.number="salaries" min="0" placeholder="0.00" class="w-24 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-right text-slate-800 font-extrabold focus:outline-none focus:border-amber-500" />
                                </td>
                                <td class="py-2 text-right font-semibold hidden print:table-cell" x-text="(parseFloat(salaries) || 0).toFixed(2) + ' LYD'"></td>
                            </tr>

                            <!-- Utilities Input -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-650">
                                <td class="py-2 pl-4">⚡ Electricity, Water & Utilities (المرافق والخدمات)</td>
                                <td class="py-2 text-right font-semibold print:hidden">
                                    <input type="number" x-model.number="utilities" min="0" placeholder="0.00" class="w-24 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-right text-slate-800 font-extrabold focus:outline-none focus:border-amber-500" />
                                </td>
                                <td class="py-2 text-right font-semibold hidden print:table-cell" x-text="(parseFloat(utilities) || 0).toFixed(2) + ' LYD'"></td>
                            </tr>

                            <!-- Misc Input -->
                            <tr class="border-b border-slate-200 print:border-black text-xs text-slate-650">
                                <td class="py-2 pl-4">📦 Miscellaneous Expenses (مصاريف نثرية أخرى)</td>
                                <td class="py-2 text-right font-semibold print:hidden">
                                    <input type="number" x-model.number="misc" min="0" placeholder="0.00" class="w-24 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-right text-slate-800 font-extrabold focus:outline-none focus:border-amber-500" />
                                </td>
                                <td class="py-2 text-right font-semibold hidden print:table-cell" x-text="(parseFloat(misc) || 0).toFixed(2) + ' LYD'"></td>
                            </tr>

                            <!-- Net Profit/Loss -->
                            <tr class="border-b-2 border-slate-350 print:border-black font-black bg-amber-50 text-amber-800 border-y border-amber-250 print:bg-gray-200 print:text-black">
                                <td class="py-4 text-base">NET MONTHLY RECONCILED PROFIT (صافي الربح الشهري النهائي)</td>
                                <td class="py-4 text-right text-base font-extrabold" x-text="({{ $grossProfit }} - (parseFloat(rent) || 0) - (parseFloat(salaries) || 0) - (parseFloat(utilities) || 0) - (parseFloat(misc) || 0)).toFixed(2) + ' LYD'"></td>
                            </tr>
                            
                            <!-- Additional items (Tax, Assets) -->
                            <tr class="border-b border-slate-100 print:border-gray-300 text-xs text-slate-450 print:text-gray-700">
                                <td class="py-2.5 pl-4">Taxes Held / Sales VAT (الضرائب المحصلة)</td>
                                <td class="py-2.5 text-right">{{ number_format($taxTotal, 2) }} LYD</td>
                            </tr>
                            <tr class="border-b border-slate-200 print:border-black text-xs text-slate-450 print:text-gray-700">
                                <td class="py-2.5 pl-4">Inventory Asset Value at End of Period (قيمة أصول المخزن بنهاية المدة)</td>
                                <td class="py-2.5 text-right text-emerald-600 print:text-black">{{ number_format($inventoryAssetValue, 2) }} LYD</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary Signature -->
                <div class="grid grid-cols-2 gap-6 pt-6 border-t border-slate-200 print:border-black text-[10px] text-slate-400 print:text-black">
                    <div class="space-y-1">
                        <p class="font-bold">Prepared By / أعد بواسطة:</p>
                        <p class="text-xs font-black text-slate-800 print:text-black">{{ auth()->user()->name }} ({{ auth()->user()->role }})</p>
                    </div>
                    <div class="space-y-1 text-right">
                        <p class="font-bold">Authorized Signature / التوقيع المعتمد:</p>
                        <div class="h-8 border-b border-slate-300 border-dashed w-36 ml-auto print:border-black"></div>
                    </div>
                </div>
            </div>

            <!-- Action buttons (Screen Only) -->
            <div class="flex gap-4 mt-8 print:hidden">
                <button @click="showReportModal = false" class="w-1/3 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold py-3.5 rounded-2xl text-xs tracking-wider transition-all">
                    CLOSE (إغلاق)
                </button>
                <button @click="window.print()" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black py-3.5 rounded-2xl text-xs tracking-wider transition-all shadow-lg shadow-amber-500/10">
                    🖨️ PRINT STATEMENT (طباعة التقرير المالي)
                </button>
            </div>
        </div>
    </div>

</body>
</html>
