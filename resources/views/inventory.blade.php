<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدينة POS - إدارة المخازن والمنتجات</title>
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
    </style>
</head>
<body class="min-h-screen flex" x-data="{ 
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

    <!-- Unified Left Sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace -->
    <div class="flex-grow flex flex-col min-h-screen overflow-y-auto">
        
        <!-- Header Bar -->
        <header class="bg-white border-b border-slate-200 px-8 py-5 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 flex-shrink-0 text-right">
            <div>
                <h1 class="text-xl font-black text-slate-800 flex items-center gap-2">
                    <span>📦</span> إدارة المخازن والمنتجات (Catalog & Stores)
                </h1>
                <span class="text-xs text-slate-400 font-medium mt-1 block">إضافة المنتجات والوجبات، إدارة المواد الخام والوصفات، وإجراء الجرد الدوري</span>
            </div>

            <!-- Tab Toggle buttons -->
            <div class="bg-slate-100 border border-slate-200 p-1.5 rounded-2xl flex flex-wrap gap-1" dir="rtl">
                <button @click="tab = 'stock'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                        :class="tab === 'stock' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-600 hover:text-slate-950'">
                    المخزون والتوريد
                </button>
                <button @click="tab = 'products'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                        :class="tab === 'products' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-600 hover:text-slate-950'">
                    إدارة الأصناف
                </button>
                <button @click="tab = 'ingredients'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                        :class="tab === 'ingredients' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-600 hover:text-slate-950'">
                    المواد الخام
                </button>
                <button @click="tab = 'recipes'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                        :class="tab === 'recipes' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-600 hover:text-slate-950'">
                    مكونات الوجبات
                </button>
                <button @click="tab = 'locations'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                        :class="tab === 'locations' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-600 hover:text-slate-950'">
                    الفروع والمواقع
                </button>
                <button @click="tab = 'reconcile'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                        :class="tab === 'reconcile' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-600 hover:text-slate-950'">
                    تسوية الجرد
                </button>
                <button @click="tab = 'history'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300"
                        :class="tab === 'history' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/10' : 'text-slate-600 hover:text-slate-950'">
                    سجل حركات المخزن
                </button>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="p-8 space-y-8" dir="rtl">
            
            @if(session('success'))
                <div class="bg-green-50 border border-green-250 text-green-700 p-4 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- TAB 1: Stock Levels & Restock Form -->
            <div x-show="tab === 'stock'" class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Right (List - 2 cols) -->
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-bold text-slate-800 flex justify-between">
                            <span>مستويات المخزون الحالي</span>
                            <span class="text-xs text-slate-400 font-medium">حالة المنتجات بالمستودعات</span>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($products as $product)
                                @php
                                    $stock = $stockLevels[$product->id] ?? 0;
                                @endphp
                                <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl flex items-center justify-between shadow-sm hover:border-amber-500/30 transition-all duration-300">
                                    <div>
                                        <span class="text-[9px] uppercase font-black text-amber-700 tracking-wider bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-lg">{{ $product->category }}</span>
                                        <h3 class="font-extrabold text-sm text-slate-800 mt-2">{{ $product->name }}</h3>
                                        <span class="text-xs text-slate-450 font-bold block mt-0.5">{{ number_format($product->base_price, 2) }} د.ل</span>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-2xl font-black block {{ $stock <= 10 ? 'text-red-650' : 'text-emerald-650' }}">
                                            {{ number_format($stock, 0) }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-bold">وحدة متوفرة</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Left (Restock Form - 1 col) -->
                <div class="xl:col-span-1 space-y-6">
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6 sticky top-28">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">توريد وتحديث المخزون</h2>
                            <span class="text-[10px] text-slate-450 font-bold block mt-1">تسجيل شحنة توريد بضاعة جديدة للمخازن</span>
                        </div>
                        <form action="/admin/inventory/restock" method="POST" class="space-y-4">
                            @csrf
                            <!-- Product -->
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold uppercase">الصنف المراد توريده</label>
                                <select name="product_id" required class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300">
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->category }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Branch Location -->
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold uppercase">الفرع / الموقع</label>
                                <select name="location_id" required class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300">
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Qty & Unit Cost -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5 text-right">
                                    <label class="text-xs text-slate-500 font-bold uppercase">الكمية الموردة</label>
                                    <input type="number" step="0.01" name="quantity" required placeholder="100.00"
                                           class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                                </div>
                                <div class="space-y-1.5 text-right">
                                    <label class="text-xs text-slate-500 font-bold uppercase">تكلفة الوحدة (د.ل)</label>
                                    <input type="number" step="0.01" name="unit_cost" required placeholder="8.50"
                                           class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-3.5 rounded-xl transition-all shadow-md shadow-amber-500/10 text-xs uppercase tracking-wider">
                                تسجيل عملية التوريد بالمخزن
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB 1.5: Stocktake Reconciliation -->
            <div x-show="tab === 'reconcile'" class="space-y-6" style="display: none;">
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6 text-right">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">جرد المخزون وتسوية الفروقات الشهرية</h2>
                            <p class="text-xs text-slate-400 mt-1">اختر الفرع، ثم أدخل الكميات الفعلية الموجودة على الرف لتعديل الرصيد الدفتري وحساب الفروقات المالية تلقائياً.</p>
                        </div>
                        <!-- Branch selector -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 font-bold uppercase">الفرع المستهدف:</span>
                            <select x-model="selectedReconcileLocation" class="bg-slate-50 border border-slate-200 text-xs rounded-xl px-4 py-2.5 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-slate-800 font-bold">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <form action="/admin/inventory/reconcile" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="location_id" :value="selectedReconcileLocation" />

                        <div class="overflow-x-auto rounded-2xl border border-slate-200">
                            <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                                <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-4 text-right">اسم الصنف</th>
                                        <th class="px-6 py-4 text-right">الفئة</th>
                                        <th class="px-6 py-4 text-center">الرصيد الدفتري بالمنظومة</th>
                                        <th class="px-6 py-4 text-center w-40">الجرد الفعلي على الرف</th>
                                        <th class="px-6 py-4 text-center">الفارق</th>
                                        <th class="px-6 py-4 text-left">الأثر المالي للفارق</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="product in products" :key="product.id">
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-bold text-slate-800" x-text="product.name"></td>
                                            <td class="px-6 py-4">
                                                <span class="text-[9px] bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-lg font-bold" x-text="product.category"></span>
                                            </td>
                                            <td class="px-6 py-4 text-center font-mono font-bold" x-text="getSystemStock(product.id)"></td>
                                            <td class="px-6 py-3">
                                                <input type="number" :name="'counts[' + product.id + ']'" x-model="counts[product.id]" placeholder="أدخل الجرد الفعلي" step="1" min="0"
                                                       class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-3 py-1.5 text-xs text-center text-slate-800 font-extrabold focus:outline-none transition-all duration-300 text-center" />
                                            </td>
                                            <td class="px-6 py-4 text-center font-mono font-bold">
                                                <span :class="getVariance(product.id) < 0 ? 'text-rose-600' : (getVariance(product.id) > 0 ? 'text-emerald-600' : 'text-slate-400')"
                                                      x-text="(getVariance(product.id) > 0 ? '+' : '') + getVariance(product.id)"></span>
                                            </td>
                                            <td class="px-6 py-4 text-left font-mono font-bold" dir="ltr">
                                                <span :class="getVariance(product.id) < 0 ? 'text-rose-650' : (getVariance(product.id) > 0 ? 'text-emerald-650' : 'text-slate-400')"
                                                      x-text="counts[product.id] === undefined || counts[product.id] === '' ? '0.00 LYD' : (getVarianceCost(product).toFixed(2) + ' LYD')"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Live Summary Box -->
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 flex flex-col sm:flex-row justify-between gap-4">
                            <div>
                                <span class="text-xs text-slate-450 font-bold block uppercase">الأثر المالي الإجمالي لعملية التسوية والجرد الحالية</span>
                                <span class="text-[10px] text-slate-400 font-semibold block mt-1">محسوب بناءً على تكلفة الصنف القياسية (40% من سعر البيع)</span>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <span class="text-xs text-slate-400 font-bold block">إجمالي الفروقات (كمية)</span>
                                    <span class="text-lg font-black block" 
                                          :class="products.reduce((sum, p) => sum + getVariance(p.id), 0) < 0 ? 'text-rose-650' : 'text-emerald-650'"
                                          x-text="products.reduce((sum, p) => sum + getVariance(p.id), 0)"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-slate-400 font-bold block">الأرباح والخسائر المتوقعة</span>
                                    <span class="text-lg font-black block" 
                                          :class="products.reduce((sum, p) => sum + getVarianceCost(p), 0) < 0 ? 'text-rose-650' : 'text-emerald-650'"
                                          x-text="products.reduce((sum, p) => sum + getVarianceCost(p), 0).toFixed(2) + ' د.ل'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs px-6 py-3.5 rounded-xl transition-all shadow-lg shadow-amber-500/10 uppercase tracking-wider">
                                اعتماد الجرد وتسوية الفروقات بالمخازن
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 2: Products Manager CRUD -->
            <div x-show="tab === 'products'" class="grid grid-cols-1 xl:grid-cols-3 gap-8" style="display: none;">
                <!-- Product List -->
                <div class="xl:col-span-2 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-800">أصناف قائمة البيع النشطة</h2>
                    
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-right">اسم الصنف الوجبة</th>
                                    <th class="px-6 py-4 text-right">الفئة</th>
                                    <th class="px-6 py-4 text-left">سعر البيع المقدر</th>
                                    <th class="px-6 py-4 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($products as $prod)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                         <td class="px-6 py-4 flex items-center gap-3 justify-start">
                                             <img src="{{ $prod->image_url ?? 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=100&auto=format&fit=crop' }}" 
                                                  alt="" 
                                                  class="w-10 h-10 rounded-xl object-cover border border-slate-200 flex-shrink-0 shadow-sm" />
                                             <span class="font-bold text-slate-800">{{ $prod->name }}</span>
                                         </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[9px] bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-lg font-bold">{{ $prod->category }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-left font-bold text-slate-800">{{ number_format($prod->base_price, 2) }} د.ل</td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="/admin/products/{{ $prod->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الصنف من القائمة نهائياً؟');">
                                                @csrf
                                                <button type="submit" class="bg-red-50 hover:bg-red-650 text-red-600 hover:text-white border border-red-100 hover:border-red-600 text-[10px] font-bold px-3 py-1.5 rounded-lg transition-all shadow-sm">
                                                    🗑️ حذف الصنف
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">لا يوجد منتجات أو أصناف مسجلة حالياً.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Product Form -->
                <div class="xl:col-span-1 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">إضافة صنف وجبة جديد</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">تسجيل صنف جديد لقائمة المبيعات بالمنظومة</span>
                    </div>

                    <form action="/admin/products" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase">اسم الوجبة / الصنف</label>
                            <input type="text" name="name" required placeholder="مثال: بيتزا مارغريتا عائلية"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold uppercase">سعر البيع (د.ل)</label>
                                <input type="number" step="0.01" name="base_price" required placeholder="24.00"
                                       class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                            </div>
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold uppercase">الفئة</label>
                                <input type="text" name="category" required placeholder="مثال: بيتزا أو برجر"
                                       class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                            </div>
                        </div>

                        <!-- Product Image URL -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase">رابط صورة الوجبة (اختياري)</label>
                            <input type="url" name="image_url" placeholder="https://images.unsplash.com/..."
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-left" dir="ltr" />
                        </div>

                        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-3.5 rounded-xl transition-all shadow-md shadow-amber-500/10 text-xs uppercase tracking-wider font-extrabold">
                            تسجيل وحفظ الصنف بقائمة الطعام
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 3: Ingredients Manager CRUD -->
            <div x-show="tab === 'ingredients'" class="grid grid-cols-1 xl:grid-cols-3 gap-8" style="display: none;">
                
                <!-- Alert box for low stock ingredients -->
                @if(isset($lowStockIngredients) && count($lowStockIngredients) > 0)
                    <div class="xl:col-span-3 bg-amber-50 border border-amber-250 text-amber-900 p-5 rounded-3xl text-xs font-bold flex flex-col gap-3 shadow-sm text-right">
                        <div class="flex items-center gap-2">
                            <span class="text-xl animate-bounce">⚠️</span>
                            <span class="font-extrabold text-sm text-slate-800">تنبيه النواقص: مواد خام تحت حد الطلب الأدنى!</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-1">
                            @foreach($lowStockIngredients as $lowIng)
                                <div class="bg-white border border-amber-100 p-3 rounded-2xl flex justify-between items-center shadow-sm">
                                    <div>
                                        <span class="font-bold text-slate-850 text-sm block">{{ $lowIng['name'] }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block mt-0.5">حد التنبيه: {{ number_format($lowIng['alert_threshold'], 2) }} {{ $lowIng['unit'] }}</span>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-sm font-black text-rose-600 block">{{ number_format($lowIng['current_stock'], 2) }} {{ $lowIng['unit'] }}</span>
                                        <span class="text-[9px] text-slate-400 font-bold block">المخزون الحالي</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Ingredient Catalogue List -->
                <div class="xl:col-span-2 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-800">كتالوج المواد الخام والمكونات الأساسية</h2>
                    
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-right">اسم المكون الخام</th>
                                    <th class="px-6 py-4 text-right">وحدة القياس</th>
                                    <th class="px-6 py-4 text-left">حد الطلب الأدنى (التنبيه)</th>
                                    <th class="px-6 py-4 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($ingredients as $ing)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-slate-800">{{ $ing->name }}</td>
                                        <td class="px-6 py-4 font-mono text-xs text-amber-750 font-bold">{{ $ing->unit }}</td>
                                        <td class="px-6 py-4 text-left font-bold text-slate-800">{{ number_format($ing->alert_threshold, 2) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="/admin/ingredients/{{ $ing->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المكون الخام نهائياً؟');">
                                                @csrf
                                                <button type="submit" class="bg-red-50 hover:bg-red-650 text-red-600 hover:text-white border border-red-100 hover:border-red-600 text-[10px] font-bold px-3 py-1.5 rounded-lg transition-all shadow-sm">
                                                    🗑️ حذف المكون
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">لا يوجد مواد خام مسجلة حالياً بالمنظومة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Ingredient Form -->
                <div class="xl:col-span-1 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">تسجيل مادة خام جديدة</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">إضافة مواد ومكونات أساسية للمطبخ لتتبع استهلاكها</span>
                    </div>

                    <form action="/admin/ingredients" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase">اسم المادة الخام</label>
                            <input type="text" name="name" required placeholder="مثال: لحم بقري مفروم أو طماطم"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold uppercase">وحدة القياس</label>
                                <input type="text" name="unit" required placeholder="مثال: كجم، قطعة، لتر"
                                       class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                            </div>
                            <div class="space-y-1.5 text-right">
                                <label class="text-xs text-slate-500 font-bold uppercase">حد التنبيه الأدنى</label>
                                <input type="number" step="0.01" name="alert_threshold" required placeholder="10.00"
                                       class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-3.5 rounded-xl transition-all shadow-md shadow-amber-500/10 text-xs uppercase tracking-wider font-extrabold">
                            تسجيل المادة الخام
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 4: Recipes Map CRUD -->
            <div x-show="tab === 'recipes'" class="grid grid-cols-1 xl:grid-cols-3 gap-8" style="display: none;">
                <!-- Recipe Map details -->
                <div class="xl:col-span-2 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6 text-right">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">تركيبات ومكونات الوجبات (الوصفات)</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">تحديد المكونات والكميات المستهلكة من المخزن لكل وجبة مباعة</span>
                    </div>

                    <div class="space-y-6">
                        @foreach($products as $prod)
                            <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl space-y-4 shadow-sm text-right">
                                <div class="flex justify-between items-center border-b border-slate-200 pb-2">
                                    <span class="font-extrabold text-sm text-amber-700">{{ $prod->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">{{ $prod->category }}</span>
                                </div>

                                <div class="space-y-2">
                                    @forelse($prod->ingredients as $recipeItem)
                                        <div class="flex justify-between items-center text-xs bg-white p-2.5 rounded-xl border border-slate-200 shadow-sm" dir="rtl">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                <span class="font-bold text-slate-800">{{ $recipeItem->name }}</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-mono text-slate-650 font-bold" dir="ltr">{{ number_format($recipeItem->pivot->quantity_needed, 4) }} {{ $recipeItem->unit }}</span>
                                                <form action="/admin/recipes/{{ $prod->id }}/{{ $recipeItem->id }}/delete" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-red-650 hover:text-red-750 text-[10px] font-bold">❌ إلغاء الربط</button>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-[10px] text-slate-400 font-medium block italic py-2">لا توجد مواد خام مرتبطة أو وصفة لهذا الصنف بعد.</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Link/Assign Ingredient to Product Form -->
                <div class="xl:col-span-1 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">ربط مادة خام بوجبة مباعة</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">ربط مادة خام من المخزن بمنتج مبيعات وتحديد مقدار الاستهلاك</span>
                    </div>

                    <form action="/admin/recipes" method="POST" class="space-y-4">
                        @csrf
                        <!-- Product Selection -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase">الصنف الوجبة المستهدف</label>
                            <select name="product_id" required class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300">
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Ingredient Selection -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase">المادة الخام المستهلكة</label>
                            <select name="ingredient_id" required class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300">
                                @foreach($ingredients as $ing)
                                    <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quantity Needed -->
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase">الكمية المستهلكة لكل عملية بيع</label>
                            <input type="number" step="0.0001" name="quantity_needed" required placeholder="0.1500"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                            <span class="text-[9px] text-slate-400 block mt-1">ستقوم المنظومة بخصم هذه الكمية تلقائياً من مخزون المادة الخام عند إتمام أي عملية بيع لهذا الصنف.</span>
                        </div>

                        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-3.5 rounded-xl transition-all shadow-md shadow-amber-500/10 text-xs uppercase tracking-wider font-extrabold">
                            ربط وحفظ مكونات الوصفة
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 5: Branches/Locations Manager CRUD -->
            <div x-show="tab === 'locations'" class="grid grid-cols-1 xl:grid-cols-3 gap-8" style="display: none;">
                <!-- Locations List -->
                <div class="xl:col-span-2 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-800">الفروع ومواقع نقاط البيع المسجلة</h2>
                    
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-right">اسم الفرع / الموقع</th>
                                    <th class="px-6 py-4 text-right">المعرف الفريد UUID</th>
                                    <th class="px-6 py-4 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($locations as $loc)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-slate-800">{{ $loc->name }}</td>
                                        <td class="px-6 py-4 font-mono text-xs text-slate-400">{{ $loc->id }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="/admin/locations/{{ $loc->id }}/delete" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الفرع نهائياً؟');">
                                                @csrf
                                                <button type="submit" class="bg-red-50 hover:bg-red-650 text-red-600 hover:text-white border border-red-100 hover:border-red-600 text-[10px] font-bold px-3 py-1.5 rounded-lg transition-all shadow-sm">
                                                    🗑️ حذف الفرع
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-sm text-slate-400">لا يوجد فروع أو مواقع مسجلة حالياً بالمنظومة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Location Form -->
                <div class="xl:col-span-1 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">إضافة فرع جديد بالمنظومة</h2>
                        <span class="text-[10px] text-slate-450 font-bold block mt-1">تسجيل فرع أو مستودع أو نقطة بيع جديدة</span>
                    </div>

                    <form action="/admin/locations" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5 text-right">
                            <label class="text-xs text-slate-500 font-bold uppercase">اسم الفرع / الموقع الجديد</label>
                            <input type="text" name="name" required placeholder="مثال: فرع طرابلس أو بنغازي الرئيسي"
                                   class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3 text-xs text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                        </div>

                        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-3.5 rounded-xl transition-all shadow-md shadow-amber-500/10 text-xs uppercase tracking-wider font-extrabold">
                            إنشاء وحفظ الفرع الجديد
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 6: Transactions Audit Ledger History -->
            <div x-show="tab === 'history'" style="display: none;">
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4 text-right">
                    <h2 class="text-sm font-bold text-slate-800">سجل حركات وعمليات المخازن التفصيلي (Audit Ledger)</h2>
                    
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-right text-sm text-slate-650" dir="rtl">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-right">رمز الحركة</th>
                                    <th class="px-6 py-4 text-right">اسم الصنف</th>
                                    <th class="px-6 py-4 text-right">الفرع / الموقع</th>
                                    <th class="px-6 py-4 text-left">الكمية</th>
                                    <th class="px-6 py-4 text-left">تكلفة الوحدة</th>
                                    <th class="px-6 py-4 text-center">نوع العملية</th>
                                    <th class="px-6 py-4 text-right">التاريخ والوقت</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($transactions as $tx)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs text-slate-400">{{ substr($tx->id, 0, 8) }}...</td>
                                        <td class="px-6 py-4 font-bold text-slate-800">{{ $tx->product->name }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-600">{{ $tx->location->name }}</td>
                                        <td class="px-6 py-4 text-left font-bold {{ $tx->quantity > 0 ? 'text-emerald-650' : 'text-red-650' }}" dir="ltr">
                                            {{ $tx->quantity > 0 ? '+' : '' }}{{ number_format($tx->quantity, 0) }}
                                        </td>
                                        <td class="px-6 py-4 text-left font-mono text-slate-800" dir="ltr">{{ number_format($tx->unit_cost, 2) }} LYD</td>
                                        <td class="px-6 py-4 text-center">
                                            @if(($tx->type ?? '') === 'restock')
                                                <span class="text-[9px] px-2.5 py-0.5 rounded font-black uppercase border bg-emerald-50 text-emerald-700 border-emerald-200">
                                                    توريد (RESTOCK)
                                                </span>
                                            @elseif(($tx->type ?? '') === 'sale')
                                                <span class="text-[9px] px-2.5 py-0.5 rounded font-black uppercase border bg-blue-50 text-blue-700 border-blue-200">
                                                    مبيعات (SALE)
                                                </span>
                                            @elseif(($tx->type ?? '') === 'waste')
                                                <span class="text-[9px] px-2.5 py-0.5 rounded font-black uppercase border bg-rose-50 text-rose-700 border-rose-200">
                                                    هدر/تالف (WASTE)
                                                </span>
                                            @elseif(($tx->type ?? '') === 'adjustment')
                                                <span class="text-[9px] px-2.5 py-0.5 rounded font-black uppercase border bg-amber-50 text-amber-700 border-amber-250">
                                                    تسوية زيادة (ADJUST)
                                                </span>
                                            @else
                                                <span class="text-[9px] px-2.5 py-0.5 rounded font-black uppercase border bg-slate-50 text-slate-700 border-slate-200">
                                                    {{ strtoupper($tx->type ?? ($tx->quantity > 0 ? 'RESTOCK' : 'SALE')) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-400 font-mono">{{ $tx->created_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">لا يوجد قيود أو حركات مخازن مسجلة بالدفاتر حالياً.</td>
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
