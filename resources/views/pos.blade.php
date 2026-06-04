<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدينة POS - واجهة الكاشير</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;650;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;850&display=swap" rel="stylesheet">
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
    <!-- Custom styling -->
    <style>
        body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            background-image: radial-gradient(circle at 0% 0%, rgba(245, 158, 11, 0.04) 0%, transparent 45%),
                              radial-gradient(circle at 100% 100%, rgba(99, 102, 241, 0.04) 0%, transparent 45%);
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-animate {
            animation: pageFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes cardFadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(6px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .card-animate {
            animation: cardFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 6px;
        }
        /* Frosted Glass Panels */
        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
        /* Paper receipt styling for high-end preview */
        .paper-receipt {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            font-family: monospace;
            position: relative;
            border: 1px solid #e2e8f0;
        }
        .paper-receipt::before {
            content: "";
            position: absolute;
            top: -8px;
            left: 0;
            right: 0;
            height: 8px;
            background-size: 16px 8px;
            background-repeat: repeat-x;
            background-image: linear-gradient(45deg, transparent 33.333%, #ffffff 33.333%, #ffffff 66.667%, transparent 66.667%),
                              linear-gradient(-45deg, transparent 33.333%, #ffffff 33.333%, #ffffff 66.667%, transparent 66.667%);
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-receipt-card, #printable-receipt-card * {
                visibility: visible;
            }
            #printable-receipt-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 80mm;
                margin: 0;
                padding: 10px;
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                border: none !important;
            }
            #printable-receipt-card::before {
                display: none !important;
            }
            @page {
                size: auto;
                margin: 0mm;
            }
        }
    </style>
</head>
<body class="h-screen overflow-hidden flex relative page-animate" x-data="posApp()">

    <!-- Decorative Glow Circles -->
    <div class="absolute -top-40 -right-40 w-[600px] h-[600px] bg-amber-500/5 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/3 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] bg-indigo-500/5 rounded-full blur-[150px] pointer-events-none"></div>

    <!-- Unified left navigation sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-grow flex flex-col overflow-hidden h-screen relative z-10">

        <!-- Top Navigation Bar -->
        <header class="glass-header px-6 py-4 flex flex-col lg:flex-row items-center justify-between gap-4 flex-shrink-0">
            <div class="flex items-center gap-4 text-right">
                <div class="w-12 h-12 rounded-[20px] bg-gradient-to-tr from-amber-500 via-orange-500 to-red-500 flex items-center justify-center font-black text-slate-950 shadow-lg shadow-orange-550/20 text-2xl animate-pulse">
                    M
                </div>
                <div>
                    <h1 class="text-base font-black tracking-tight text-slate-900 leading-none">منظومة المدينة POS</h1>
                    <span class="text-[10px] text-amber-600 font-extrabold uppercase tracking-wider block mt-1">واجهة الكاشير ونقاط البيع المزامنة</span>
                </div>
            </div>

            <!-- Sync & Connection Info -->
            <div class="flex flex-wrap items-center gap-4" dir="rtl">
                <!-- Active Location Selector -->
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider">الفرع:</span>
                    <select x-model="selectedLocation" @change="changeLocation()" class="bg-white/80 border border-slate-200 text-xs rounded-2xl px-4 py-2.5 focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-slate-800 font-bold shadow-sm transition-all">
                        <option value="">اختر الفرع</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Printer IP Configuration -->
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider">طابعة IP:</span>
                    <input type="text" x-model="printerIp" placeholder="192.168.1.100" class="w-36 bg-white/80 border border-slate-200 text-xs rounded-2xl px-4 py-2.5 focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-slate-800 text-center font-mono shadow-sm transition-all" dir="ltr" />
                </div>

                <!-- Network Connection Status -->
                <div class="flex items-center gap-2 px-4 py-2 rounded-full text-[10px] font-black tracking-wider border shadow-sm"
                     :class="isOnline ? 'bg-emerald-50 text-emerald-700 border-emerald-250' : 'bg-rose-50 text-rose-700 border-rose-250'">
                    <span class="w-2.5 h-2.5 rounded-full" :class="isOnline ? 'bg-emerald-550 animate-pulse' : 'bg-rose-550'"></span>
                    <span x-text="isOnline ? 'متصل بالشبكة' : 'الوضع المحلي'"></span>
                </div>

                <!-- Sync Button -->
                <button @click="triggerManualSync()" :disabled="syncing" class="relative bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 disabled:from-slate-200 disabled:to-slate-200 text-slate-950 disabled:text-slate-400 font-black text-xs px-5 py-3 rounded-2xl flex items-center gap-2 transition-all shadow-lg shadow-orange-550/15 hover:shadow-orange-550/25 active:scale-[0.98] disabled:scale-100">
                    <svg x-show="syncing" class="animate-spin h-3.5 w-3.5 text-slate-950" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="syncing ? 'مزامنة...' : 'مزامنة'"></span>
                    <span x-show="pendingSyncCount > 0" class="absolute -top-2 -right-2 bg-rose-550 text-white text-[9px] w-6 h-6 rounded-full flex items-center justify-center font-black border-2 border-white animate-bounce" x-text="pendingSyncCount"></span>
                </button>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="flex-grow flex overflow-hidden">

            <!-- Right Column: Checkout Cart -->
            <section class="w-[400px] bg-slate-950/95 backdrop-blur-xl border-l border-slate-900 flex flex-col flex-shrink-0 text-right shadow-2xl relative z-10 h-full overflow-hidden text-slate-100">
                <!-- Active Customer / Cart Metadata -->
                <div class="p-5 border-b border-slate-900 flex justify-between items-center bg-slate-900/30">
                    <h2 class="font-black text-xs text-slate-100 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🛒</span> السلة الحالية
                    </h2>
                    <button @click="clearCart()" class="text-[10px] font-black text-rose-450 hover:text-rose-450 transition-colors bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 px-3 py-1.5 rounded-xl">مسح السلة</button>
                </div>

                <!-- Cart Items List (Scrollable) -->
                <div class="flex-grow overflow-y-auto p-4 space-y-3 min-h-0">
                    <template x-if="cart.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-slate-500 gap-3 py-20">
                            <span class="text-5xl animate-bounce">🍕</span>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">سلة المشتريات فارغة</span>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="item.product.id">
                        <div class="bg-slate-900/40 border border-slate-900 hover:border-amber-500/20 hover:bg-slate-900/70 rounded-[22px] p-4 flex items-center justify-between gap-3 shadow-sm transition-all duration-300">
                            <div class="min-w-0 flex-grow text-right">
                                <h3 class="font-extrabold text-xs text-slate-200 truncate" x-text="item.product.name"></h3>
                                <span class="text-[11px] text-amber-400 font-extrabold block mt-0.5" x-text="formatCurrency(item.product.base_price)"></span>
                            </div>
                            <div class="flex items-center gap-2 bg-slate-950 p-1.5 rounded-2xl border border-slate-850 shadow-inner" dir="ltr">
                                <button @click="decrementQty(index)" class="w-7 h-7 bg-slate-900 hover:bg-slate-800 active:scale-90 text-slate-400 font-black rounded-xl flex items-center justify-center text-sm border border-slate-800 transition-all">-</button>
                                <span class="w-6 text-center font-extrabold text-xs text-slate-200" x-text="item.quantity"></span>
                                <button @click="incrementQty(index)" class="w-7 h-7 bg-slate-900 hover:bg-slate-800 active:scale-90 text-slate-400 font-black rounded-xl flex items-center justify-center text-sm border border-slate-800 transition-all">+</button>
                            </div>
                            <div class="text-left w-20 flex-shrink-0" dir="ltr">
                                <span class="font-black text-xs text-slate-200" x-text="formatCurrency(item.product.base_price * item.quantity)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Tax, Discount, Subtotal & Total Controls -->
                <div class="p-4 bg-slate-900/85 border-t border-slate-850/80 space-y-3 rounded-t-[28px] text-white shadow-[0_-12px_40px_rgba(15,23,42,0.3)] relative z-20 flex-shrink-0">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="font-bold">المجموع الفرعي</span>
                        <span class="font-black text-slate-300" x-text="formatCurrency(getSubtotal())"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <!-- Discount Input -->
                        <div class="flex flex-col gap-1.5 text-right">
                            <span class="text-[9px] text-slate-400 font-extrabold">الخصم الممنوح (د.ل)</span>
                            <input type="number" min="0" x-model.number="discount" class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500/80 focus:ring-4 focus:ring-amber-550/10 rounded-2xl px-3 py-2 text-center text-amber-400 font-black focus:outline-none text-xs transition-all shadow-inner" />
                        </div>

                        <!-- Tax Input -->
                        <div class="flex flex-col gap-1.5 text-right">
                            <span class="text-[9px] text-slate-400 font-extrabold">الضريبة المضافة (د.ل)</span>
                            <input type="number" min="0" x-model.number="tax" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500/80 focus:ring-4 focus:ring-rose-550/10 rounded-2xl px-3 py-2 text-center text-rose-450 font-black focus:outline-none text-xs transition-all shadow-inner" />
                        </div>
                    </div>

                    <!-- Order Type Selector -->
                    <div class="flex flex-col gap-1.5 text-right">
                        <label class="text-[9px] font-black text-slate-450 uppercase tracking-wider block">نوع الطلب (المطبخ)</label>
                        <div class="bg-slate-950 border border-slate-850 p-1.5 rounded-2xl flex gap-1.5" dir="rtl">
                            <button @click="orderType = 'dinein'" type="button"
                                    :class="orderType === 'dinein' ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black shadow-lg shadow-orange-500/15' : 'text-slate-400 hover:text-white'"
                                    class="w-1/3 py-2 rounded-xl text-[10px] font-black transition-all duration-300 flex items-center justify-center gap-1">
                                <span>🛋️</span> محلي
                            </button>
                            <button @click="orderType = 'takeaway'" type="button"
                                    :class="orderType === 'takeaway' ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black shadow-lg shadow-orange-500/15' : 'text-slate-400 hover:text-white'"
                                    class="w-1/3 py-2 rounded-xl text-[10px] font-black transition-all duration-300 flex items-center justify-center gap-1">
                                <span>🛍️</span> سفري
                            </button>
                            <button @click="orderType = 'delivery'" type="button"
                                    :class="orderType === 'delivery' ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black shadow-lg shadow-orange-500/15' : 'text-slate-400 hover:text-white'"
                                    class="w-1/3 py-2 rounded-xl text-[10px] font-black transition-all duration-300 flex items-center justify-center gap-1">
                                <span>🚗</span> توصيل
                            </button>
                        </div>
                    </div>

                    <!-- Order Notes Input -->
                    <div class="flex flex-col gap-1.5 text-right">
                        <label class="text-[9px] font-black text-slate-450 uppercase tracking-wider block">ملاحظات التحضير الخاصة</label>
                        <input type="text" x-model="notes" placeholder="مثال: بدون بصل، زيادة جبنة، إلخ..." 
                               class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-2.5 text-xs text-slate-200 focus:outline-none text-right placeholder-slate-700 transition-all" />
                    </div>

                    <!-- Grand Total -->
                    <div class="flex items-center justify-between border-t border-slate-800/80 pt-4 text-sm">
                        <span class="font-extrabold text-slate-450 uppercase tracking-wider text-[10px]">إجمالي الفاتورة النهائي</span>
                        <span class="text-2xl font-black text-amber-400" x-text="formatCurrency(getTotal())"></span>
                    </div>

                    <!-- Checkout Button -->
                    <button @click="openPaymentModal()" :disabled="cart.length === 0 || !selectedLocation"
                            class="w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 disabled:bg-none disabled:bg-slate-800/50 text-slate-950 disabled:text-slate-500 font-black py-4 rounded-2xl shadow-xl shadow-orange-550/10 hover:shadow-orange-550/20 disabled:shadow-none hover:scale-[1.01] active:scale-[0.99] disabled:scale-100 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 text-xs tracking-wider uppercase">
                        <span x-text="!selectedLocation ? 'الرجاء تحديد الفرع أولاً' : 'إتمام الدفع وطباعة الفاتورة'"></span>
                    </button>
                </div>
            </section>

            <!-- Left Column: Menu Products Grid -->
            <section class="flex-grow flex flex-col bg-slate-50/50 text-right relative">
                <!-- Category Horizontal Scroll Bar -->
                <div class="p-4 bg-white/70 backdrop-blur-md border-b border-slate-200/60 flex items-center gap-3 overflow-x-auto flex-shrink-0" dir="rtl">
                    <button @click="selectedCategory = 'All'"
                            class="px-5 py-3 rounded-2xl text-xs font-black transition-all flex-shrink-0 uppercase tracking-wider flex items-center gap-2"
                            :class="selectedCategory === 'All' ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'bg-slate-100 hover:bg-slate-200/80 text-slate-700 border border-slate-200/50 hover:scale-[1.02]'">
                        <span>🍽️</span>
                        <span>جميع الوجبات</span>
                    </button>
                    <template x-for="cat in categories" :key="cat">
                        <button @click="selectedCategory = cat"
                                class="px-5 py-3 rounded-2xl text-xs font-black transition-all flex-shrink-0 uppercase tracking-wider flex items-center gap-2"
                                :class="selectedCategory === cat ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'bg-slate-100 hover:bg-slate-200/80 text-slate-700 border border-slate-200/50 hover:scale-[1.02]'">
                            <span x-text="getCategoryIcon(cat)"></span>
                            <span x-text="cat"></span>
                        </button>
                    </template>
                </div>

                <!-- Product Card Grid (Scrollable) -->
                <div class="flex-grow overflow-y-auto p-6 relative z-10" dir="rtl">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                        <template x-for="product in filteredProducts()" :key="product.id">
                            <div @click="addToCart(product)"
                                 class="bg-white/90 backdrop-blur-sm border border-slate-200 hover:border-amber-500/40 rounded-[28px] p-3 flex flex-col justify-between cursor-pointer transition-all duration-350 hover:-translate-y-1 shadow-[0_4px_20px_rgba(241,245,249,0.5)] hover:shadow-[0_12px_32px_rgba(245,158,11,0.12)] group relative overflow-hidden h-56 text-right card-animate">
                                
                                <!-- Product Thumbnail Image -->
                                <div class="w-full h-32 relative overflow-hidden rounded-2xl bg-slate-100">
                                    <img :src="product.image_url || 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=500&auto=format&fit=crop'" 
                                         x-on:error="$event.target.src = 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=500&auto=format&fit=crop'"
                                         alt="Food image"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 to-transparent"></div>
                                    <!-- Floating category -->
                                    <span class="absolute top-2.5 right-2.5 text-[8px] uppercase font-black text-amber-500 tracking-widest bg-slate-950/80 backdrop-blur-sm px-2 py-0.5 rounded-lg border border-slate-800 shadow" x-text="product.category"></span>
                                </div>
                                
                                <!-- Product Meta Details -->
                                <div class="mt-3 flex flex-col justify-between flex-grow">
                                    <h3 class="font-extrabold text-slate-800 text-xs leading-snug group-hover:text-amber-600 transition-colors truncate text-right" x-text="product.name"></h3>
                                    <div class="flex items-center justify-between mt-1">
                                        <p class="text-amber-600 font-black text-xs" x-text="formatCurrency(product.base_price)"></p>
                                        <span class="text-[9px] font-black bg-amber-500/10 text-amber-600 group-hover:bg-amber-500 group-hover:text-slate-950 px-2.5 py-1 rounded-xl transition-all duration-300">إضافة +</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </section>
        </main>

        <!-- Payment Gateway & Receipt Preview Modal -->
        <div x-show="showPaymentModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm" @click="checkoutState !== 'receipt' && closePaymentModal()"></div>

            <!-- Modal Container -->
            <div class="flex items-center justify-center min-h-screen p-6 relative">
                
                <!-- Standard Payment Select Screen -->
                <div class="bg-white border border-slate-200 rounded-[32px] max-w-md w-full p-6 shadow-2xl relative z-10 space-y-6 text-right" x-show="checkoutState !== 'receipt'">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                        <h3 class="text-sm font-black text-slate-850 uppercase tracking-wider">اختر طريقة دفع الفاتورة</h3>
                        <button @click="closePaymentModal()" class="text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl flex justify-between items-center border border-slate-200/80 shadow-inner">
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">القيمة الإجمالية المطلوبة</span>
                        <span class="text-xl font-black text-amber-600" x-text="formatCurrency(getTotal())"></span>
                    </div>

                    <!-- Selection Grid -->
                    <div class="grid grid-cols-2 gap-4" x-show="!checkoutState">
                        <!-- Cash -->
                        <button @click="processCashPayment()" class="flex flex-col items-center justify-center gap-3.5 p-5 bg-slate-50/60 hover:bg-white border border-slate-200/85 hover:border-amber-500 rounded-3xl transition-all hover:shadow-[0_8px_24px_rgba(245,158,11,0.08)] group hover:-translate-y-0.5">
                            <span class="text-4xl group-hover:scale-110 transition-transform">💵</span>
                            <span class="text-[10px] font-black text-slate-700 uppercase tracking-wide">نقداً (كاش)</span>
                        </button>
                        <!-- Sadad -->
                        <button @click="processSadadPayment()" class="flex flex-col items-center justify-center gap-3.5 p-5 bg-slate-50/60 hover:bg-white border border-slate-200/85 hover:border-amber-500 rounded-3xl transition-all hover:shadow-[0_8px_24px_rgba(245,158,11,0.08)] group hover:-translate-y-0.5">
                            <span class="text-4xl group-hover:scale-110 transition-transform">📱</span>
                            <span class="text-[10px] font-black text-slate-700 uppercase tracking-wide">بوابة سداد الإلكترونية</span>
                        </button>
                        <!-- MobiCash -->
                        <button @click="processMobiCashPayment()" class="flex flex-col items-center justify-center gap-3.5 p-5 bg-slate-50/60 hover:bg-white border border-slate-200/85 hover:border-amber-500 rounded-3xl transition-all hover:shadow-[0_8px_24px_rgba(245,158,11,0.08)] group hover:-translate-y-0.5">
                            <span class="text-4xl group-hover:scale-110 transition-transform">📲</span>
                            <span class="text-[10px] font-black text-slate-700 uppercase tracking-wide">موبي كاش (MobiCash)</span>
                        </button>
                        <!-- Tadawul POS -->
                        <button @click="processTadawulPayment()" class="flex flex-col items-center justify-center gap-3.5 p-5 bg-slate-50/60 hover:bg-white border border-slate-200/85 hover:border-amber-500 rounded-3xl transition-all hover:shadow-[0_8px_24px_rgba(245,158,11,0.08)] group hover:-translate-y-0.5">
                            <span class="text-4xl group-hover:scale-110 transition-transform">💳</span>
                            <span class="text-[10px] font-black text-slate-700 uppercase tracking-wide">جهاز تداول (Tadawul)</span>
                        </button>
                    </div>

                    <!-- Process States -->
                    <div class="space-y-4 text-center py-4" x-show="checkoutState">
                        <div class="flex flex-col items-center justify-center gap-4">
                            <div x-show="checkoutState === 'mobicash'" class="bg-white p-4 rounded-3xl shadow-xl border border-amber-500/20">
                                <!-- Simulating QR code -->
                                <div class="w-44 h-44 bg-slate-50 flex items-center justify-center text-[10px] font-bold text-slate-800 p-3 break-all border border-slate-200 shadow-inner rounded-2xl" x-text="mobicashPayload"></div>
                            </div>

                            <div x-show="checkoutState === 'tadawul'" class="space-y-2">
                                <span class="text-5xl animate-bounce inline-block">💳</span>
                                <p class="text-xs font-bold text-slate-650" x-text="tadawulMsg"></p>
                            </div>

                            <div x-show="checkoutState === 'sadad'" class="space-y-3">
                                <span class="text-5xl animate-pulse inline-block">🔗</span>
                                <p class="text-xs font-bold text-slate-650">جاري إنشاء رابط الدفع الآمن لسداد...</p>
                                <a :href="sadadUrl" target="_blank" class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-655 text-slate-950 text-xs font-black px-6 py-3 rounded-2xl mt-2 shadow-lg shadow-orange-550/15 active:scale-95 transition-all">فتح بوابة الدفع الإلكتروني</a>
                            </div>

                            <div class="flex items-center gap-2 text-[10px] text-amber-600 font-extrabold tracking-wider uppercase animate-pulse bg-amber-50 border border-amber-200/50 px-4 py-2 rounded-xl">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                <span x-text="checkoutStatusText"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3" x-show="checkoutState">
                        <button @click="cancelCheckout()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black py-3.5 rounded-2xl transition-all">إلغاء</button>
                        <button @click="forceMockApprove()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-xs font-black py-3.5 rounded-2xl text-white transition-all shadow-lg shadow-emerald-500/10">محاكاة نجاح الدفع</button>
                    </div>
                </div>

                <!-- TAB 2: Customer Paper Invoice Receipt Ticket -->
                <div class="max-w-sm w-full p-4 relative z-10 space-y-6" x-show="checkoutState === 'receipt'" style="display: none;">
                    <!-- Real-looking invoice receipt -->
                    <div id="printable-receipt-card" class="paper-receipt p-6 space-y-4 text-right" dir="rtl">
                        <div class="text-center space-y-1">
                            <h2 class="text-base font-black tracking-tight text-slate-900">مطعم المدينة المنورة</h2>
                            <p class="text-[10px] text-slate-500 font-bold" x-text="receiptData.locationName"></p>
                            <p class="text-[9px] text-slate-400">الهاتف: 091-0000000</p>
                        </div>

                        <!-- Cut line design -->
                        <div class="border-t border-dashed border-slate-300 my-2"></div>

                        <!-- Order Metadata -->
                        <div class="text-[10px] space-y-1 text-slate-700">
                            <div class="flex justify-between">
                                <span>رقم الفاتورة:</span>
                                <span class="font-bold text-slate-900" x-text="receiptData.orderId"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>تاريخ العملية:</span>
                                <span x-text="receiptData.date"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>طريقة الدفع:</span>
                                <span class="font-bold text-slate-900" x-text="translatePaymentMethod(receiptData.paymentMethod)"></span>
                            </div>
                        </div>

                        <!-- Notes display if exists -->
                        <template x-if="receiptData.notes">
                            <div class="bg-amber-50 text-amber-955 border border-amber-250/60 p-2.5 rounded-2xl text-[9px] font-sans font-bold flex flex-col gap-0.5 mt-2 text-right">
                                <span class="text-[8px] text-amber-700 uppercase">ملاحظات التحضير:</span>
                                <span x-text="receiptData.notes"></span>
                            </div>
                        </template>

                        <div class="border-t border-dashed border-slate-350 my-2"></div>

                        <!-- Receipt items -->
                        <div class="text-[10px] space-y-1">
                            <div class="flex justify-between font-black text-slate-650 mb-1 border-b border-slate-100 pb-1">
                                <span class="w-1/2 text-right">الصنف الوجبة</span>
                                <span class="w-1/4 text-center">الكمية</span>
                                <span class="w-1/4 text-left">السعر</span>
                            </div>
                            <template x-for="item in receiptData.items" :key="item.product.id">
                                <div class="flex justify-between py-0.5 text-slate-800">
                                    <span class="w-1/2 text-right truncate font-medium" x-text="item.product.name"></span>
                                    <span class="w-1/4 text-center font-bold" x-text="item.quantity"></span>
                                    <span class="w-1/4 text-left font-bold" x-text="parseFloat(item.product.base_price * item.quantity).toFixed(2) + ' د.ل'"></span>
                                </div>
                            </template>
                        </div>

                        <div class="border-t border-dashed border-slate-350 my-2"></div>

                        <!-- Calculations -->
                        <div class="text-[10px] space-y-1 text-slate-700">
                            <div class="flex justify-between">
                                <span>المجموع الفرعي:</span>
                                <span x-text="parseFloat(receiptData.subtotal).toFixed(2) + ' د.ل'"></span>
                            </div>
                            <div class="flex justify-between text-amber-600 font-bold" x-show="receiptData.discount > 0">
                                <span>الخصم:</span>
                                <span x-text="'-' + parseFloat(receiptData.discount).toFixed(2) + ' د.ل'"></span>
                            </div>
                            <div class="flex justify-between text-rose-600 font-bold">
                                <span>الضريبة المضافة:</span>
                                <span x-text="parseFloat(receiptData.tax).toFixed(2) + ' د.ل'"></span>
                            </div>
                            <div class="flex justify-between font-black text-sm pt-2 border-t border-slate-200 text-slate-900">
                                <span>الإجمالي النهائي:</span>
                                <span class="text-amber-650" x-text="parseFloat(receiptData.total).toFixed(2) + ' د.ل'"></span>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-slate-350 my-2"></div>

                        <!-- Receipt Footer: Mock Barcode & Shukran -->
                        <div class="text-center space-y-2">
                            <p class="text-[10px] font-black">شكراً لزيارتكم • صحتين وعافية!</p>
                            <!-- Simulated Barcode in HTML -->
                            <div class="flex justify-center items-center gap-0.5 h-8 w-44 mx-auto bg-slate-50 p-1 rounded-xl border border-slate-100 shadow-inner">
                                <div class="w-1 bg-slate-850 h-full"></div>
                                <div class="w-0.5 bg-slate-850 h-full"></div>
                                <div class="w-1.5 bg-slate-850 h-full"></div>
                                <div class="w-0.5 bg-slate-850 h-full"></div>
                                <div class="w-1 bg-slate-850 h-full"></div>
                                <div class="w-2 bg-slate-850 h-full"></div>
                                <div class="w-0.5 bg-slate-850 h-full"></div>
                                <div class="w-1 bg-slate-850 h-full"></div>
                                <div class="w-1.5 bg-slate-850 h-full"></div>
                                <div class="w-0.5 bg-slate-850 h-full"></div>
                            </div>
                            <p class="text-[8px] text-slate-400 font-mono" x-text="receiptData.orderId"></p>
                        </div>
                    </div>

                    <!-- Print & Continue Buttons -->
                    <div class="flex flex-col gap-2 relative z-25">
                        <div class="flex gap-2">
                            <button @click="triggerLocalPrinter(receiptData.orderIdRaw)" class="w-1/2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black py-3.5 rounded-2xl text-[10px] tracking-wider shadow-lg shadow-orange-550/15 transition-all">
                                🖨️ طابعة الشبكة IP
                            </button>
                            <button @click="printViaBrowser()" class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-black py-3.5 rounded-2xl text-[10px] tracking-wider shadow-lg shadow-blue-500/10 transition-all">
                                📄 طباعة المتصفح
                            </button>
                        </div>
                        <button @click="finishReceiptAndReset()" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-3.5 rounded-2xl text-xs tracking-wider border border-slate-800 shadow-lg transition-all">
                            فتح فاتورة جديدة (New Order)
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Script Block for POS Logic -->
        <script>
            // IndexedDB Simple Offline Store Wrapper
            const dbName = "posOfflineDB";
            let db;

            const request = indexedDB.open(dbName, 1);
            request.onupgradeneeded = function(e) {
                const localDb = e.target.result;
                if (!localDb.objectStoreNames.contains("orders")) {
                    localDb.createObjectStore("orders", { keyPath: "id" });
                }
                if (!localDb.objectStoreNames.contains("order_items")) {
                    localDb.createObjectStore("order_items", { keyPath: "id" });
                }
                if (!localDb.objectStoreNames.contains("payments")) {
                    localDb.createObjectStore("payments", { keyPath: "id" });
                }
                if (!localDb.objectStoreNames.contains("inventory_transactions")) {
                    localDb.createObjectStore("inventory_transactions", { keyPath: "id" });
                }
            };
            request.onsuccess = function(e) {
                db = e.target.result;
                window.dispatchEvent(new CustomEvent('db-ready'));
            };

            function posApp() {
                return {
                    products: @json($products),
                    categories: @json($categories),
                    locations: @json($locations),
                    
                    selectedLocation: '',
                    selectedCategory: 'All',
                    orderType: 'takeaway',
                    printerIp: localStorage.getItem('printerIp') || '',
                    
                    cart: [],
                    discount: 0,
                    tax: 0,
                    notes: '',
                    
                    // Connection and syncing
                    isOnline: navigator.onLine,
                    syncing: false,
                    pendingSyncCount: 0,
                    
                    // Modals & checkout state
                    showPaymentModal: false,
                    checkoutState: null, // 'sadad', 'mobicash', 'tadawul', 'receipt'
                    checkoutStatusText: '',
                    currentOrderUuid: null,
                    
                    // Gateway outputs
                    mobicashPayload: '',
                    sadadUrl: '',
                    tadawulMsg: '',
                    pollingInterval: null,

                    // Print preview receipt data (Phase 6 upgrade)
                    receiptData: {
                        orderId: '',
                        orderIdRaw: '',
                        date: '',
                        locationName: '',
                        items: [],
                        subtotal: 0,
                        discount: 0,
                        tax: 0,
                        total: 0,
                        notes: '',
                        paymentMethod: ''
                    },

                    init() {
                        this.selectedLocation = localStorage.getItem('selectedLocation') || '';
                        window.addEventListener('online', () => { this.isOnline = true; this.triggerAutoSync(); });
                        window.addEventListener('offline', () => { this.isOnline = false; });
                        window.addEventListener('db-ready', () => { this.updatePendingSyncCount(); });
                        
                        this.$watch('printerIp', val => localStorage.setItem('printerIp', val));
                        setInterval(() => this.triggerAutoSync(), 30000);
                    },

                    changeLocation() {
                        localStorage.setItem('selectedLocation', this.selectedLocation);
                    },

                    formatCurrency(value) {
                        return parseFloat(value).toFixed(2) + ' د.ل';
                    },

                    translatePaymentMethod(method) {
                        const dict = {
                            'cash': 'نقداً (كاش)',
                            'sadad': 'سداد (Sadad)',
                            'mobicash': 'موبي كاش',
                            'tadawul': 'تداول (Tadawul)'
                        };
                        return dict[method.toLowerCase()] || method;
                    },

                    getCategoryIcon(cat) {
                        const icons = {
                            'Pizza': '🍕',
                            'Burgers': '🍔',
                            'Coffee': '☕',
                            'Cold Drinks': '🥤',
                            'Cold drinks': '🥤',
                            'Shawarma': '🌯',
                            'Dessert': '🍰',
                            'Appetizers': '🍟'
                        };
                        return icons[cat] || '🍽️';
                    },

                    filteredProducts() {
                        if (this.selectedCategory === 'All') {
                            return this.products;
                        }
                        return this.products.filter(p => p.category === this.selectedCategory);
                    },

                    addToCart(product) {
                        const existingIndex = this.cart.findIndex(item => item.product.id === product.id);
                        if (existingIndex > -1) {
                            this.cart[existingIndex].quantity++;
                        } else {
                            this.cart.push({ product: product, quantity: 1 });
                        }
                    },

                    incrementQty(index) {
                        this.cart[index].quantity++;
                    },

                    decrementQty(index) {
                        if (this.cart[index].quantity > 1) {
                            this.cart[index].quantity--;
                        } else {
                            this.cart.splice(index, 1);
                        }
                    },

                    clearCart() {
                        this.cart = [];
                        this.discount = 0;
                        this.tax = 0;
                        this.notes = '';
                    },

                    getSubtotal() {
                        return this.cart.reduce((sum, item) => sum + (item.product.base_price * item.quantity), 0);
                    },

                    getTotal() {
                        const subtotal = this.getSubtotal();
                        return Math.max(0, subtotal - this.discount + this.tax);
                    },

                    openPaymentModal() {
                        this.showPaymentModal = true;
                        this.checkoutState = null;
                    },

                    closePaymentModal() {
                        this.showPaymentModal = false;
                        this.checkoutState = null;
                        if (this.pollingInterval) {
                            clearInterval(this.pollingInterval);
                        }
                    },

                    generateUUID() {
                        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                            return v.toString(16);
                        });
                    },

                    saveOrderOffline(order, items, payment, inventoryTx) {
                        if (!db) return;
                        const tx = db.transaction(["orders", "order_items", "payments", "inventory_transactions"], "readwrite");
                        
                        tx.objectStore("orders").put(order);
                        items.forEach(it => tx.objectStore("order_items").put(it));
                        tx.objectStore("payments").put(payment);
                        tx.objectStore("inventory_transactions").put(inventoryTx);

                        tx.oncomplete = () => {
                            this.updatePendingSyncCount();
                            
                            // Populate Receipt Preview data
                            const activeLoc = this.locations.find(l => l.id === this.selectedLocation);
                            this.receiptData = {
                                orderId: order.id.substring(0, 8).toUpperCase(),
                                orderIdRaw: order.id,
                                date: new Date(order.created_at).toLocaleString('ar-LY'),
                                locationName: activeLoc ? activeLoc.name : 'فرع طرابلس',
                                items: JSON.parse(JSON.stringify(this.cart)),
                                subtotal: this.getSubtotal(),
                                discount: this.discount,
                                tax: this.tax,
                                total: this.getTotal(),
                                notes: order.notes || '',
                                paymentMethod: payment.payment_method
                            };
                            
                            // Transition to show print receipt card
                            this.checkoutState = 'receipt';
                            
                            // Attempt sync
                            this.triggerAutoSync();
                        };
                    },

                    finishReceiptAndReset() {
                        this.clearCart();
                        this.closePaymentModal();
                    },

                    // 1. Process CASH Checkouts
                    processCashPayment() {
                        const orderId = this.generateUUID();
                        const paymentId = this.generateUUID();
                        const txId = this.generateUUID();
                        
                        const order = {
                            id: orderId,
                            location_id: this.selectedLocation,
                            status: 'pending',
                            payment_status: 'paid',
                            total_amount: this.getTotal(),
                            discount: this.discount,
                            tax: this.tax,
                            notes: (this.orderType === 'dinein' ? '[محلي]' : (this.orderType === 'delivery' ? '[توصيل]' : '[سفري]')) + (this.notes ? ' ' + this.notes : ''),
                            sync_status: 'pending',
                            created_at: new Date().toISOString()
                        };

                        const items = this.cart.map(c => ({
                            id: this.generateUUID(),
                            order_id: orderId,
                            product_id: c.product.id,
                            quantity: c.quantity,
                            price: c.product.base_price
                        }));

                        const payment = {
                            id: paymentId,
                            order_id: orderId,
                            amount: this.getTotal(),
                            payment_method: 'cash',
                            transaction_id: 'CASH_' + Math.floor(Math.random() * 100000),
                            status: 'completed',
                            created_at: new Date().toISOString()
                        };

                        const inventoryTx = {
                            id: txId,
                            product_id: items[0].product_id,
                            location_id: this.selectedLocation,
                            quantity: -items.reduce((sum, i) => sum + i.quantity, 0),
                            unit_cost: items[0].price * 0.4,
                            source_id: null,
                            created_at: new Date().toISOString()
                        };

                        this.saveOrderOffline(order, items, payment, inventoryTx);
                    },

                    // 2. Process SADAD Integration
                    processSadadPayment() {
                        const orderId = this.generateUUID();
                        this.currentOrderUuid = orderId;
                        this.checkoutState = 'sadad';
                        this.checkoutStatusText = "جاري الاتصال بخدمة سداد (SADAD API)...";

                        if (this.isOnline) {
                            fetch('/api/payments/sadad/checkout', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ order_id: orderId, amount: this.getTotal() })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    this.sadadUrl = data.checkout_url;
                                    this.checkoutStatusText = "في انتظار تأكيد عملية الدفع من سداد...";
                                    this.startPaymentPolling('sadad', orderId);
                                }
                            })
                            .catch(() => {
                                this.checkoutStatusText = "النظام غير متصل أو انتهت المهلة. تم الحفظ للمعالجة لاحقاً.";
                            });
                        } else {
                            this.checkoutStatusText = "النظام غير متصل. تم حفظ الفاتورة محلياً.";
                        }
                    },

                    // 3. Process MOBICASH Integration
                    processMobiCashPayment() {
                        const orderId = this.generateUUID();
                        this.currentOrderUuid = orderId;
                        this.checkoutState = 'mobicash';
                        this.checkoutStatusText = "جاري إنشاء رمز الاستجابة السريع لموبي كاش (QR)...";

                        if (this.isOnline) {
                            fetch('/api/payments/mobicash/qr', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ order_id: orderId, amount: this.getTotal() })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    this.mobicashPayload = data.qr_payload;
                                    this.checkoutStatusText = "امسح الرمز من تطبيق الهاتف لتأكيد الدفع...";
                                    this.startPaymentPolling('mobicash', data.payment_id);
                                }
                            });
                        } else {
                            this.mobicashPayload = `MOBICASH_MADINA_77|${orderId}|${this.getTotal()}|${Date.now()}`;
                            this.checkoutStatusText = "رمز QR محلي (سيتم التأكيد عند مزامنة الشبكة)...";
                        }
                    },

                    // 4. Process TADAWUL POS Card Reader Integration
                    processTadawulPayment() {
                        const orderId = this.generateUUID();
                        this.currentOrderUuid = orderId;
                        this.checkoutState = 'tadawul';
                        this.tadawulMsg = "جاري تهيئة جهاز نقاط البيع تداول...";
                        this.checkoutStatusText = "جاري إرسال القيمة لجهاز بطاقات الدفع...";

                        if (this.isOnline) {
                            fetch('/api/payments/tadawul/transact', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ order_id: orderId, amount: this.getTotal() })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    this.tadawulMsg = data.message;
                                    this.checkoutStatusText = "مرر البطاقة على جهاز تداول لإتمام الدفع...";
                                    this.startPaymentPolling('tadawul', data.payment_id);
                                }
                            });
                        } else {
                            this.tadawulMsg = "الدفع بالبطاقة دون اتصال. يرجى تمرير البطاقة بالجهاز.";
                            this.checkoutStatusText = "دفع بطاقة دون اتصال (تم الحفظ للمزامنة)...";
                        }
                    },

                    startPaymentPolling(method, identifier) {
                        if (this.pollingInterval) clearInterval(this.pollingInterval);
                        this.pollingInterval = setInterval(() => {
                            fetch(`/api/payments/${method}/status/${identifier}`)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success && data.status === 'completed') {
                                        clearInterval(this.pollingInterval);
                                        this.finalizeOrderGatewaySuccess(method);
                                    }
                                });
                        }, 3000);
                    },

                    forceMockApprove() {
                        if (this.pollingInterval) clearInterval(this.pollingInterval);
                        this.finalizeOrderGatewaySuccess(this.checkoutState);
                    },

                    finalizeOrderGatewaySuccess(method) {
                        const orderId = this.currentOrderUuid;
                        const order = {
                            id: orderId,
                            location_id: this.selectedLocation,
                            status: 'cooking',
                            payment_status: 'paid',
                            total_amount: this.getTotal(),
                            discount: this.discount,
                            tax: this.tax,
                            notes: (this.orderType === 'dinein' ? '[محلي]' : (this.orderType === 'delivery' ? '[توصيل]' : '[سفري]')) + (this.notes ? ' ' + this.notes : ''),
                            sync_status: 'pending',
                            created_at: new Date().toISOString()
                        };

                        const items = this.cart.map(c => ({
                            id: this.generateUUID(),
                            order_id: orderId,
                            product_id: c.product.id,
                            quantity: c.quantity,
                            price: c.product.base_price
                        }));

                        const payment = {
                            id: this.generateUUID(),
                            order_id: orderId,
                            amount: this.getTotal(),
                            payment_method: method,
                            transaction_id: 'GATEWAY_' + Math.floor(Math.random() * 100000),
                            status: 'completed',
                            created_at: new Date().toISOString()
                        };

                        const inventoryTx = {
                            id: this.generateUUID(),
                            product_id: items[0].product_id,
                            location_id: this.selectedLocation,
                            quantity: -items.reduce((sum, i) => sum + i.quantity, 0),
                            unit_cost: items[0].price * 0.4,
                            source_id: null,
                            created_at: new Date().toISOString()
                        };

                        this.saveOrderOffline(order, items, payment, inventoryTx);
                    },

                    cancelCheckout() {
                        if (this.pollingInterval) clearInterval(this.pollingInterval);
                        this.checkoutState = null;
                    },

                    printViaBrowser() {
                        window.print();
                    },

                    triggerLocalPrinter(orderId) {
                        if (!this.printerIp) {
                            alert("يرجى تهيئة وإدخال عنوان IP الخاص بالطابعة الشبكية في الشريط العلوي أولاً!");
                            return;
                        }
                        
                        fetch(`/pos/orders/${orderId}/print`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ ip: this.printerIp })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert("تم إرسال أمر الطباعة بنجاح للطابعة الشبكية!");
                            } else {
                                alert("فشل الاتصال بالطابعة الشبكية. يرجى التحقق من اتصالها بالشبكة.");
                            }
                        });
                    },

                    updatePendingSyncCount() {
                        if (!db) return;
                        const tx = db.transaction(["orders"], "readonly");
                        const store = tx.objectStore("orders");
                        const req = store.getAll();
                        req.onsuccess = () => {
                            const unsynced = req.result.filter(o => o.sync_status === 'pending');
                            this.pendingSyncCount = unsynced.length;
                        };
                    },

                    triggerManualSync() {
                        this.syncing = true;
                        this.triggerAutoSync().finally(() => {
                            setTimeout(() => this.syncing = false, 1000);
                        });
                    },

                    async triggerAutoSync() {
                        if (!this.isOnline || !db || this.syncing) return;
                        const lastSync = localStorage.getItem('last_sync_time') || new Date(0).toISOString();
                        
                        try {
                            const pullRes = await fetch(`/api/sync/pull?last_sync=${encodeURIComponent(lastSync)}`);
                            const pullData = await pullRes.json();
                            
                            if (pullData.force_reset) {
                                const wipeTx = db.transaction(["orders", "order_items", "payments", "inventory_transactions"], "readwrite");
                                wipeTx.objectStore("orders").clear();
                                wipeTx.objectStore("order_items").clear();
                                wipeTx.objectStore("payments").clear();
                                wipeTx.objectStore("inventory_transactions").clear();
                            }

                            const pushTx = db.transaction(["orders", "order_items", "payments", "inventory_transactions"], "readonly");
                            
                            const localOrders = await new Promise(r => pushTx.objectStore("orders").getAll().onsuccess = e => r(e.target.result));
                            const localItems = await new Promise(r => pushTx.objectStore("order_items").getAll().onsuccess = e => r(e.target.result));
                            const localPayments = await new Promise(r => pushTx.objectStore("payments").getAll().onsuccess = e => r(e.target.result));
                            const localTx = await new Promise(r => pushTx.objectStore("inventory_transactions").getAll().onsuccess = e => r(e.target.result));

                            const unsyncedOrders = localOrders.filter(o => o.sync_status === 'pending');
                            
                            if (unsyncedOrders.length > 0) {
                                const unsyncedOrderIds = unsyncedOrders.map(o => o.id);
                                
                                const payload = {
                                    orders: unsyncedOrders,
                                    order_items: localItems.filter(i => unsyncedOrderIds.includes(i.order_id)),
                                    payments: localPayments.filter(p => unsyncedOrderIds.includes(p.order_id)),
                                    inventory_transactions: localTx
                                };

                                const pushRes = await fetch('/api/sync/push', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify(payload)
                                });
                                
                                const pushData = await pushRes.json();
                                
                                if (pushData.success) {
                                    const markTx = db.transaction(["orders"], "readwrite");
                                    const store = markTx.objectStore("orders");
                                    
                                    for (let id of pushData.synced_orders) {
                                        const getReq = store.get(id);
                                        getReq.onsuccess = (e) => {
                                            const record = e.target.result;
                                            if (record) {
                                                record.sync_status = 'synced';
                                                store.put(record);
                                            }
                                        };
                                    }
                                    
                                    markTx.oncomplete = () => {
                                        this.updatePendingSyncCount();
                                    };
                                }
                            }

                            localStorage.setItem('last_sync_time', pullData.server_time);

                        } catch (err) {
                            console.error("Auto Sync failed: ", err);
                        }
                    }
                };
            }
        </script>
    </div>
</body>
</html>
