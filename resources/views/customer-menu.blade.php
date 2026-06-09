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
        body { font-family: 'Cairo', sans-serif; background: #faf7f2; color: #1a1a2e; }
        .menu-card { background: white; border-radius: 20px; box-shadow: 0 2px 20px rgba(0,0,0,0.06); transition: all 0.3s cubic-bezier(0.16,1,0.3,1); border: 1px solid rgba(0,0,0,0.04); }
        .menu-card:active { transform: scale(0.97); }
        .menu-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
        .qty-btn { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; transition: all 0.2s; border: none; cursor: pointer; }
        .cat-chip { padding: 8px 20px; border-radius: 100px; font-size: 13px; font-weight: 700; transition: all 0.25s; white-space: nowrap; border: none; cursor: pointer; }
        .cat-chip-active { background: #1a1a2e; color: #fbbf24; box-shadow: 0 4px 15px rgba(26,26,46,0.2); }
        .cat-chip-inactive { background: white; color: #64748b; border: 1px solid #e2e8f0; }
        .hero-gradient { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); }
        .floating-btn { box-shadow: 0 4px 25px rgba(251,191,36,0.4); transition: all 0.3s; }
        .floating-btn:active { transform: scale(0.95); }
        .cart-sheet { background: white; border-radius: 28px 28px 0 0; box-shadow: 0 -10px 40px rgba(0,0,0,0.12); }
        .price-tag { background: linear-gradient(135deg, #fbbf24, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeUp 0.4s cubic-bezier(0.16,1,0.3,1) forwards; }
        @keyframes pulse-dot { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: 0.5; } }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        input, select, textarea { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body x-data="menuApp()" x-init="initMenu()" class="min-h-screen">

    <div class="hero-gradient text-white px-5 pt-8 pb-12 rounded-b-[32px] shadow-xl relative overflow-hidden">
        <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;border-radius:50%;background:rgba(251,191,36,0.08);"></div>
        <div style="position:absolute;bottom:-60px;left:-30px;width:160px;height:160px;border-radius:50%;background:rgba(239,68,68,0.06);"></div>
        <div class="max-w-md mx-auto relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-400 flex items-center justify-center shadow-lg shadow-amber-500/30">
                        <span class="text-2xl font-black text-slate-900">B</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tight" style="font-family:'Playfair Display',serif;">Bello Smash</h1>
                        <p class="text-xs text-amber-300/80 font-semibold -mt-0.5">Burger &amp; More</p>
                    </div>
                </div>
                <button @click="showCart = true" class="relative p-2.5 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/10 transition hover:bg-white/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                    <span x-show="cartCount() > 0" x-text="cartCount()" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center shadow-lg" style="animation:pulse-dot 2s infinite;"></span>
                </button>
            </div>
            <p class="text-sm text-slate-300 font-medium leading-relaxed">اطلب منيو المطعم مباشرة من جوالك واستمتع بتجربة طعام فريدة 🍔</p>
        </div>
    </div>

    <div class="max-w-md mx-auto px-4 -mt-4 relative z-20">
        <div class="flex gap-2.5 overflow-x-auto pb-1 scrollbar-none mb-5" x-show="categories.length > 0">
            <button @click="selectedCategory = 'all'" class="cat-chip" :class="selectedCategory === 'all' ? 'cat-chip-active' : 'cat-chip-inactive'">🔥 الكل</button>
            <template x-for="cat in categories" :key="cat">
                <button @click="selectedCategory = cat" class="cat-chip" :class="selectedCategory === cat ? 'cat-chip-active' : 'cat-chip-inactive'" x-text="cat"></button>
            </template>
        </div>

        <div class="flex items-center gap-3 mb-4">
            <div class="h-px flex-grow bg-gradient-to-r from-transparent via-amber-300 to-transparent"></div>
            <span class="text-xs font-black text-slate-400 uppercase tracking-widest" x-text="selectedCategory === 'all' ? 'كل الأصناف' : selectedCategory"></span>
            <div class="h-px flex-grow bg-gradient-to-r from-transparent via-amber-300 to-transparent"></div>
        </div>

        <div class="space-y-3 pb-36">
            <template x-for="(product, idx) in filteredProducts()" :key="product.id">
                <div class="menu-card p-3 flex gap-4 cursor-pointer fade-up" :style="'animation-delay:' + (idx * 0.05) + 's'" @click="addToCart(product)">
                    <div class="w-24 h-24 rounded-2xl overflow-hidden shrink-0 bg-amber-50 relative shadow-inner">
                        <img :src="product.image_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&auto=format&fit=crop'" 
                             :alt="product.name" class="w-full h-full object-cover transition duration-500 hover:scale-110" />
                        <div x-show="getItemQty(product.id) > 0" class="absolute top-1 right-1 bg-amber-400 text-slate-900 text-[9px] font-black w-5 h-5 rounded-full flex items-center justify-center shadow-md">
                            <span x-text="getItemQty(product.id)"></span>
                        </div>
                    </div>
                    <div class="flex flex-col justify-between flex-1 min-w-0 py-1">
                        <div>
                            <h3 class="font-black text-slate-900 text-sm leading-tight" x-text="product.name"></h3>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5 truncate" x-text="product.category"></p>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="font-black text-lg price-tag" x-text="Number(product.base_price).toFixed(2) + ' د.ل'"></span>
                            <div x-show="getItemQty(product.id) === 0" class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center font-black text-lg shadow-md transition hover:bg-slate-800">+</div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="cartCount() > 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-y-full opacity-0" x-transition:enter-end="transform translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-y-0 opacity-100" x-transition:leave-end="transform translate-y-full opacity-0"
         class="fixed bottom-0 left-0 right-0 p-4 z-30" style="background:linear-gradient(transparent, rgba(250,247,242,0.95) 30%);">
        <div @click="showCart = true" class="max-w-md mx-auto bg-slate-900 rounded-2xl p-4 flex items-center justify-between text-white floating-btn cursor-pointer">
            <div class="flex items-center gap-3">
                <div class="bg-amber-400 p-2 rounded-xl">
                    <svg class="w-6 h-6 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold">طلبي</p>
                    <p class="font-black text-sm" x-text="cartCount() + ' أصناف — ' + cartTotal().toFixed(2) + ' د.ل'"></p>
                </div>
            </div>
            <div class="flex items-center gap-1 bg-amber-400 text-slate-900 px-4 py-2 rounded-xl font-black text-xs">
                <span>عرض السلة</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </div>
    </div>

    <div x-show="showCart" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm" x-cloak x-transition.opacity>
        <div @click.away="showCart = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-y-0" x-transition:leave-end="transform translate-y-full"
             class="cart-sheet w-full max-w-md max-h-[88vh] overflow-y-auto">
            <div class="sticky top-0 bg-white z-10 px-6 pt-5 pb-4 border-b border-slate-100 rounded-[28px] rounded-b-none">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                        </svg>
                        سلة الطلبات
                    </h2>
                    <button @click="showCart = false" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition hover:bg-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="w-10 h-1 rounded-full bg-slate-200 mx-auto mt-3"></div>
            </div>
            <div class="px-6 pt-4 pb-6 space-y-5">
                <div x-show="cart.length === 0" class="text-center py-12">
                    <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                        </svg>
                    </div>
                    <p class="text-slate-500 font-bold text-sm">السلة فاضية</p>
                    <p class="text-xs text-slate-400 mt-1">أضف وجبات من المنيو للبدء</p>
                </div>
                <div x-show="cart.length > 0" class="space-y-3">
                    <template x-for="(item, idx) in cart" :key="item.id">
                        <div class="flex items-center justify-between bg-slate-50 rounded-2xl p-3.5">
                            <div>
                                <h4 class="font-black text-slate-900 text-sm" x-text="item.name"></h4>
                                <p class="text-xs font-bold text-amber-600 mt-0.5" x-text="Number(item.price * item.quantity).toFixed(2) + ' د.ل'"></p>
                            </div>
                            <div class="flex items-center gap-2 bg-white rounded-xl p-1 shadow-sm border border-slate-100">
                                <button @click="changeQty(item.id, -1)" class="qty-btn bg-slate-100 text-slate-600 hover:bg-slate-200">−</button>
                                <span class="w-8 text-center font-black text-sm text-slate-900" x-text="item.quantity"></span>
                                <button @click="changeQty(item.id, 1)" class="qty-btn bg-slate-900 text-amber-400 hover:bg-slate-800">+</button>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="cart.length > 0" class="space-y-4 border-t border-slate-100 pt-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">الاسم <span class="text-red-500">*</span></label>
                        <input type="text" x-model="customerName" placeholder="اسمك الكريم" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100 transition font-semibold">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5">نوع الطلب</label>
                            <select x-model="orderType" class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm text-slate-900 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100 transition font-semibold">
                                <option value="table">🍽️ صالة</option>
                                <option value="takeaway">🛍️ سفري</option>
                            </select>
                        </div>
                        <div x-show="orderType === 'table'">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5">رقم الطاولة <span class="text-red-500">*</span></label>
                            <input type="number" x-model="tableNumber" placeholder="رقم" class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100 transition font-semibold text-center">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">ملاحظات (اختياري)</label>
                        <textarea x-model="orderNotes" rows="2" placeholder="مثال: بدون بصل، زيادة جبن..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100 transition font-semibold resize-none"></textarea>
                    </div>
                    <button @click="submitOrder()" :disabled="submitting || !customerName || (orderType === 'table' && !tableNumber)"
                            :class="(submitting || !customerName || (orderType === 'table' && !tableNumber)) ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-slate-900 text-amber-400 hover:bg-slate-800 active:scale-[0.98]'"
                            class="w-full py-4 rounded-2xl transition font-black text-sm flex items-center justify-center gap-2 shadow-lg">
                        <span x-show="submitting" class="w-5 h-5 border-2 border-amber-400/30 border-t-amber-400 rounded-full animate-spin"></span>
                        <span x-text="submitting ? 'جاري الإرسال...' : 'إرسال الطلب ✓  ' + cartTotal().toFixed(2) + ' د.ل'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showSuccess" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4" x-cloak x-transition.opacity>
        <div class="bg-white max-w-sm w-full rounded-3xl p-8 text-center shadow-2xl fade-up">
            <div class="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-5 shadow-inner">
                <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-black text-slate-900 mb-2">تم إرسال الطلب! 🎉</h3>
            <p class="text-sm text-slate-500 mb-8 leading-relaxed">طلبك وصل للكاشير. رح يبدأ التحضير قريباً. صحتين وعافية!</p>
            <button @click="showSuccess = false; clearCart();" class="w-full py-3.5 bg-slate-900 hover:bg-slate-800 text-amber-400 font-black rounded-2xl transition text-sm shadow-lg active:scale-[0.98]">تم، شكراً!</button>
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
