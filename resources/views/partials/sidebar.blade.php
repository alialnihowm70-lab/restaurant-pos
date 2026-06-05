<!-- Premium Arabic-English Shared Navigation Sidebar -->
<div x-data="sidebarNotificationApp()" @toggle-sidebar.window="isOpen = !isOpen" class="contents">
    <!-- Global Top Progress Loading Bar -->
    <div id="global-top-loader" class="fixed top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 z-[9999] opacity-0 transition-all duration-300 ease-out pointer-events-none" style="width: 0%;"></div>

    <aside class="fixed lg:sticky top-0 right-0 h-screen z-50 w-72 bg-slate-950/95 backdrop-blur-xl border-l border-slate-800/80 flex flex-col justify-between flex-shrink-0 transition-all duration-350 ease-out transform lg:transform-none shadow-2xl"
           :class="isOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
        
        <!-- Decorative Glow inside Sidebar -->
        <div class="absolute top-0 right-0 w-36 h-36 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-20 left-0 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="p-6 space-y-8 relative z-10">
            <!-- Logo Header -->
            <div class="flex items-center gap-3.5 border-b border-slate-850 pb-5 justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-amber-500 via-orange-500 to-red-500 flex items-center justify-center font-black text-slate-950 shadow-xl shadow-orange-500/20 text-xl tracking-wider hover:rotate-12 transition-transform duration-300">
                        M
                    </div>
                    <div>
                        <h1 class="text-xs font-black tracking-tight text-white uppercase">Al-Madina POS</h1>
                        <span class="text-[9px] text-amber-500 font-extrabold uppercase tracking-widest block mt-0.5">منظومة المدينة المتكاملة</span>
                    </div>
                </div>
            </div>

            <!-- Theme Toggle Widget (Sun/Moon Switch) -->
            <div class="flex items-center justify-between bg-slate-900/60 backdrop-blur-md border border-slate-800/80 rounded-2xl p-2.5 shadow-inner">
                <span class="text-[10px] font-black text-slate-350">مظهر المنظومة</span>
                <button @click="toggleTheme()" 
                        class="relative w-12 h-6.5 rounded-full transition-colors duration-300 focus:outline-none flex items-center p-1 bg-slate-800 border border-slate-700/60"
                        :class="isDark ? 'bg-amber-500/10 border-amber-500/30' : 'bg-slate-800 border-slate-700/60'">
                    <!-- Moving Dial -->
                    <div class="w-4.5 h-4.5 rounded-full flex items-center justify-center text-[9px] transition-all duration-300 transform shadow-md"
                         :class="isDark ? 'translate-x-0 bg-amber-550 text-slate-950 rotate-0' : '-translate-x-5.5 bg-slate-700 text-slate-300 rotate-180'">
                        <span x-text="isDark ? '🌙' : '☀️'"></span>
                    </div>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-2.5">
                @if(auth()->check())
                    <!-- POS Cashier Link (Admin & Cashier only) -->
                    @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                        <a href="/pos" 
                           class="flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('pos') ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 shadow-lg shadow-orange-500/20 scale-[1.01]' : 'text-slate-400 hover:bg-slate-900/60 hover:text-white border border-transparent hover:border-slate-800/40' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-base group-hover:scale-110 transition-transform group-hover:rotate-12">🛒</span>
                                <div class="flex flex-col text-right">
                                    <span>شاشة الكاشير (POS)</span>
                                    <span class="text-[8px] {{ request()->is('pos') ? 'text-slate-900/80 font-semibold' : 'text-slate-500 font-normal' }}">نقاط بيع وتسجيل الفواتير</span>
                                </div>
                            </div>
                            <span class="text-[8px] {{ request()->is('pos') ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-800 text-slate-400' }} px-2 py-0.5 rounded-md font-black tracking-wider uppercase">POS</span>
                        </a>
                    @endif

                    <!-- Kitchen Board KDS Link (Admin & Chef only) -->
                    @if(in_array(auth()->user()->role, ['admin', 'chef']))
                        <a href="/kds" 
                           class="flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('kds') ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 shadow-lg shadow-orange-500/20 scale-[1.01]' : 'text-slate-400 hover:bg-slate-900/60 hover:text-white border border-transparent hover:border-slate-800/40' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-base group-hover:scale-110 transition-transform group-hover:-rotate-12">🍳</span>
                                <div class="flex flex-col text-right">
                                    <span>شاشة المطبخ (KDS)</span>
                                    <span class="text-[8px] {{ request()->is('kds') ? 'text-slate-900/80 font-semibold' : 'text-slate-500 font-normal' }}">مراقبة وتحضير الطلبات</span>
                                </div>
                            </div>
                            <span class="text-[8px] {{ request()->is('kds') ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-800 text-slate-400' }} px-2 py-0.5 rounded-md font-black tracking-wider uppercase">KDS</span>
                        </a>
                    @endif

                    <!-- Dashboard Statistics (Admin only) -->
                    @if(auth()->user()->role === 'admin')
                        <a href="/admin" 
                           class="flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('admin') ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 shadow-lg shadow-orange-500/20 scale-[1.01]' : 'text-slate-400 hover:bg-slate-900/60 hover:text-white border border-transparent hover:border-slate-800/40' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-base group-hover:scale-110 transition-transform group-hover:bounce">📊</span>
                                <div class="flex flex-col text-right">
                                    <span>لوحة الإدارة والتحليلات</span>
                                    <span class="text-[8px] {{ request()->is('admin') ? 'text-slate-900/80 font-semibold' : 'text-slate-500 font-normal' }}">إحصائيات الإيرادات والموظفين</span>
                                </div>
                            </div>
                            <span class="text-[8px] {{ request()->is('admin') ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-800 text-slate-400' }} px-2 py-0.5 rounded-md font-black tracking-wider uppercase">Stats</span>
                        </a>
                    @endif

                    <!-- Inventory Manager Link (Admin only) -->
                    @if(auth()->user()->role === 'admin')
                        <a href="/admin/inventory" 
                           class="flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('admin/inventory*') ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 shadow-lg shadow-orange-500/20 scale-[1.01]' : 'text-slate-400 hover:bg-slate-900/60 hover:text-white border border-transparent hover:border-slate-800/40' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-base group-hover:scale-110 transition-transform group-hover:pulse">📦</span>
                                <div class="flex flex-col text-right">
                                    <span>المخازن ومطابقة الجرد</span>
                                    <span class="text-[8px] {{ request()->is('admin/inventory*') ? 'text-slate-900/80 font-semibold' : 'text-slate-500 font-normal' }}">الأصناف ومواد الوصفات الأساسية</span>
                                </div>
                            </div>
                            <!-- Warning count badge & Active text -->
                            <div class="flex items-center gap-1.5">
                                <span x-show="lowStockCount > 0" class="bg-rose-500 text-white text-[9px] w-5 h-5 rounded-full flex items-center justify-center font-black animate-pulse" x-text="lowStockCount" style="display: none;"></span>
                                <span class="text-[8px] {{ request()->is('admin/inventory*') ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-800 text-slate-400' }} px-2 py-0.5 rounded-md font-black tracking-wider uppercase">Stock</span>
                            </div>
                        </a>
                    @endif

                    <!-- Order Sales History Link (Admin & Cashier only) -->
                    @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                        <a href="/admin/orders" 
                           class="flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('admin/orders*') ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 shadow-lg shadow-orange-550/20 scale-[1.01]' : 'text-slate-400 hover:bg-slate-900/60 hover:text-white border border-transparent hover:border-slate-800/40' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-base group-hover:scale-110 transition-transform group-hover:rotate-6">📜</span>
                                <div class="flex flex-col text-right">
                                    <span>سجل الفواتير والمبيعات</span>
                                    <span class="text-[8px] {{ request()->is('admin/orders*') ? 'text-slate-900/80 font-semibold' : 'text-slate-500 font-normal' }}">أرشيف العمليات وطباعة الفواتير</span>
                                </div>
                            </div>
                            <span class="text-[8px] {{ request()->is('admin/orders*') ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-800 text-slate-400' }} px-2 py-0.5 rounded-md font-black tracking-wider uppercase">Sales</span>
                        </a>
                    @endif
                @endif
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-6 border-t border-slate-900 bg-slate-950/50 space-y-4 relative z-10">
            <!-- Device Web Notification Enabler -->
            <template x-if="'Notification' in window">
                <div class="bg-slate-900/60 border border-slate-800/60 p-2.5 rounded-xl flex items-center justify-between gap-2 shadow-inner">
                    <div class="flex flex-col gap-0.5 text-right">
                        <span class="text-[8px] font-extrabold uppercase text-slate-400">تنبيهات نواقص المخزون</span>
                        <span class="text-[7px] text-slate-500" x-text="notificationPermission === 'granted' ? 'مفعلة على الجهاز' : 'غير مفعلة حالياً'"></span>
                    </div>
                    <button @click="requestPermission()" 
                            class="p-2 rounded-lg text-[9px] transition-all duration-300 font-bold"
                            :class="notificationPermission === 'granted' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25' : 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-slate-950 shadow-md'">
                        <span x-text="notificationPermission === 'granted' ? '🔔 نشط' : '🔔 تفعيل'"></span>
                    </button>
                </div>
            </template>

            <!-- Logged-in Profile Box -->
            @if(auth()->check())
                <div class="bg-slate-900/60 border border-slate-800/60 p-3.5 rounded-2xl space-y-3 shadow-inner">
                    <div class="flex items-center justify-between gap-2 text-right">
                        <div class="min-w-0 flex-grow">
                            <span class="font-black text-[10px] text-white block truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                            <span class="text-[8px] text-slate-500 block truncate" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                        </div>
                        <!-- Role badge -->
                        <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded-md flex-shrink-0 border
                            @if(auth()->user()->role === 'admin') bg-rose-500/10 text-rose-450 border-rose-500/20
                            @elseif(auth()->user()->role === 'chef') bg-emerald-500/10 text-emerald-450 border-emerald-500/20
                            @else bg-sky-500/10 text-sky-450 border-sky-500/20 @endif">
                            @if(auth()->user()->role === 'admin') المدير
                            @elseif(auth()->user()->role === 'chef') الطاهي
                            @else الكاشير @endif
                        </span>
                    </div>

                    <form action="/logout" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full bg-rose-950/20 hover:bg-rose-900/30 text-rose-400 hover:text-rose-300 border border-rose-900/30 hover:border-rose-800/50 py-2 rounded-xl text-[9px] font-extrabold transition-all duration-300 flex items-center justify-center gap-1.5 shadow-sm">
                            <span>🚪</span> تسجيل الخروج (Logout)
                        </button>
                    </form>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div class="flex flex-col text-right">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">سيرفر الفرع المحلي</span>
                    <span class="text-[8px] text-slate-655 font-medium">عقدة طرابلس، ليبيا</span>
                </div>
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse"></div>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar Overlay (Backdrop) -->
    <div x-show="isOpen" 
         @click="isOpen = false" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm z-40 lg:hidden"
         style="display: none;">
    </div>


</div>

<script>
    function sidebarNotificationApp() {
        return {
            isOpen: false,
            lowStockCount: 0,
            notificationPermission: 'default',
            isDark: localStorage.getItem('theme') === 'dark',

            toggleTheme() {
                this.isDark = !this.isDark;
                if (this.isDark) {
                    localStorage.setItem('theme', 'dark');
                    document.documentElement.classList.add('dark');
                } else {
                    localStorage.setItem('theme', 'light');
                    document.documentElement.classList.remove('dark');
                }
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: this.isDark } }));
            },

            init() {
                if ('Notification' in window) {
                    this.notificationPermission = Notification.permission;
                }

                // Add transition class after initial loading
                setTimeout(() => {
                    document.documentElement.classList.add('theme-transition');
                }, 100);
                
                // Perform initial check
                this.checkLowStock();
                
                // Poll stock alerts every 30 seconds
                setInterval(() => {
                    this.checkLowStock();
                }, 30000);

                // Add page unload progress bar trigger
                window.addEventListener('beforeunload', () => {
                    const loader = document.getElementById('global-top-loader');
                    if (loader) {
                        loader.style.opacity = '1';
                        loader.style.width = '90%';
                    }
                });

                // Add instant click interceptors on sidebar links to show progress immediately
                this.$nextTick(() => {
                    const links = document.querySelectorAll('aside a[href]');
                    links.forEach(link => {
                        link.addEventListener('click', (e) => {
                            if (!e.ctrlKey && !e.metaKey && !e.shiftKey && link.getAttribute('target') !== '_blank') {
                                const loader = document.getElementById('global-top-loader');
                                if (loader) {
                                    loader.style.opacity = '1';
                                    loader.style.width = '60%';
                                    let w = 60;
                                    const interval = setInterval(() => {
                                        if (w < 95) {
                                            w += 5;
                                            loader.style.width = w + '%';
                                        } else {
                                            clearInterval(interval);
                                        }
                                    }, 100);
                                }
                            }
                        });
                    });
                });
            },

            requestPermission() {
                if (!('Notification' in window)) return;
                Notification.requestPermission().then(permission => {
                    this.notificationPermission = permission;
                });
            },

            checkLowStock() {
                fetch('/api/ingredients/low-stock')
                    .then(res => res.json())
                    .then(data => {
                        this.lowStockCount = data.length;
                        
                        // Keep track of current alert IDs to clean old sessionStorage keys
                        const currentAlertIds = data.map(ing => ing.id);
                        
                        data.forEach(ing => {
                            const sessionKey = 'notified_ing_' + ing.id;
                            const alreadyNotified = sessionStorage.getItem(sessionKey);
                            
                            // Trigger device OS notification if permission granted and not already shown in this session
                            if (this.notificationPermission === 'granted' && !alreadyNotified) {
                                new Notification("⚠️ تنبيه: نقص مخزون " + ing.name, {
                                    body: `المتبقي في المخازن: ${ing.current_stock} ${ing.unit} (الحد المسموح به: ${ing.alert_threshold} ${ing.unit})`,
                                    icon: '/manifest.json'
                                });
                                sessionStorage.setItem(sessionKey, 'true');
                            }
                        });
                        
                        // Clean up notified keys in sessionStorage for ingredients that are no longer low-stock
                        for (let i = 0; i < sessionStorage.length; i++) {
                            const key = sessionStorage.key(i);
                            if (key && key.startsWith('notified_ing_')) {
                                const ingId = key.replace('notified_ing_', '');
                                if (!currentAlertIds.includes(ingId)) {
                                    sessionStorage.removeItem(key);
                                }
                            }
                        }
                    })
                    .catch(err => console.error("Failed to check low stock status: ", err));
            }
        };
    }
</script>
