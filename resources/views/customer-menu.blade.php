<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bello Smash - قائمة الطعام المميزة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                        playfair: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#166534',
                            600: '#15803d',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;550;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            -webkit-tap-highlight-color: transparent;
            outline: none;
        }
        body {
            font-family: 'Cairo', sans-serif;
        }
        /* Custom Scrollbar for dynamic category chips */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        /* Premium Card Hover Effects */
        .premium-card {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .premium-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.08);
        }
        .dark .premium-card:hover {
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.4);
        }
        /* Smooth scale bounce for buttons */
        .btn-bounce {
            transition: transform 0.15s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .btn-bounce:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body x-data="menuApp()" x-init="initMenu()" :class="{ 'dark': theme === 'dark' }" class="bg-slate-50 dark:bg-zinc-950 text-slate-800 dark:text-zinc-100 min-h-screen transition-colors duration-300">

    <!-- Glowing Top Accent Line -->
    <div class="h-1.5 w-full bg-gradient-to-r from-emerald-600 via-amber-500 to-emerald-700"></div>

    <!-- Floating Toast Message -->
    <div class="fixed top-6 right-6 z-[9999] pointer-events-none space-y-2 max-w-[320px]">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300 transform translate-x-10 opacity-0"
                 x-transition:enter-end="transform translate-x-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200 transform translate-x-10 opacity-0"
                 class="p-4 rounded-2xl shadow-xl flex items-center gap-3 bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 pointer-events-auto"
                 :class="toast.type === 'success' ? 'border-r-4 border-r-emerald-500' : 'border-r-4 border-r-amber-500'">
                <span class="text-xl" x-text="toast.type === 'success' ? '✅' : '🔔'"></span>
                <p class="text-xs font-bold" x-text="toast.message"></p>
            </div>
        </template>
    </div>

    <!-- Header & Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-xl border-b border-slate-200/60 dark:border-zinc-900/60 transition-colors duration-300">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <!-- Brand Logo / Title -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-800 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-800/10">
                    <span class="text-xl font-black text-white font-playfair">B</span>
                </div>
                <div>
                    <h1 class="text-lg font-black text-slate-900 dark:text-white leading-tight font-playfair tracking-wide">Bello Smash</h1>
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider -mt-0.5">Burger &amp; More</p>
                </div>
            </div>

            <!-- Utility Controls (Theme Toggle & Cart Button) -->
            <div class="flex items-center gap-2">
                <!-- Theme Toggle Button -->
                <button @click="toggleTheme()" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-zinc-900 hover:bg-slate-200 dark:hover:bg-zinc-800 flex items-center justify-center transition-colors btn-bounce" aria-label="تغيير المظهر">
                    <!-- Sun Icon (shows in Dark Mode) -->
                    <svg x-show="theme === 'dark'" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path>
                    </svg>
                    <!-- Moon Icon (shows in Light Mode) -->
                    <svg x-show="theme === 'light'" class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>

                <!-- Floating Cart Trigger (Only displays if cart is not empty) -->
                <button @click="showCartSheet = true" class="relative w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-center transition-colors btn-bounce">
                    <svg class="w-5 h-5 text-emerald-700 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span x-show="cartCount() > 0" class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white text-[9px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-white dark:border-zinc-950 shadow-md animate-pulse" x-text="cartCount()"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero / Welcome Banner -->
    <section class="max-w-4xl mx-auto px-4 pt-6 pb-2">
        <div class="bg-gradient-to-r from-emerald-900 to-emerald-800 dark:from-zinc-900 dark:to-zinc-900/60 rounded-3xl p-6 shadow-xl relative overflow-hidden">
            <!-- Decorative Light Circles -->
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-700/20 dark:bg-zinc-800/30 rounded-full blur-2xl"></div>
            <div class="absolute -left-10 -top-10 w-40 h-40 bg-amber-500/10 rounded-full blur-2xl"></div>
            
            <div class="relative z-10 max-w-lg">
                <span class="px-3 py-1 rounded-full bg-emerald-700/50 dark:bg-emerald-950/50 text-[10px] font-black tracking-wider text-emerald-200 border border-emerald-600/30">أهلاً بك في مطعم بيلو سماش</span>
                <h2 class="text-2xl font-black text-white mt-3 leading-tight">اختر وجبتك المفضلة وعش تجربة طعم لا تُنسى</h2>
                <p class="text-xs text-emerald-100/80 dark:text-zinc-400 mt-2 font-medium leading-relaxed">تصفح قائمتنا اللذيذة واطلب مباشرة من طاولة الطعام ليوصلك طلبك ساخناً وطازجاً.</p>
            </div>
        </div>
    </section>

    <!-- Sticky Sub-Bar (Search & Category Chips) -->
    <section class="max-w-4xl mx-auto px-4 pt-4 sticky top-[67px] z-30 bg-slate-50/90 dark:bg-zinc-950/90 backdrop-blur-md pb-3 transition-colors duration-300">
        <div class="space-y-3">
            <!-- Interactive Search Bar -->
            <div class="relative">
                <input type="text" x-model="searchQuery" placeholder="ابحث عن وجبتك المفضلة..." class="w-full bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800/80 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl px-11 py-3 text-sm text-slate-800 dark:text-zinc-200 focus:outline-none placeholder-slate-400 dark:placeholder-zinc-600 transition-all shadow-sm">
                <!-- Search Icon -->
                <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400 dark:text-zinc-650" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <!-- Clear Button -->
                <button x-show="searchQuery.length > 0" @click="searchQuery = ''" class="absolute inset-y-0 left-4 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-zinc-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Categories Scroller -->
            <div class="flex gap-2 overflow-x-auto pb-1 hide-scrollbar" x-show="categories.length > 0">
                <button @click="selectedCategory = 'all'" 
                        class="px-5 py-2.5 rounded-xl text-xs font-black transition-all flex-shrink-0 border shadow-sm btn-bounce"
                        :class="selectedCategory === 'all' 
                            ? 'bg-emerald-850 border-emerald-850 text-white dark:bg-white dark:border-white dark:text-zinc-950 font-black shadow-lg shadow-emerald-800/10 dark:shadow-none' 
                            : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300 dark:bg-zinc-900 dark:border-zinc-800/60 dark:text-zinc-400 dark:hover:text-zinc-200'">
                    الكل
                </button>
                <template x-for="cat in categories" :key="cat">
                    <button @click="selectedCategory = cat" 
                            class="px-5 py-2.5 rounded-xl text-xs font-black transition-all flex-shrink-0 border shadow-sm btn-bounce"
                            :class="selectedCategory === cat 
                                ? 'bg-emerald-850 border-emerald-850 text-white dark:bg-white dark:border-white dark:text-zinc-950 font-black shadow-lg shadow-emerald-800/10 dark:shadow-none' 
                                : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300 dark:bg-zinc-900 dark:border-zinc-800/60 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        <span x-text="cat"></span>
                    </button>
                </template>
            </div>
        </div>
    </section>

    <!-- Products Cards Grid -->
    <main class="max-w-4xl mx-auto px-4 pb-28 pt-2">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4" x-show="filteredProducts().length > 0">
            <template x-for="(product, idx) in filteredProducts()" :key="product.id">
                <!-- Product Card -->
                <div @click="openDetail(product)" 
                     class="premium-card bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-850/60 rounded-2xl overflow-hidden cursor-pointer flex flex-col h-full shadow-[0_4px_12px_rgba(0,0,0,0.02)] relative group">
                    
                    <!-- Favorite Tag / Badge overlay -->
                    <span class="absolute top-2.5 right-2.5 z-10 px-2 py-0.5 rounded-md bg-emerald-500/90 text-white text-[9px] font-black uppercase tracking-wider backdrop-blur-sm shadow-sm" x-text="product.category"></span>

                    <!-- Product Image -->
                    <div class="w-full aspect-square overflow-hidden bg-slate-100 dark:bg-zinc-850 relative">
                        <img :src="product.image_url || ''" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out" x-on:error.once="$el.style.display='none'; $el.nextElementSibling.style.display='flex'">
                        <!-- Fallback Image Placeholder -->
                        <div class="img-placeholder w-full h-full text-4xl flex items-center justify-center" style="display:none">🍔</div>
                    </div>

                    <!-- Card Details -->
                    <div class="p-3.5 flex flex-col justify-between flex-grow">
                        <div>
                            <h3 class="font-black text-sm md:text-base text-slate-900 dark:text-white leading-tight mb-1 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors" x-text="product.name"></h3>
                            <!-- Short static preview text -->
                            <p class="text-[10px] text-slate-400 dark:text-zinc-500 line-clamp-1 leading-relaxed mb-3" x-text="getProductDescription(product)"></p>
                        </div>
                        
                        <!-- Price and Add button -->
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-zinc-850/60 mt-auto">
                            <span class="font-extrabold text-sm md:text-base text-emerald-700 dark:text-emerald-400" x-text="formatCurrency(product.base_price)"></span>
                            <!-- Direct Add to Cart Button -->
                            <button @click.stop="addToCart(product)" class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-600 dark:hover:bg-emerald-600 text-emerald-700 dark:text-emerald-400 hover:text-white flex items-center justify-center transition-colors btn-bounce shadow-sm" aria-label="إضافة للطلب">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="filteredProducts().length === 0" class="text-center py-20 bg-white dark:bg-zinc-900 rounded-3xl border border-slate-200/60 dark:border-zinc-850/60 p-8 shadow-sm">
            <div class="w-16 h-16 bg-slate-50 dark:bg-zinc-950 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🔍</div>
            <h4 class="font-black text-slate-700 dark:text-zinc-300 text-sm">عذراً، لم نجد ما تبحث عنه</h4>
            <p class="text-xs text-slate-400 dark:text-zinc-500 mt-1">تأكد من كتابة الاسم بشكل صحيح أو تصفح الأقسام الأخرى.</p>
        </div>
    </main>

    <!-- Bottom Mobile Sticky Bar (Visible if items in Cart) -->
    <div x-show="cartCount() > 0" 
         x-transition:enter="transition ease-out duration-300 transform translate-y-10"
         x-transition:enter-end="transform translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform translate-y-10"
         class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-xl border-t border-slate-200/80 dark:border-zinc-850/80 px-4 py-4 pb-6 shadow-[0_-10px_30px_rgba(0,0,0,0.05)]">
        <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
            <div>
                <span class="text-[10px] text-slate-400 dark:text-zinc-550 font-extrabold uppercase">إجمالي الطلب</span>
                <p class="text-lg font-black text-emerald-700 dark:text-emerald-400" x-text="formatCurrency(cartTotal())"></p>
            </div>
            <button @click="showCartSheet = true" class="flex-grow max-w-xs bg-emerald-700 hover:bg-emerald-800 dark:bg-emerald-650 dark:hover:bg-emerald-700 text-white font-black py-3.5 px-6 rounded-2xl text-xs tracking-wider transition-all btn-bounce shadow-lg shadow-emerald-700/10 flex items-center justify-center gap-2">
                <span>عرض السلة</span>
                <span class="bg-white/20 text-white text-[10px] px-2 py-0.5 rounded-full font-black" x-text="cartCount()"></span>
            </button>
        </div>
    </div>

    <!-- ── 1. PRODUCT DETAILS MODAL ── -->
    <div x-show="showDetailModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;" 
         x-transition>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md" @click="closeDetail()"></div>

        <!-- Modal Dialog Wrapper -->
        <div class="flex items-end sm:items-center justify-center min-h-screen p-0 sm:p-6 relative">
            
            <!-- Modal Body Content -->
            <div x-show="showDetailModal"
                 x-transition:enter="transition ease-out duration-300 transform translate-y-20 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="transform translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200 transform translate-y-20 sm:translate-y-0 sm:scale-100"
                 x-transition:leave-end="transform translate-y-20 sm:translate-y-0 sm:scale-95"
                 class="bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl max-w-md w-full overflow-hidden shadow-2xl relative z-10 text-right border border-slate-100 dark:border-zinc-800">
                
                <!-- Close Button -->
                <button @click="closeDetail()" class="absolute top-4 left-4 z-20 w-8 h-8 rounded-full bg-slate-950/30 hover:bg-slate-950/50 text-white flex items-center justify-center backdrop-blur-md transition-transform hover:rotate-90 btn-bounce">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Hero Image Area -->
                <div class="w-full aspect-[4/3] bg-slate-100 dark:bg-zinc-800 relative">
                    <img :src="selectedProduct?.image_url || ''" :alt="selectedProduct?.name" class="w-full h-full object-cover">
                    <!-- Category Badge -->
                    <span class="absolute bottom-4 right-4 px-3 py-1 rounded-full bg-emerald-600/90 text-white text-[10px] font-black backdrop-blur-sm shadow-sm" x-text="selectedProduct?.category"></span>
                </div>

                <!-- Info Area -->
                <div class="p-6 space-y-5">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white" x-text="selectedProduct?.name"></h3>
                        <p class="text-emerald-700 dark:text-emerald-400 font-black text-lg mt-1" x-text="formatCurrency(selectedProduct?.base_price)"></p>
                    </div>

                    <!-- Localized Arabic Description -->
                    <div class="space-y-1">
                        <h4 class="text-xs font-black text-slate-400 dark:text-zinc-500 uppercase tracking-wider">وصف الوجبة</h4>
                        <p class="text-xs md:text-sm text-slate-650 dark:text-zinc-300 leading-relaxed font-medium" x-text="getProductDescription(selectedProduct)"></p>
                    </div>

                    <!-- Preloaded Ingredients (Relationships) -->
                    <div class="space-y-2.5" x-show="selectedProduct?.ingredients && selectedProduct.ingredients.length > 0">
                        <h4 class="text-xs font-black text-slate-400 dark:text-zinc-500 uppercase tracking-wider">المكونات والمحضر</h4>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="ing in selectedProduct?.ingredients" :key="ing.id">
                                <span class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-zinc-850 text-slate-700 dark:text-zinc-350 text-[11px] font-bold border border-slate-100 dark:border-zinc-800/80 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span x-text="translateIngredient(ing.name)"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Dynamic Delivery/Spec Badge -->
                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 dark:border-zinc-850/60">
                        <div class="bg-amber-500/5 dark:bg-amber-500/5 p-3 rounded-2xl text-center border border-amber-500/10">
                            <span class="text-lg block">⏱️</span>
                            <span class="text-[10px] font-extrabold text-slate-400 dark:text-zinc-500">وقت التحضير</span>
                            <span class="text-xs font-black text-amber-600 dark:text-amber-400 block mt-0.5">10 - 15 دقيقة</span>
                        </div>
                        <div class="bg-emerald-500/5 dark:bg-emerald-500/5 p-3 rounded-2xl text-center border border-emerald-500/10">
                            <span class="text-lg block">🌟</span>
                            <span class="text-[10px] font-extrabold text-slate-400 dark:text-zinc-500">مستوى النظافة</span>
                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 block mt-0.5">ممتاز (طازج)</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Cart Action Buttons -->
                <div class="p-6 bg-slate-50 dark:bg-zinc-900/50 border-t border-slate-100 dark:border-zinc-850/80 flex items-center gap-3">
                    <button @click="closeDetail()" class="w-1/3 py-3.5 rounded-2xl bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 text-xs font-black transition-colors btn-bounce text-center">إغلاق</button>
                    <button @click="addToCart(selectedProduct); closeDetail()" class="w-2/3 py-3.5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 dark:bg-emerald-650 dark:hover:bg-emerald-700 text-white text-xs font-black transition-colors btn-bounce shadow-lg shadow-emerald-700/10 flex items-center justify-center gap-2">
                        <span>إضافة إلى سلة الطلب</span>
                        <span class="font-extrabold" x-text="formatCurrency(selectedProduct?.base_price)"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. SHOPPING CART & CHECKOUT SHEET ── -->
    <div x-show="showCartSheet" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;" 
         x-transition>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md" @click="showCartSheet = false"></div>

        <!-- Right Side Panel Overlay -->
        <div class="flex justify-end min-h-screen relative">
            <div x-show="showCartSheet"
                 x-transition:enter="transition ease-out duration-300 transform translate-x-full"
                 x-transition:enter-end="transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform translate-x-full"
                 class="bg-white dark:bg-zinc-900 max-w-md w-full min-h-screen shadow-2xl relative z-10 flex flex-col justify-between text-right border-r border-slate-100 dark:border-zinc-800">
                
                <!-- Cart Header -->
                <div class="p-5 border-b border-slate-100 dark:border-zinc-850/80 flex items-center justify-between bg-slate-50 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-700 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">سلة الطلبات</h3>
                        <span class="bg-emerald-100 dark:bg-emerald-950 text-emerald-850 dark:text-emerald-400 text-[10px] font-black px-2 py-0.5 rounded-full" x-text="cartCount()"></span>
                    </div>
                    <button @click="showCartSheet = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 p-2 rounded-xl bg-slate-100 dark:bg-zinc-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Main Scrollable Section -->
                <div class="flex-grow overflow-y-auto p-5 space-y-4">
                    
                    <!-- Checkout Progress State -->
                    <div x-show="orderSuccess" class="py-10 text-center space-y-4">
                        <div class="w-20 h-20 bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500 rounded-full flex items-center justify-center mx-auto text-4xl animate-bounce">🎉</div>
                        <h4 class="text-lg font-black text-slate-800 dark:text-white">تم استلام طلبك بنجاح!</h4>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 max-w-xs mx-auto leading-relaxed" x-text="successMessage"></p>
                        <button @click="clearCart(); showCartSheet = false; orderSuccess = false;" class="px-6 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs transition-colors shadow-lg">تم (العودة للمنيو)</button>
                    </div>

                    <!-- Cart Empty State -->
                    <div x-show="cart.length === 0 && !orderSuccess" class="py-20 text-center text-slate-400 dark:text-zinc-500 space-y-4">
                        <span class="text-5xl block">🛒</span>
                        <h4 class="font-black text-slate-700 dark:text-zinc-300 text-sm">السلة فارغة حالياً</h4>
                        <p class="text-xs max-w-xs mx-auto">تصفح المنيو وأضف وجباتك اللذيذة المفضلة لتظهر هنا.</p>
                    </div>

                    <!-- Cart Item Cards -->
                    <template x-if="cart.length > 0 && !orderSuccess">
                        <div class="space-y-3">
                            <template x-for="(item, index) in cart" :key="item.product.id">
                                <div class="bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-850 p-4 rounded-2xl space-y-3 shadow-inner">
                                    <div class="flex gap-3">
                                        <!-- Product Image -->
                                        <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 dark:bg-zinc-850 flex-shrink-0 relative border border-slate-200/50 dark:border-zinc-800">
                                            <img :src="item.product.image_url || ''" :alt="item.product.name" class="w-full h-full object-cover" x-on:error.once="$el.style.display='none'; $el.nextElementSibling.style.display='flex'">
                                            <div class="img-placeholder w-full h-full text-xl flex items-center justify-center bg-slate-100 dark:bg-zinc-850" style="display:none">🍔</div>
                                        </div>

                                        <!-- Name, Price & Total -->
                                        <div class="flex-grow flex flex-col justify-between py-0.5">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-black text-xs md:text-sm text-slate-800 dark:text-white" x-text="item.product.name"></h4>
                                                    <span class="text-emerald-700 dark:text-emerald-400 font-extrabold text-xs block mt-1" x-text="formatCurrency(item.product.base_price)"></span>
                                                </div>
                                                <span class="font-black text-sm text-slate-800 dark:text-white" x-text="formatCurrency(item.product.base_price * item.quantity)"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-2 border-t border-slate-200/50 dark:border-zinc-800/80">
                                        <!-- Remove button -->
                                        <button @click="removeFromCart(item.product.id)" class="text-rose-500 hover:text-rose-600 dark:text-rose-400 text-[10px] font-black hover:bg-rose-500/10 px-2.5 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span>إزالة</span>
                                        </button>

                                        <!-- Quantity controls -->
                                        <div class="flex items-center gap-2.5 bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 px-2 py-1 rounded-xl shadow-sm">
                                            <button @click="updateQuantity(item.product.id, -1)" class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-zinc-850 hover:bg-slate-250 dark:hover:bg-zinc-800 text-slate-850 dark:text-white text-xs font-black flex items-center justify-center transition-colors btn-bounce">-</button>
                                            <span class="w-5 text-center font-black text-xs text-slate-850 dark:text-white" x-text="item.quantity"></span>
                                            <button @click="updateQuantity(item.product.id, 1)" class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-zinc-850 hover:bg-slate-250 dark:hover:bg-zinc-800 text-slate-850 dark:text-white text-xs font-black flex items-center justify-center transition-colors btn-bounce">+</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Sticky Cart Footer -->
                <div x-show="cart.length > 0 && !orderSuccess" class="p-5 border-t border-slate-100 dark:border-zinc-850/80 bg-slate-50 dark:bg-zinc-900/50">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-extrabold text-slate-550 dark:text-zinc-400">القيمة الإجمالية للطلب</span>
                        <span class="text-xl font-black text-emerald-700 dark:text-emerald-400" x-text="formatCurrency(cartTotal())"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Implementation -->
    <script>
        function menuApp() {
            // Localized dictionary mapping English names to Arabic descriptions
            const arabicDescriptions = {
                'Pizza Margherita': 'بيتزا مارغريتا كلاسيكية محضرة من عجينة نابوليتانية إيطالية رقيقة ومقرمشة، يعلوها صلصة طماطم سان مارزانو الغنية، جبنة الموزاريلا الفريش الذائبة، أوراق الريحان الطازجة، ورشة خفيفة من زيت الزيتون البكر الممتاز.',
                'Chicken Shawarma': 'شاورما دجاج عربية فاخرة متبلة بخلطتنا السرية المميزة، مشوية ومقطعة بعناية، ملفوفة بخبز الصاج الطازج مع كريم الثوم الغني (الثومية)، شرائح المخلل المقرمش، والبطاطس المحمرة.',
                'Beef Burger Classic': 'برجر لحم كلاسيكي محضر من شريحة لحم بقري بلدي 100% مشوية على اللهب، مغطاة بطبقة من جبن الشيدر الذائب، خس مقرمش، طماطم طازجة، شرائح بصل، وصلصة البرجر الخاصة بنا في خبز بريوش محمص بالزبدة.',
                'Espresso': 'إسبريسو غني ومركز محضر من حبوب بن أرابيكا الفاخرة المحمصة بدرجة متوسطة، يتميز بقوام متماسك وطبقة كريمة ذهبية كثيفة تدوم طويلاً مع نوتات الشوكولاتة الداكنة.',
                'Fresh Orange Juice': 'عصير برتقال طبيعي 100% معصور طازجاً عند الطلب من ثمار البرتقال المنتقاة بعناية. خالٍ من السكر المضاف أو المواد الحافظة، غني بفيتامين C ومنعش للغاية.'
            };

            // Localized dictionary translating English database ingredient names to Arabic
            const ingredientTranslations = {
                'Flour': 'دقيق فاخر',
                'Mozzarella Cheese': 'جبن موزاريلا طبيعي',
                'Tomato Sauce': 'صلصة طماطم منزلية',
                'Chicken Breast': 'صدور دجاج متبلة بالبهارات',
                'Burger Beef': 'لحم بقري بلدي 100%',
                'Espresso Beans': 'حبوب بن فاخرة',
                'Fresh Oranges': 'برتقال طبيعي طازج'
            };

            return {
                products: @json($products),
                categories: @json($categories),
                selectedCategory: 'all',
                searchQuery: '',
                selectedProduct: null,
                showDetailModal: false,

                // Cart details
                cart: [],
                showCartSheet: false,
                orderType: 'table',
                customerName: '',
                tableNumber: '',
                notes: '',
                submitting: false,
                orderSuccess: false,
                successMessage: '',

                // Visual Toast Array
                toasts: [],

                // Aesthetic Theme State
                theme: 'light',

                initMenu() {
                    // Load Cart from localStorage
                    const savedCart = localStorage.getItem('bellosmash_cart');
                    if (savedCart) {
                        try {
                            this.cart = JSON.parse(savedCart);
                        } catch (e) {
                            this.cart = [];
                        }
                    }

                    // Check Saved Theme or OS System Defaults
                    const savedTheme = localStorage.getItem('theme');
                    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        this.theme = 'dark';
                    } else {
                        this.theme = 'light';
                    }
                },

                // Filters Products depending on Active Category selection & search keywords
                filteredProducts() {
                    return this.products.filter(p => {
                        const matchesCategory = this.selectedCategory === 'all' || p.category === this.selectedCategory;
                        const matchesSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                              p.category.toLowerCase().includes(this.searchQuery.toLowerCase());
                        return matchesCategory && matchesSearch;
                    });
                },

                // Fetch Details for a Specific Product and Open Modal
                openDetail(product) {
                    this.selectedProduct = product;
                    this.showDetailModal = true;
                },

                closeDetail() {
                    this.showDetailModal = false;
                    setTimeout(() => {
                        this.selectedProduct = null;
                    }, 200);
                },

                // Localized Arabic description accessor
                getProductDescription(product) {
                    if (!product) return '';
                    return arabicDescriptions[product.name] || 'طبق مميز يحضر بعناية من أفضل المكونات الطازجة والتوابل الخاصة ليقدم لكم تجربة مذاق فريدة ولذيذة.';
                },

                // Localized Arabic ingredient translation mapping helper
                translateIngredient(name) {
                    return ingredientTranslations[name] || name;
                },

                // Theme Toggle handler
                toggleTheme() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                },

                // Add an Item to Cart and trigger alert
                addToCart(product) {
                    const existingItem = this.cart.find(item => item.product.id === product.id);
                    if (existingItem) {
                        existingItem.quantity += 1;
                    } else {
                        this.cart.push({
                            product: product,
                            quantity: 1
                        });
                    }
                    this.saveCart();
                    this.showToast(`تمت إضافة ${product.name} إلى السلة`, 'success');
                },

                removeFromCart(productId) {
                    this.cart = this.cart.filter(item => item.product.id !== productId);
                    this.saveCart();
                    this.showToast('تمت إزالة الوجبة من السلة', 'warning');
                },

                updateQuantity(productId, delta) {
                    const item = this.cart.find(item => item.product.id === productId);
                    if (item) {
                        item.quantity += delta;
                        if (item.quantity <= 0) {
                            this.removeFromCart(productId);
                        } else {
                            this.saveCart();
                        }
                    }
                },

                saveCart() {
                    localStorage.setItem('bellosmash_cart', JSON.stringify(this.cart));
                },

                clearCart() {
                    this.cart = [];
                    localStorage.removeItem('bellosmash_cart');
                },

                cartTotal() {
                    return this.cart.reduce((total, item) => {
                        return total + (parseFloat(item.product.base_price) * item.quantity);
                    }, 0);
                },

                cartCount() {
                    return this.cart.reduce((count, item) => count + item.quantity, 0);
                },

                formatCurrency(amount) {
                    return Number(amount).toFixed(2) + ' د.ل';
                },

                showToast(message, type = 'success') {
                    const id = Date.now();
                    this.toasts.push({ id, message, type });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 3000);
                },

                // Submits Order JSON Payload directly to back-end API
                async submitOrder() {
                    if (this.submitting) return;

                    // Form Validation checks
                    if (!this.customerName.trim()) {
                        this.showToast('الرجاء إدخال اسم الزبون', 'warning');
                        return;
                    }
                    if (this.orderType === 'table' && !this.tableNumber) {
                        this.showToast('الرجاء إدخال رقم الطاولة للطلب المحلي', 'warning');
                        return;
                    }

                    this.submitting = true;

                    // Prepare items parameter matching Controller constraints
                    const itemsPayload = this.cart.map(item => ({
                        id: item.product.id,
                        name: item.product.name,
                        price: parseFloat(item.product.base_price),
                        quantity: item.quantity
                    }));

                    const payload = {
                        customer_name: this.customerName,
                        table_number: this.orderType === 'table' ? parseInt(this.tableNumber) : null,
                        notes: this.notes,
                        order_type: this.orderType,
                        items: itemsPayload,
                        total_amount: this.cartTotal()
                    };

                    try {
                        const response = await fetch('/api/customer/orders', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            this.orderSuccess = true;
                            this.successMessage = result.message || 'تم إرسال طلبك بنجاح للمطبخ لتجهيزه!';
                            this.showToast('تم إرسال طلبك بنجاح!', 'success');
                            // Clear form fields
                            this.tableNumber = '';
                            this.notes = '';
                        } else {
                            this.showToast(result.error || 'حدث خطأ أثناء إرسال طلبك. يرجى المحاولة لاحقاً.', 'warning');
                        }
                    } catch (error) {
                        console.error('Order submission error:', error);
                        this.showToast('عذراً، حدث خطأ في الاتصال بالخادم. يرجى التأكد من اتصالك بالإنترنت.', 'warning');
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
