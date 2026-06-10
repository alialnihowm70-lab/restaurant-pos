<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Bello Smash - قائمة الطعام</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Cairo', sans-serif; background: #f8fafc; color: #1e293b; }
        .menu-card { background: white; border-radius: 16px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; overflow: hidden; }
        .menu-card:active { background: #f8fafc; }
        .cat-chip { padding: 8px 18px; border-radius: 100px; font-size: 13px; font-weight: 700; transition: all 0.2s; white-space: nowrap; border: none; cursor: pointer; }
        .cat-chip-active { background: #166534; color: white; box-shadow: 0 2px 8px rgba(22,101,52,0.25); }
        .cat-chip-inactive { background: #f1f5f9; color: #475569; }
        .cart-sheet { background: white; border-radius: 24px 24px 0 0; box-shadow: 0 -8px 40px rgba(0,0,0,0.1); }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        input, select, textarea { font-family: 'Cairo', sans-serif; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeUp 0.3s ease-out forwards; }
        .btn-green { background: #166534; color: white; font-weight: 900; border: none; transition: all 0.15s; }
        .btn-green:active { transform: scale(0.97); background: #15803d; }
        .btn-green:disabled { background: #cbd5e1; color: #94a3b8; cursor: not-allowed; transform: none; }
        .img-placeholder { background: linear-gradient(135deg, #f0fdf4, #dcfce7); display: flex; align-items: center; justify-content: center; font-size: 28px; color: #166534; }
    </style>
</head>
<body x-data="menuApp()" x-init="initMenu()" class="min-h-screen">

    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-4 pt-6 pb-4">
        <div class="max-w-md mx-auto">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#166534] flex items-center justify-center shadow-sm">
                        <span class="text-xl font-black text-white">B</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-slate-900" style="font-family:'Playfair Display',serif;">Bello Smash</h1>
                        <p class="text-[11px] text-slate-500 font-semibold -mt-0.5">Burger &amp; More</p>
                    </div>
                </div>
            </div>
            <p class="text-sm text-slate-500 font-medium">تصفح قائمة مأكولاتنا اللذيذة وأطباقنا المميزة</p>
        </div>
    </div>

    <!-- Categories + Products -->
    <div class="max-w-md mx-auto px-4 pt-4 pb-12">

        <div class="flex gap-2.5 overflow-x-auto pb-2 mb-4" x-show="categories.length > 0">
            <button @click="selectedCategory = 'all'" class="cat-chip" :class="selectedCategory === 'all' ? 'cat-chip-active' : 'cat-chip-inactive'">الكل</button>
            <template x-for="cat in categories" :key="cat">
                <button @click="selectedCategory = cat" class="cat-chip" :class="selectedCategory === cat ? 'cat-chip-active' : 'cat-chip-inactive'" x-text="cat"></button>
            </template>
        </div>

        <div class="grid grid-cols-2 gap-3" x-show="filteredProducts().length > 0">
            <template x-for="(product, idx) in filteredProducts()" :key="product.id">
                <div class="menu-card fade-up" :style="'animation-delay:' + (idx * 0.04) + 's'">
                    <div class="w-full aspect-square overflow-hidden bg-slate-50 relative">
                        <img :src="product.image_url || ''" :alt="product.name" class="w-full h-full object-cover" x-on:error.once="$el.style.display='none'; $el.nextElementSibling.style.display='flex'">
                        <div class="img-placeholder w-full h-full" style="display:none">🍔</div>
                    </div>
                    <div class="p-3">
                        <h3 class="font-black text-sm text-slate-900 leading-tight mb-0.5" x-text="product.name"></h3>
                        <p class="text-[10px] text-slate-400 font-medium truncate mb-2" x-text="product.category"></p>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-sm text-[#166534]" x-text="Number(product.base_price).toFixed(2) + ' د.ل'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filteredProducts().length === 0" class="text-center py-16 text-slate-400">
            <p class="font-bold text-sm">لا توجد منتجات</p>
        </div>
    </div>



    <script>
        function menuApp() {
            return {
                products: @json($products),
                categories: @json($categories),
                selectedCategory: 'all',

                initMenu() {},

                filteredProducts() {
                    return this.selectedCategory === 'all'
                        ? this.products
                        : this.products.filter(p => p.category === this.selectedCategory);
                }
            };
        }
    </script>
</body>
</html>
