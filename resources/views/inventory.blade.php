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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدينة POS - إدارة المخازن والمنتجات</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@350;400;650;755;850;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    </style>
</head>
<body class="min-h-screen flex bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 relative overflow-x-hidden page-animate" x-data="{ 
    tab: 'stock', 
    editingIngredient: null,
    selectedReconcileLocation: '{{ $locations->first()?->id }}',
    locationStocks: {{ json_encode($locationStocks) }},
    products: {{ json_encode($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'category' => $p->category, 'base_price' => (float)$p->base_price])) }},
    counts: {},
    getSystemStock(productId) {
        if (!this.selectedReconcileLocation) return 0;
        const loc = this.locationStocks[this.selectedReconcileLocation];
        return loc ? (parseFloat(loc[productId]) || 0) : 0;
    },
    getVariance(productId) {
        if (this.counts[productId] === undefined || this.counts[productId] === '') return 0;
        const actual = parseFloat(this.counts[productId]);
        if (isNaN(actual)) return 0;
        const system = this.getSystemStock(productId);
        return actual - system;
    },
    getVarianceCost(product) {
        const variance = this.getVariance(product.id);
        const cost = product.base_price * 0.40;
        return variance * cost;
    }
}">

    <!-- Decorative Glow Circles -->
    <div class="hidden dark:block absolute top-10 right-10 w-[500px] h-[500px] bg-amber-500/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="hidden dark:block absolute bottom-10 left-10 w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- Unified Left Sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace -->
    <div class="flex-grow flex flex-col h-screen overflow-hidden relative z-10">
        
        <!-- Header Bar -->
        <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 px-5 py-3 flex flex-col lg:flex-row items-center justify-between gap-3 flex-shrink-0 text-right">
            <!-- Mobile Header Row -->
            <div class="flex items-center justify-between w-full lg:w-auto">
                <div class="flex items-center gap-3">
                    <!-- Mobile Sidebar Toggle -->
                    <button @click="$dispatch('toggle-sidebar')" class="lg:hidden p-2 text-slate-700 hover:text-slate-900 focus:outline-none text-xl leading-none">
                        ☰
                    </button>
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-lg shadow-lg shadow-orange-500/20 flex-shrink-0">
                        📦
                    </div>
                    <div>
                        <h1 class="text-sm font-black text-slate-900 dark:text-white leading-none">إدارة المستودعات والمنتجات</h1>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block mt-0.5">Inventory & Stock Management</span>
                    </div>
                </div>
            </div>

            <!-- Tab Toggle buttons (Scrollable on mobile) -->
            <div class="bg-slate-100 border border-slate-200 p-1.5 rounded-2xl flex gap-1 overflow-x-auto w-full lg:w-auto max-w-full scrollbar-none" dir="rtl">
                <button @click="tab = 'stock'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all duration-300 flex-shrink-0"
                        :class="tab === 'stock' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-950'">
                    المخزون والتوريد
                </button>
                <button @click="tab = 'products'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all duration-300 flex-shrink-0"
                        :class="tab === 'products' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-950'">
                    إدارة الأصناف
                </button>
                <button @click="tab = 'ingredients'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all duration-300 flex-shrink-0"
                        :class="tab === 'ingredients' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-950'">
                    المواد الخام
                </button>
                <button @click="tab = 'recipes'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all duration-300 flex-shrink-0"
                        :class="tab === 'recipes' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-950'">
                    مكونات الوجبات
                </button>
                <button @click="tab = 'locations'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all duration-300 flex-shrink-0"
                        :class="tab === 'locations' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-950'">
                    الفروع والمواقع
                </button>
                <button @click="tab = 'reconcile'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all duration-300 flex-shrink-0"
                        :class="tab === 'reconcile' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-950'">
                    تسوية الجرد
                </button>
                <button @click="tab = 'history'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all duration-300 flex-shrink-0"
                        :class="tab === 'history' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-600 hover:text-slate-950'">
                    حركات المخازن
                </button>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow overflow-y-auto p-5 lg:p-6 space-y-5" dir="rtl">
            
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse shadow-sm">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- TAB 1: Stock Levels & Restock Form -->
            <div x-show="tab === 'stock'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Right (List - 2 cols) -->
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-black text-slate-800 flex justify-between">
                            <span>مستويات المخزون الحالي</span>
                            <span class="text-xs text-slate-400 font-bold">حالة المنتجات بالمستودعات</span>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($products as $product)
                                @php
                                    $stock = $stockLevels[$product->id] ?? 0;
                                @endphp
                                <div class="bg-slate-50/70 border border-slate-200/80 p-5 rounded-[24px] flex items-center justify-between shadow-sm hover:border-amber-500/30 transition-all duration-300">
                                    <div>
                                        <span class="text-[8px] uppercase font-black text-amber-600 tracking-wider bg-amber-50 border border-amber-200/50 px-2 py-0.5 rounded-md shadow-sm">{{ $product->category }}</span>
                                        <h3 class="font-extrabold text-xs text-slate-800 mt-2.5">{{ $product->name }}</h3>
                                        <span class="text-[10px] text-slate-450 font-black block mt-0.5" dir="ltr">{{ number_format($product->base_price, 2) }} د.ل</span>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-xl font-black block {{ $stock <= 10 ? 'text-rose-600 animate-pulse' : 'text-emerald-650' }}">
                                            {{ number_format($stock, 0) }}
                                        </span>
                                        <span class="text-[9px] text-slate-400 font-bold">وحدة متوفرة</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Left (Restock Form - 1 col) -->
                <div class="xl:col-span-1 space-y-6">
                    <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-6 sticky top-28">
                        <div>
                            <h2 class="text-sm font-black text-slate-800">توريد وتحديث المخزون</h2>
                            <span class="text-[10px] text-slate-450 font-bold block mt-1">تسجيل شحنة توريد بضاعة جديدة للمخازن</span>
                        </div>
                        <form action="/admin/inventory/restock" method="POST" class="space-y-4">
                            @csrf
                            <!-- Product -->
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold">الصنف المراد توريده</label>
                                <select name="product_id" required class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all shadow-sm">
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->category }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Branch Location -->
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold">الفرع / الموقع المستلم</label>
                                <select name="location_id" required class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all shadow-sm">
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Qty & Unit Cost -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5 text-right">
                                    <label class="text-xs text-slate-500 font-bold">الكمية الموردة</label>
                                    <input type="number" step="0.01" name="quantity" required placeholder="100.00"
                                           class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all text-right shadow-sm" />
                                </div>
                                <div class="space-y-1.5 text-right">
                                    <label class="text-xs text-slate-500 font-bold">تكلفة الوحدة (د.ل)</label>
                                    <input type="number" step="0.01" name="unit_cost" required placeholder="8.50"
                                           class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all text-right shadow-sm" />
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-4 rounded-2xl shadow-lg shadow-orange-550/15 transition-all text-xs tracking-wider">
                                تسجيل شحنة التوريد بالمستندات
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB 1.5: Stocktake Reconciliation -->
            <div x-show="tab === 'reconcile'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="space-y-6" style="display: none;">
                <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-6 text-right">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-150 pb-4">
                        <div>
                            <h2 class="text-sm font-black text-slate-800">مطابقة الجرد وتسوية فروقات المخازن</h2>
                            <p class="text-xs text-slate-400 mt-1">اختر الفرع، ثم أدخل كميات الجرد الفعلي على الرف لتعديل الرصيد الدفتري وحساب الفروقات المالية.</p>
                        </div>
                        <!-- Branch selector -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 font-bold">الفرع المستهدف:</span>
                            <select x-model="selectedReconcileLocation" class="bg-white border border-slate-200 text-xs rounded-xl px-4 py-2 focus:outline-none focus:border-amber-500 shadow-sm font-bold">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <form action="/admin/inventory/reconcile" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="location_id" :value="selectedReconcileLocation" />

                        <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                            <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                                <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-4.5 text-right">اسم الصنف</th>
                                        <th class="px-6 py-4.5 text-right">الفئة</th>
                                        <th class="px-6 py-4.5 text-center">الرصيد الدفتري بالمنظومة</th>
                                        <th class="px-6 py-4.5 text-center w-48">الجرد الفعلي على الرف</th>
                                        <th class="px-6 py-4.5 text-center">الفارق في المخزون</th>
                                        <th class="px-6 py-4.5 text-left">الأثر المالي (د.ل)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="product in products" :key="product.id">
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-black text-slate-800" x-text="product.name"></td>
                                            <td class="px-6 py-4">
                                                <span class="text-[8px] bg-amber-50 text-amber-600 border border-amber-200/50 px-2 py-0.5 rounded-md font-black shadow-sm" x-text="product.category"></span>
                                            </td>
                                            <td class="px-6 py-4 text-center font-mono font-bold text-slate-800" x-text="getSystemStock(product.id)"></td>
                                            <td class="px-6 py-2.5">
                                                <input type="number" :name="'counts[' + product.id + ']'" x-model="counts[product.id]" placeholder="أدخل الكمية الفعلية" step="1" min="0"
                                                       class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-3 py-2 text-center text-xs text-slate-800 font-extrabold focus:outline-none transition-all shadow-inner" />
                                            </td>
                                            <td class="px-6 py-4 text-center font-mono font-bold">
                                                <span :class="getVariance(product.id) < 0 ? 'text-rose-600' : (getVariance(product.id) > 0 ? 'text-emerald-600' : 'text-slate-400')"
                                                      x-text="(getVariance(product.id) > 0 ? '+' : '') + getVariance(product.id)"></span>
                                            </td>
                                            <td class="px-6 py-4 text-left font-mono font-bold" dir="ltr">
                                                <span :class="getVariance(product.id) < 0 ? 'text-rose-600' : (getVariance(product.id) > 0 ? 'text-emerald-650' : 'text-slate-400')"
                                                      x-text="counts[product.id] === undefined || counts[product.id] === '' ? '0.00 LYD' : (getVarianceCost(product).toFixed(2) + ' LYD')"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Live Summary Box -->
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 flex flex-col sm:flex-row justify-between gap-4 shadow-inner">
                            <div>
                                <span class="text-xs text-slate-500 font-bold block">الأثر المالي الإجمالي لتسوية فروقات الجرد الحالية</span>
                                <span class="text-[9px] text-slate-400 font-semibold block mt-1">محسوب بيعياً بناءً على متوسط هامش التكلفة التقديري للأغذية</span>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-450 font-bold block">الفروقات الإجمالية (وحدات)</span>
                                    <span class="text-lg font-black block" 
                                          :class="products.reduce((sum, p) => sum + getVariance(p.id), 0) < 0 ? 'text-rose-650' : 'text-emerald-650'"
                                          x-text="products.reduce((sum, p) => sum + getVariance(p.id), 0)"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-450 font-bold block">صافي الفارق التشغيلي</span>
                                    <span class="text-lg font-black block" 
                                          :class="products.reduce((sum, p) => sum + getVarianceCost(p), 0) < 0 ? 'text-rose-650' : 'text-emerald-650'"
                                          x-text="products.reduce((sum, p) => sum + getVarianceCost(p), 0).toFixed(2) + ' د.ل'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black text-xs px-6 py-4 rounded-2xl transition-all shadow-lg shadow-orange-550/15">
                                اعتماد كشف الجرد وتسوية رصيد المخزن
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 2: Products Manager CRUD -->
            <div x-show="tab === 'products'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="grid grid-cols-1 xl:grid-cols-3 gap-6" style="display: none;">
                <!-- Product List -->
                <div class="xl:col-span-2 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-black text-slate-800">كتالوج المنتجات وأصناف قائمة المبيعات</h2>
                    
                    <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                        <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4.5 text-right">الوجبة / الصنف</th>
                                    <th class="px-6 py-4.5 text-right">الفئة</th>
                                    <th class="px-6 py-4.5 text-left">سعر البيع الافتراضي</th>
                                    <th class="px-6 py-4.5 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($products as $prod)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                         <td class="px-6 py-4 flex items-center gap-3 justify-start">
                                             <img src="{{ $prod->image_url ?? 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=100&auto=format&fit=crop' }}" 
                                                  alt="" 
                                                  class="w-10 h-10 rounded-2xl object-cover border border-slate-200 flex-shrink-0 shadow-sm" />
                                             <span class="font-black text-slate-800 text-xs">{{ $prod->name }}</span>
                                         </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[8px] bg-amber-50 text-amber-600 border border-amber-200/50 px-2 py-0.5 rounded-md font-black shadow-sm">{{ $prod->category }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-left font-black text-slate-800" dir="ltr">{{ number_format($prod->base_price, 2) }} د.ل</td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="/admin/products/{{ $prod->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الصنف من القائمة نهائياً؟');">
                                                @csrf
                                                <button type="submit" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-650 text-[9px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                    🗑️ حذف الصنف
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-450 font-bold">لا يوجد منتجات أو أصناف مسجلة حالياً.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Product Form -->
                <div class="xl:col-span-1 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-black text-slate-800">إضافة صنف وجبة جديد</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">تسجيل وجبة جديدة لعرضها على الكاشير بالمنظومة</span>
                    </div>

                    <form action="/admin/products" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">اسم الوجبة الصنف</label>
                            <input type="text" name="name" required placeholder="مثال: بيتزا مارغريتا عائلية"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-850 focus:outline-none transition-all text-right shadow-sm" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold">سعر البيع (د.ل)</label>
                                <input type="number" step="0.01" name="base_price" required placeholder="24.00"
                                       class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-850 focus:outline-none transition-all text-right shadow-sm" />
                            </div>
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold">الفئة</label>
                                <input type="text" name="category" required placeholder="مثال: بيتزا أو برجر"
                                       class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-850 focus:outline-none transition-all text-right shadow-sm" />
                            </div>
                        </div>

                        <!-- Product Image URL -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">رابط صورة الوجبة (اختياري)</label>
                            <input type="url" name="image_url" placeholder="https://images.unsplash.com/..."
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-850 focus:outline-none transition-all text-left shadow-sm" dir="ltr" />
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-4 rounded-2xl shadow-lg shadow-orange-550/15 transition-all text-xs tracking-wider">
                            حفظ وتسجيل الصنف بالمنظومة
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 3: Ingredients Manager CRUD -->
            <div x-show="tab === 'ingredients'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="grid grid-cols-1 xl:grid-cols-3 gap-6" style="display: none;">
                
                <!-- Alert box for low stock ingredients -->
                @if(isset($lowStockIngredients) && count($lowStockIngredients) > 0)
                    <div class="xl:col-span-3 bg-gradient-to-r from-amber-500/10 to-orange-555/10 border border-amber-500/20 text-amber-900 p-5 rounded-[28px] text-xs font-bold flex flex-col gap-3 shadow-sm text-right">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl animate-bounce">⚠️</span>
                            <span class="font-black text-slate-900 text-sm">تنبيه النواقص: مواد خام تحت حد الطلب الأدنى!</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-1">
                            @foreach($lowStockIngredients as $lowIng)
                                <div class="bg-white border border-amber-200/60 p-4.5 rounded-[22px] flex justify-between items-center shadow-sm">
                                    <div>
                                        <span class="font-black text-slate-850 text-xs block">{{ $lowIng['name'] }}</span>
                                        <span class="text-[9px] text-slate-400 font-extrabold block mt-1">حد التنبيه: {{ number_format($lowIng['alert_threshold'], 2) }} {{ $lowIng['unit'] }}</span>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-sm font-black text-rose-600 block" dir="ltr">{{ number_format($lowIng['current_stock'], 2) }} {{ $lowIng['unit'] }}</span>
                                        <span class="text-[8px] text-slate-400 font-extrabold block mt-0.5">الرصيد الفعلي</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Ingredient Catalogue List -->
                <div class="xl:col-span-2 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-black text-slate-800">كتالوج المواد الخام ومستلزمات الطهي</h2>
                    
                    <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                        <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4.5 text-right">اسم المادة الخام</th>
                                    <th class="px-6 py-4.5 text-right">وحدة القياس المعتمدة</th>
                                    <th class="px-6 py-4.5 text-left">حد الطلب الأدنى للتنبيه</th>
                                    <th class="px-6 py-4.5 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($ingredients as $ing)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-black text-slate-800">{{ $ing->name }}</td>
                                        <td class="px-6 py-4 font-mono text-xs text-amber-600 font-extrabold">{{ $ing->unit }}</td>
                                        <td class="px-6 py-4 text-left font-black text-slate-800" dir="ltr">{{ number_format($ing->alert_threshold, 2) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="/admin/ingredients/{{ $ing->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المكون الخام نهائياً؟');">
                                                @csrf
                                                <button type="submit" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-650 text-[9px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                    🗑️ حذف المكون
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-450 font-bold">لا يوجد مواد خام مسجلة حالياً بالمنظومة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Ingredient Form -->
                <div class="xl:col-span-1 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-black text-slate-800">تسجيل مادة خام جديدة</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">إضافة مكونات أساسية للمطابخ لتتبع وحساب الاستهلاك</span>
                    </div>

                    <form action="/admin/ingredients" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">اسم المادة الخام</label>
                            <input type="text" name="name" required placeholder="مثال: لحم بقري مفروم أو طماطم"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-855 focus:outline-none transition-all text-right shadow-sm" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold">وحدة القياس</label>
                                <input type="text" name="unit" required placeholder="مثال: كجم، قطعة، لتر"
                                       class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-855 focus:outline-none transition-all text-right shadow-sm" />
                            </div>
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold">حد التنبيه الأدنى</label>
                                <input type="number" step="0.01" name="alert_threshold" required placeholder="10.00"
                                       class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-855 focus:outline-none transition-all text-right shadow-sm" />
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-4 rounded-2xl shadow-lg shadow-orange-550/15 transition-all text-xs tracking-wider">
                            حفظ وتسجيل المادة الخام
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 4: Recipes Map CRUD -->
            <div x-show="tab === 'recipes'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="grid grid-cols-1 xl:grid-cols-3 gap-6" style="display: none;">
                <!-- Recipe Map details -->
                <div class="xl:col-span-2 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-6 text-right">
                    <div>
                        <h2 class="text-sm font-black text-slate-800">تركيبات ومكونات الوجبات (الوصفات)</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">تحديد مقادير المواد الخام المستهلكة تلقائياً عند بيع كل وجبة</span>
                    </div>

                    <div class="space-y-4">
                        @foreach($products as $prod)
                            <div class="bg-slate-50/70 border border-slate-200/80 p-5 rounded-[24px] space-y-4 shadow-sm text-right">
                                <div class="flex justify-between items-center border-b border-slate-200/85 pb-2.5">
                                    <span class="font-black text-xs text-amber-600">{{ $prod->name }}</span>
                                    <span class="text-[9px] font-black text-slate-400 uppercase bg-white border border-slate-200 px-2 py-0.5 rounded-md shadow-sm">{{ $prod->category }}</span>
                                </div>

                                <div class="space-y-2">
                                    @forelse($prod->ingredients as $recipeItem)
                                        <div class="flex justify-between items-center text-xs bg-white p-3 rounded-xl border border-slate-200/80 shadow-sm" dir="rtl">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_6px_rgba(245,158,11,0.5)] animate-pulse"></span>
                                                <span class="font-extrabold text-slate-800 text-xs">{{ $recipeItem->name }}</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-mono text-slate-600 font-bold text-xs" dir="ltr">{{ number_format($recipeItem->pivot->quantity_needed, 4) }} {{ $recipeItem->unit }}</span>
                                                <form action="/admin/recipes/{{ $prod->id }}/{{ $recipeItem->id }}/delete" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-[10px] font-black bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg transition-colors border border-rose-100">❌ إلغاء الربط</button>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-[10px] text-slate-400 font-bold block italic py-2">لا توجد مواد خام مرتبطة أو وصفة لهذا الصنف بعد.</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Link/Assign Ingredient to Product Form -->
                <div class="xl:col-span-1 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-black text-slate-800">ربط مادة خام بصنف وجبة</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">ربط مادة خام لتخصيص مقدار الاستهلاك الفعلي للمبيعات</span>
                    </div>

                    <form action="/admin/recipes" method="POST" class="space-y-4">
                        @csrf
                        <!-- Product Selection -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">الوجبة المستهدفة</label>
                            <select name="product_id" required class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all shadow-sm">
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Ingredient Selection -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">المادة الخام المستهلكة</label>
                            <select name="ingredient_id" required class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all shadow-sm">
                                @foreach($ingredients as $ing)
                                    <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quantity Needed -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">كمية الاستهلاك لكل عملية بيع</label>
                            <input type="number" step="0.0001" name="quantity_needed" required placeholder="0.1500"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-855 focus:outline-none transition-all text-right shadow-sm" />
                            <span class="text-[9px] text-slate-450 block mt-1.5 leading-relaxed">ستقوم المنظومة بخصم هذه المقادير من رصيد المادة الخام تلقائياً عند إتمام أي عملية بيع لهذا الصنف.</span>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-4 rounded-2xl shadow-lg shadow-orange-550/15 transition-all text-xs tracking-wider">
                            ربط وحفظ مكونات الوصفة
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 5: Branches/Locations Manager CRUD -->
            <div x-show="tab === 'locations'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="grid grid-cols-1 xl:grid-cols-3 gap-6" style="display: none;">
                <!-- Locations List -->
                <div class="xl:col-span-2 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-black text-slate-800">الفروع والمواقع المسجلة بالمنظومة</h2>
                    
                    <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                        <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4.5 text-right">اسم الفرع / موقع المبيعات</th>
                                    <th class="px-6 py-4.5 text-right">المعرف الفريد UUID</th>
                                    <th class="px-6 py-4.5 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($locations as $loc)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-black text-slate-800">{{ $loc->name }}</td>
                                        <td class="px-6 py-4 font-mono text-[10px] text-slate-450 font-bold">{{ $loc->id }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="/admin/locations/{{ $loc->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الفرع نهائياً؟');">
                                                @csrf
                                                <button type="submit" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-650 text-[9px] font-black px-3.5 py-2 rounded-xl transition-all shadow-sm">
                                                    🗑️ حذف الفرع
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-slate-450 font-bold">لا يوجد فروع أو مواقع مسجلة حالياً بالمنظومة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Location Form -->
                <div class="xl:col-span-1 bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-black text-slate-800">إضافة فرع أو مستودع جديد</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">تسجيل موقع جغرافي أو نقطة بيع إضافية بالفروع</span>
                    </div>

                    <form action="/admin/locations" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold">اسم الفرع الجديد</label>
                            <input type="text" name="name" required placeholder="مثال: فرع طرابلس أو بنغازي الرئيسي"
                                   class="w-full bg-white border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3 text-xs text-slate-855 focus:outline-none transition-all text-right shadow-sm" />
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 font-black py-4 rounded-2xl shadow-lg shadow-orange-550/15 transition-all text-xs tracking-wider">
                            تأكيد وتسجيل الفرع الجديد
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 6: Transactions Audit Ledger History -->
            <div x-show="tab === 'history'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 style="display: none;">
                <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-[32px] p-6 shadow-sm space-y-4 text-right">
                    <h2 class="text-sm font-black text-slate-800">سجل حركات وعمليات المخازن التفصيلي (Audit Ledger)</h2>
                    
                    <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                        <table class="w-full text-right text-xs text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4.5 text-right">رمز الحركة</th>
                                    <th class="px-6 py-4.5 text-right">الصنف المنتج</th>
                                    <th class="px-6 py-4.5 text-right">الفرع المستهدف</th>
                                    <th class="px-6 py-4.5 text-left">الكمية</th>
                                    <th class="px-6 py-4.5 text-left">تكلفة الوحدة</th>
                                    <th class="px-6 py-4.5 text-center">نوع العملية</th>
                                    <th class="px-6 py-4.5 text-right">التاريخ والوقت</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($transactions as $tx)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-[10px] text-slate-400 font-bold" dir="ltr">{{ substr($tx->id, 0, 8) }}...</td>
                                        <td class="px-6 py-4 font-black text-slate-800">{{ $tx->product->name }}</td>
                                        <td class="px-6 py-4 text-slate-600 font-bold">{{ $tx->location->name }}</td>
                                        <td class="px-6 py-4 text-left font-black {{ $tx->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}" dir="ltr">
                                            {{ $tx->quantity > 0 ? '+' : '' }}{{ number_format($tx->quantity, 0) }}
                                        </td>
                                        <td class="px-6 py-4 text-left font-mono text-slate-800 font-bold" dir="ltr">{{ number_format($tx->unit_cost, 2) }} د.ل</td>
                                        <td class="px-6 py-4 text-center">
                                            @if(($tx->type ?? '') === 'restock')
                                                <span class="text-[8px] px-2.5 py-0.5 rounded font-black uppercase border bg-emerald-500/10 text-emerald-600 border-emerald-500/20">
                                                    توريد (RESTOCK)
                                                </span>
                                            @elseif(($tx->type ?? '') === 'sale')
                                                <span class="text-[8px] px-2.5 py-0.5 rounded font-black uppercase border bg-blue-500/10 text-blue-600 border-blue-500/20">
                                                    مبيعات (SALE)
                                                </span>
                                            @elseif(($tx->type ?? '') === 'waste')
                                                <span class="text-[8px] px-2.5 py-0.5 rounded font-black uppercase border bg-rose-500/10 text-rose-600 border-rose-500/20">
                                                    هدر/تالف (WASTE)
                                                </span>
                                            @elseif(($tx->type ?? '') === 'adjustment')
                                                <span class="text-[8px] px-2.5 py-0.5 rounded font-black uppercase border bg-amber-500/10 text-amber-600 border-amber-500/20">
                                                    تسوية زيادة (ADJUST)
                                                </span>
                                            @else
                                                <span class="text-[8px] px-2.5 py-0.5 rounded font-black uppercase border bg-slate-500/10 text-slate-600 border-slate-500/20">
                                                    {{ strtoupper($tx->type ?? ($tx->quantity > 0 ? 'RESTOCK' : 'SALE')) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-400 font-bold font-mono">{{ $tx->created_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-slate-450 font-bold">لا يوجد قيود أو حركات مخازن مسجلة بالدفاتر حالياً.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

</body>
</html>
