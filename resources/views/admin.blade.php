<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منظومة المدينة - لوحة الإدارة والتحليلات</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;650;750;850;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background-image: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.04) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(99, 102, 241, 0.04) 0%, transparent 40%);
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
<body class="min-h-screen flex relative overflow-x-hidden">

    <!-- Decorative Glow Circles -->
    <div class="absolute top-10 right-10 w-[500px] h-[500px] bg-amber-500/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-10 left-10 w-[550px] h-[550px] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- Unified Left Sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace Area -->
    <div class="flex-grow flex flex-col min-h-screen overflow-y-auto relative z-10">
        <!-- Top bar inside content area -->
        <header class="bg-white/85 backdrop-blur-xl border-b border-slate-200/80 px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 flex-shrink-0 text-right">
            <div>
                <h1 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <span>📊</span> لوحة التحكم والتقارير الإدارية (Dashboard)
                </h1>
                <span class="text-[10px] text-slate-400 font-extrabold mt-1 block">متابعة إيرادات المبيعات الفورية، نسب الأرباح، تالف المخزون وجرد الفروع</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <form action="/admin" method="GET" class="flex items-center gap-3 bg-white border border-slate-200 p-1.5 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-1.5 px-2">
                        <span class="text-[9px] text-slate-400 font-extrabold uppercase">من:</span>
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="bg-transparent text-xs font-bold text-slate-700 focus:outline-none" />
                    </div>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-1.5 px-2">
                        <span class="text-[9px] text-slate-400 font-extrabold uppercase">إلى:</span>
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="bg-transparent text-xs font-bold text-slate-700 focus:outline-none" />
                    </div>
                    <button type="submit" class="bg-slate-900 hover:bg-slate-950 text-white text-[9px] font-black px-4 py-2 rounded-xl transition-all shadow">
                        تصفية
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="/admin" class="text-rose-600 hover:text-rose-700 text-[10px] font-bold px-2">إعادة تعيين</a>
                    @endif
                </form>
                <button @click="showReportModal = true" class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 text-xs font-black px-5 py-3 rounded-2xl transition-all shadow-lg shadow-orange-550/15">
                    💼 إصدار تقرير مالي P&L
                </button>
            </div>
        </header>

        <main class="p-6 lg:p-8 space-y-8" x-data="{ supplierTab: 'list', showReportModal: false, rent: '', salaries: '', utilities: '', misc: '' }">
        
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
        <section class="grid grid-cols-1 md:grid-cols-5 gap-5">
            <!-- Total Revenue Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-blue-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">إجمالي المبيعات (Gross Revenue)</span>
                    <h3 class="text-xl font-black text-blue-600 tracking-tight" dir="ltr">{{ number_format($salesTotal, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">مجموع مقبوضات الفترة الحالية</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-inner">💵</div>
            </div>

            <!-- Cost of Goods Sold Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-rose-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">تكلفة الأغذية (COGS)</span>
                    <h3 class="text-xl font-black text-rose-600 tracking-tight" dir="ltr">{{ number_format($totalCogs, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">التكلفة التشغيلية التقديرية للوجبات</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-550/5 text-rose-600 flex items-center justify-center text-lg shadow-inner">🥩</div>
            </div>

            <!-- Inventory Loss/Waste Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-red-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">الفقد والهدر والتالف</span>
                    <h3 class="text-xl font-black text-red-650 tracking-tight" dir="ltr">{{ number_format($totalWasteCost, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">تكلفة فروقات مطابقة تسويات الجرد</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg shadow-inner">🗑️</div>
            </div>

            <!-- Net Profit Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-amber-500"></div>
                <div class="space-y-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">صافي ربح المبيعات</span>
                        <span class="text-[8px] font-black bg-amber-500/10 text-amber-600 border border-amber-500/20 px-1.5 py-0.5 rounded-lg">{{ number_format($profitMargin, 1) }}%</span>
                    </div>
                    <h3 class="text-xl font-black text-amber-650 tracking-tight" dir="ltr">{{ number_format($grossProfit, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">إجمالي المبيعات مطروحاً منها التكاليف</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-inner">📈</div>
            </div>

            <!-- Inventory Asset Value Card -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-[28px] p-5 flex items-center justify-between shadow-sm hover:shadow transition-all group relative overflow-hidden text-right">
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-emerald-500"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">قيمة أصول المستودعات</span>
                    <h3 class="text-xl font-black text-emerald-650 tracking-tight" dir="ltr">{{ number_format($inventoryAssetValue, 2) }} <span class="text-[9px] font-bold text-slate-400">د.ل</span></h3>
                    <span class="text-[9px] text-slate-400 block font-medium">حجم الأصول المالية بالمخازن</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-inner">📦</div>
            </div>
        </section>

        <!-- 2. Visual Analytics Section (SVG Charts) -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Line Chart Card -->
            <div class="lg:col-span-2 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 space-y-4 shadow-sm text-right">
                <div>
                    <h3 class="text-sm font-black text-slate-800">مخطط المبيعات ومؤشر النشاط</h3>
                    <span class="text-xs text-slate-400 font-bold">متابعة بيانية لحظية لسجل العمليات في الفترة المحددة</span>
                </div>
                <!-- Clean Inline SVG Chart -->
                <div class="w-full h-48 bg-slate-50/80 border border-slate-200/60 rounded-3xl p-4 flex items-center justify-center relative shadow-inner">
                    <svg viewBox="0 0 500 150" class="w-full h-full overflow-visible">
                        <defs>
                            <linearGradient id="salesGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.2"/>
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
                        <path d="M 0 130 Q 80 80 150 110 T 300 40 T 450 70 L 500 70" fill="none" stroke="#ea580c" stroke-width="4" stroke-linecap="round"/>
                        
                        <!-- Chart points -->
                        <circle cx="150" cy="110" r="6" fill="#ea580c" stroke="#ffffff" stroke-width="2.5"/>
                        <circle cx="300" cy="40" r="6" fill="#ea580c" stroke="#ffffff" stroke-width="2.5"/>
                        <circle cx="450" cy="70" r="6" fill="#ea580c" stroke="#ffffff" stroke-width="2.5"/>
                    </svg>
                    <!-- Floating Labels -->
                    <div class="absolute bottom-2.5 right-6 text-[10px] font-black text-slate-450">الفترة الصباحية</div>
                    <div class="absolute bottom-2.5 left-1/2 -translate-x-1/2 text-[10px] font-black text-orange-600">ذروة الغداء</div>
                    <div class="absolute bottom-2.5 left-6 text-[10px] font-black text-slate-450">الفترة المسائية</div>
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
            <div x-show="supplierTab === 'list'" class="p-6">
                <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                    <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                        <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4.5 text-right">اسم المورد</th>
                                <th class="px-6 py-4.5 text-right">اسم المصرف</th>
                                <th class="px-6 py-4.5 text-right">رقم الحساب البنكي / IBAN</th>
                                <th class="px-6 py-4.5 text-right">رمز السويفت SWIFT</th>
                                <th class="px-6 py-4.5 text-right">تاريخ التسجيل</th>
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
            <div x-show="supplierTab === 'add'" class="p-6 max-w-2xl mx-auto py-10" style="display: none;">
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
            <div x-show="supplierTab === 'users'" class="p-6" style="display: none;">
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
                                            <form action="/admin/users/{{ $user->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم نهائياً؟');" class="inline-block">
                                                @csrf
                                                <button type="submit" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 text-[10px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                    🗑️ حذف الموظف
                                                </button>
                                            </form>
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
            <div x-show="supplierTab === 'add_user'" class="p-6 max-w-2xl mx-auto py-10" style="display: none;">
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
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6" dir="rtl">
            <!-- Payment Methods Stats -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 space-y-4 shadow-sm text-right">
                <div>
                    <h3 class="text-sm font-black text-slate-800">الإيرادات حسب طريقة الدفع</h3>
                    <span class="text-xs text-slate-400 font-bold">توزيع دخل المبيعات على قنوات الدفع الإلكترونية والنقدية المحلية</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Cash -->
                    <div class="bg-slate-50/80 border border-slate-200 p-4 rounded-2xl flex items-center justify-between shadow-sm">
                        <span class="text-3xl">💵</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[9px] font-black text-slate-400 block text-right">كاش (نقدًا)</span>
                            <span class="text-sm font-black text-emerald-600" dir="ltr">{{ number_format($salesByPayment['cash'] ?? 0, 2) }} <span class="text-[8px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>

                    <!-- Sadad -->
                    <div class="bg-slate-50/80 border border-slate-200 p-4 rounded-2xl flex items-center justify-between shadow-sm">
                        <span class="text-3xl">📱</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[9px] font-black text-slate-400 block text-right">سداد (Sadad)</span>
                            <span class="text-sm font-black text-amber-650" dir="ltr">{{ number_format($salesByPayment['sadad'] ?? 0, 2) }} <span class="text-[8px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>

                    <!-- MobiCash -->
                    <div class="bg-slate-50/80 border border-slate-200 p-4 rounded-2xl flex items-center justify-between shadow-sm">
                        <span class="text-3xl">💳</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[9px] font-black text-slate-400 block text-right">موبي كاش</span>
                            <span class="text-sm font-black text-blue-600" dir="ltr">{{ number_format($salesByPayment['mobicash'] ?? 0, 2) }} <span class="text-[8px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>

                    <!-- Tadawul POS -->
                    <div class="bg-slate-50/80 border border-slate-200 p-4 rounded-2xl flex items-center justify-between shadow-sm">
                        <span class="text-3xl">🖥️</span>
                        <div class="space-y-1 text-left">
                            <span class="text-[9px] font-black text-slate-400 block text-right">تداول (Tadawul)</span>
                            <span class="text-sm font-black text-purple-650" dir="ltr">{{ number_format($salesByPayment['tadawul'] ?? 0, 2) }} <span class="text-[8px] text-slate-400">د.ل</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branch Location Sales Performance -->
            <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 space-y-4 shadow-sm text-right">
                <div>
                    <h3 class="text-sm font-black text-slate-800">مبيعات الفروع ونقاط البيع</h3>
                    <span class="text-xs text-slate-400 font-bold">مساهمة الإيرادات لكل فرع نشط بالمنظومة</span>
                </div>
                
                <div class="space-y-4 pt-2">
                    @forelse($salesByLocation as $locName => $total)
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-slate-700">{{ $locName }}</span>
                                <span class="text-amber-600 font-black" dir="ltr">{{ number_format($total, 2) }} د.ل</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden border border-slate-200/50 shadow-inner">
                                <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-full rounded-full" style="width: {{ $salesTotal > 0 ? ($total / $salesTotal) * 100 : 0 }}%"></div>
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
                <button @click="showReportModal = false" class="w-1/3 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-black py-3.5 rounded-2xl text-xs tracking-wider transition-all">
                    إغلاق النافذة
                </button>
                <button @click="window.print()" class="w-2/3 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-3.5 rounded-2xl text-xs tracking-wider transition-all shadow-lg shadow-orange-550/15">
                    🖨️ طباعة وتصدير كشف الأرباح والخسائر
                </button>
            </div>
        </div>
    </div>

</body>
</html>
