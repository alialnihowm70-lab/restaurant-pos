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
                <button @click="showCart = true" class="relative p-2.5 bg-slate-100 rounded-xl transition hover:bg-slate-200">
                    <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                    <span x-show="cartCount() > 0" x-text="cartCount()" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center shadow-md"></span>
                </button>
            </div>
            <p class="text-sm text-slate-500 font-medium">اطلب منيو المطعم مباشرة من جوالك</p>
        </div>
    </div>

    <!-- Categories + Products -->
    <div class="max-w-md mx-auto px-4 pt-4 pb-40">

        <div class="flex gap-2.5 overflow-x-auto pb-2 mb-4" x-show="categories.length > 0">
            <button @click="selectedCategory = 'all'" class="cat-chip" :class="selectedCategory === 'all' ? 'cat-chip-active' : 'cat-chip-inactive'">الكل</button>
            <template x-for="cat in categories" :key="cat">
                <button @click="selectedCategory = cat" class="cat-chip" :class="selectedCategory === cat ? 'cat-chip-active' : 'cat-chip-inactive'" x-text="cat"></button>
            </template>
        </div>

        <div class="grid grid-cols-2 gap-3" x-show="filteredProducts().length > 0">
            <template x-for="(product, idx) in filteredProducts()" :key="product.id">
                <div class="menu-card fade-up" :style="'animation-delay:' + (idx * 0.04) + 's'">
                    <div class="w-full aspect-square overflow-hidden bg-slate-50 relative" x-init="$el.addEventListener('click', () => addToCart(product))">
                        <img :src="product.image_url || ''" :alt="product.name" class="w-full h-full object-cover" @error.once="$el.style.display='none'; $el.nextElementSibling.style.display='flex'">
                        <div class="img-placeholder w-full h-full" style="display:none">🍔</div>
                        <div x-show="getItemQty(product.id) > 0" class="absolute top-2 left-2 bg-[#166534] text-white text-[10px] font-black w-6 h-6 rounded-full flex items-center justify-center shadow-md shadow-green-900/20" x-text="getItemQty(product.id)"></div>
                    </div>
                    <div class="p-3" x-init="$el.addEventListener('click', () => addToCart(product))">
                        <h3 class="font-black text-sm text-slate-900 leading-tight mb-0.5" x-text="product.name"></h3>
                        <p class="text-[10px] text-slate-400 font-medium truncate mb-2" x-text="product.category"></p>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-sm text-[#166534]" x-text="Number(product.base_price).toFixed(2) + ' د.ل'"></span>
                            <button @click.stop="addToCart(product)" class="w-8 h-8 rounded-full bg-[#166534] text-white flex items-center justify-center font-bold text-lg shadow-sm transition active:scale-90">+</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filteredProducts().length === 0" class="text-center py-16 text-slate-400">
            <p class="font-bold text-sm">لا توجد منتجات</p>
        </div>
    </div>

    <!-- Floating Cart Button -->
    <div x-show="cartCount() > 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-y-full opacity-0" x-transition:enter-end="transform translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-y-0 opacity-100" x-transition:leave-end="transform translate-y-full opacity-0"
         class="fixed bottom-0 left-0 right-0 p-4 z-30" style="background:linear-gradient(transparent, #f8fafc 25%);">
        <button @click="showCart = true" class="btn-green w-full max-w-md mx-auto rounded-2xl p-4 flex items-center justify-between shadow-lg shadow-green-900/20">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-xl">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                </div>
                <div class="text-right">
                    <p class="text-xs text-white/70 font-semibold">طلبي</p>
                    <p class="font-black text-sm text-white" x-text="cartCount() + ' أصناف — ' + cartTotal().toFixed(2) + ' د.ل'"></p>
                </div>
            </div>
            <div class="flex items-center gap-1 bg-white text-[#166534] px-4 py-2 rounded-xl font-black text-xs">
                <span>عرض السلة</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </button>
    </div>

    <!-- Cart Sheet -->
    <div x-show="showCart" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" x-cloak x-transition.opacity>
        <div @click.away="showCart = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-y-0" x-transition:leave-end="transform translate-y-full"
             class="cart-sheet w-full max-w-md max-h-[85vh] overflow-y-auto">
            <div class="sticky top-0 bg-white z-10 px-5 pt-4 pb-3 border-b border-slate-100 rounded-[24px] rounded-b-none">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-black text-slate-900">سلة الطلبات</h2>
                    <button @click="showCart = false" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 transition hover:bg-slate-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <div class="px-5 pt-3 pb-5 space-y-4">
                <div x-show="cart.length === 0" class="text-center py-12">
                    <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-[#166534]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                        </svg>
                    </div>
                    <p class="text-slate-500 font-bold text-sm">السلة فاضية</p>
                    <p class="text-xs text-slate-400 mt-1">أضف وجبات من المنيو للبدء</p>
                </div>

                <div x-show="cart.length > 0" class="space-y-2">
                    <template x-for="(item, idx) in cart" :key="item.id">
                        <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
                            <div class="flex-1 min-w-0 ml-3">
                                <h4 class="font-black text-slate-900 text-sm truncate" x-text="item.name"></h4>
                                <p class="text-xs font-bold text-[#166534] mt-0.5" x-text="Number(item.price * item.quantity).toFixed(2) + ' د.ل'"></p>
                            </div>
                            <div class="flex items-center gap-1.5 bg-white rounded-lg p-0.5 border border-slate-200 shrink-0">
                                <button @click="changeQty(item.id, -1)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm hover:bg-slate-200 transition">−</button>
                                <span class="w-7 text-center font-black text-sm text-slate-900" x-text="item.quantity"></span>
                                <button @click="changeQty(item.id, 1)" class="w-8 h-8 rounded-lg bg-[#166534] text-white flex items-center justify-center font-bold text-sm hover:bg-green-800 transition">+</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="cart.length > 0" class="space-y-3 border-t border-slate-100 pt-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">الاسم <span class="text-red-500">*</span></label>
                        <input type="text" x-model="customerName" placeholder="اسمك الكريم" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#166534] focus:ring-2 focus:ring-green-100 transition font-semibold">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5">نوع الطلب</label>
                            <select x-model="orderType" class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-[#166534] focus:ring-2 focus:ring-green-100 transition font-semibold">
                                <option value="table">🍽️ صالة</option>
                                <option value="takeaway">🛍️ سفري</option>
                            </select>
                        </div>
                        <div x-show="orderType === 'table'">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5">رقم الطاولة <span class="text-red-500">*</span></label>
                            <input type="number" x-model="tableNumber" placeholder="رقم" class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#166534] focus:ring-2 focus:ring-green-100 transition font-semibold text-center">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">ملاحظات (اختياري)</label>
                        <textarea x-model="orderNotes" rows="2" placeholder="مثال: بدون بصل، زيادة جبن..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#166534] focus:ring-2 focus:ring-green-100 transition font-semibold resize-none"></textarea>
                    </div>
                    <button @click="submitOrder()" :disabled="submitting || !customerName || (orderType === 'table' && !tableNumber)"
                            :class="(submitting || !customerName || (orderType === 'table' && !tableNumber)) ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'btn-green'"
                            class="btn-green w-full py-4 rounded-2xl text-sm flex items-center justify-center gap-2 shadow-lg shadow-green-900/15">
                        <span x-show="submitting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span x-text="submitting ? 'جاري الإرسال...' : 'إرسال الطلب  ' + cartTotal().toFixed(2) + ' د.ل'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccess" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 px-4" x-cloak x-transition.opacity>
        <div class="bg-white max-w-sm w-full rounded-3xl p-8 text-center shadow-2xl fade-up">
            <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-[#166534]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-black text-slate-900 mb-2">تم إرسال الطلب!</h3>
            <p class="text-sm text-slate-500 mb-8 leading-relaxed">طلبك وصل للكاشير. رح يبدأ التحضير قريباً. صحتين وعافية!</p>
            <button @click="showSuccess = false; clearCart()" class="btn-green w-full py-3.5 rounded-2xl text-sm shadow-lg shadow-green-900/15">تم، شكراً!</button>
        </div>
    </div>

    <script>
        function menuApp() {
            return {
                products: @json($products),
                categories: @json($categories),
                selectedCategory: 'all',
                cart: [],
                customerName: '',
                orderType: 'table',
                tableNumber: '',
                orderNotes: '',
                showCart: false,
                submitting: false,
                showSuccess: false,
                apiBase: '',

                initMenu() {},

                filteredProducts() {
                    return this.selectedCategory === 'all'
                        ? this.products
                        : this.products.filter(p => p.category === this.selectedCategory);
                },

                addToCart(product) {
                    const existing = this.cart.find(item => item.id === product.id);
                    if (existing) { existing.quantity++; }
                    else { this.cart.push({ id: product.id, name: product.name, price: product.base_price, quantity: 1 }); }
                },

                getItemQty(productId) {
                    const item = this.cart.find(i => i.id === productId);
                    return item ? item.quantity : 0;
                },

                changeQty(productId, amount) {
                    const item = this.cart.find(i => i.id === productId);
                    if (!item) return;
                    item.quantity += amount;
                    if (item.quantity <= 0) { this.cart = this.cart.filter(i => i.id !== productId); }
                },

                cartCount() { return this.cart.reduce((t, i) => t + i.quantity, 0); },
                cartTotal() { return this.cart.reduce((t, i) => t + (i.price * i.quantity), 0); },

                clearCart() { this.cart = []; this.orderNotes = ''; this.customerName = ''; this.tableNumber = ''; },

                async submitOrder() {
                    if (this.submitting) return;
                    this.submitting = true;
                    try {
                        const res = await fetch(this.apiBase + '/api/customer/orders', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                customer_name: this.customerName,
                                table_number: this.orderType === 'table' ? parseInt(this.tableNumber) : null,
                                order_type: this.orderType,
                                notes: this.orderNotes,
                                items: this.cart,
                                total_amount: this.cartTotal()
                            })
                        });
                        const result = await res.json();
                        if (result.success) { this.showCart = false; this.showSuccess = true; }
                        else { alert('فشل الإرسال: ' + (result.error || 'خطأ غير معروف')); }
                    } catch (err) {
                        alert('تعذر الاتصال بالسيرفر. تأكد من اتصالك بالإنترنت.');
                    } finally { this.submitting = false; }
                }
            };
        }
    </script>
</body>
</html>
