<!-- Premium Arabic-English Shared Navigation Sidebar -->
<div x-data="sidebarNotificationApp()" @toggle-sidebar.window="isOpen = !isOpen" class="contents">
    <!-- Global Top Progress Loading Bar -->
    <div id="global-top-loader" class="fixed top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 z-[9999] opacity-0 transition-all duration-300 ease-out pointer-events-none" style="width: 0%;"></div>

    <aside class="fixed lg:sticky top-0 right-0 h-screen z-50 w-[280px] bg-white dark:bg-slate-950 border-l border-slate-100 dark:border-slate-800 flex flex-col justify-between flex-shrink-0 transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] shadow-2xl lg:shadow-none"
           :class="isOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">

        <div class="p-6 space-y-8 relative z-10 overflow-y-auto hide-scrollbar">
            <!-- Logo Header -->
            <div class="flex items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-6">
                <div class="w-12 h-12 flex-shrink-0 rounded-[14px] bg-amber-500 flex items-center justify-center font-black text-white shadow-[0_4px_12px_rgba(245,158,11,0.3)] text-xl hover:rotate-6 transition-transform duration-300">
                    M
                </div>
                <div>
                    <h1 class="text-sm font-black tracking-tight text-slate-900 dark:text-white leading-tight">Al-Madina POS</h1>
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider block mt-0.5">منظومة ذكية متكاملة</span>
                </div>
            </div>

            <!-- Theme Toggle Widget (Sun/Moon Switch) -->
            <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl p-3 shadow-sm">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">مظهر المنظومة</span>
                <button @click="toggleTheme()" 
                        class="relative w-12 h-6 rounded-full transition-colors duration-300 focus:outline-none flex items-center p-1 shadow-inner"
                        :class="isDark ? 'bg-amber-500' : 'bg-slate-300'">
                    <!-- Moving Dial -->
                    <div class="w-5 h-5 rounded-full bg-white flex items-center justify-center text-[10px] transition-transform duration-300 shadow-sm"
                         :class="isDark ? 'translate-x-0' : '-translate-x-6'">
                        <span x-show="!isDark" class="text-amber-500">☀️</span>
                        <span x-show="isDark" class="text-slate-900">🌙</span>
                    </div>
                </button>
            </div>

            <!-- Date Filter Widget -->
            <div x-data="{ preset: '{{ $datePreset ?? 'this_month' }}' }" 
                 class="bg-slate-50 dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-4.5 space-y-3 shadow-sm text-right">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-black text-slate-850 dark:text-slate-200">📅 تصفية الفترة الزمنية</span>
                </div>
                <form action="" method="GET" class="space-y-3">
                    <!-- Preset Select -->
                    <div>
                        <select name="date_preset" x-model="preset" 
                                class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 focus:outline-none focus:border-amber-500">
                            <option value="today">📆 اليوم (Today)</option>
                            <option value="yesterday">🗓️ أمس (Yesterday)</option>
                            <option value="this_week">📅 هذا الأسبوع (This Week)</option>
                            <option value="this_month">📊 هذا الشهر (This Month)</option>
                            <option value="custom">⚙️ فترة مخصصة (Custom)</option>
                        </select>
                    </div>

                    <!-- Custom Date Inputs -->
                    <div x-show="preset === 'custom'" x-transition.duration.300ms class="space-y-2" style="display: none;">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 block">من تاريخ:</label>
                            <input type="date" name="start_date" value="{{ isset($startDate) ? substr($startDate, 0, 10) : '' }}"
                                   class="w-full bg-white dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs font-mono text-slate-700 dark:text-slate-300 focus:outline-none focus:border-amber-500 text-center" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 block">إلى تاريخ:</label>
                            <input type="date" name="end_date" value="{{ isset($endDate) ? substr($endDate, 0, 10) : '' }}"
                                   class="w-full bg-white dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs font-mono text-slate-700 dark:text-slate-300 focus:outline-none focus:border-amber-500 text-center" />
                        </div>
                    </div>

                    <!-- Apply Button -->
                    <button type="submit" 
                            class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs py-2 rounded-xl transition-all shadow-md shadow-amber-500/10 hover:shadow-amber-500/20">
                        تطبيق الفلترة ⚡
                    </button>
                </form>
            </div>


            <!-- Navigation Links -->
            <nav class="space-y-1">
                @if(auth()->check())
                    <!-- POS Cashier Link (Admin & Cashier only) -->
                    @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                        <a href="/pos" 
                           class="flex items-center justify-between px-4 py-3.5 rounded-xl text-sm font-bold transition-all duration-200 group {{ request()->is('pos') ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900' }}">
                            <div class="flex items-center gap-4">
                                <span class="text-lg group-hover:scale-110 transition-transform">🛒</span>
                                <span>شاشة الكاشير</span>
                            </div>
                            <span class="text-[9px] {{ request()->is('pos') ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }} px-2 py-0.5 rounded-md font-bold tracking-wider uppercase">POS</span>
                        </a>
                    @endif



                    <!-- Dashboard Statistics (Admin only) -->
                    @if(auth()->user()->role === 'admin')
                        <a href="/admin" 
                           class="flex items-center justify-between px-4 py-3.5 rounded-xl text-sm font-bold transition-all duration-200 group {{ request()->is('admin') ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900' }}">
                            <div class="flex items-center gap-4">
                                <span class="text-lg group-hover:scale-110 transition-transform">📊</span>
                                <span>التحليلات</span>
                            </div>
                            <span class="text-[9px] {{ request()->is('admin') ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }} px-2 py-0.5 rounded-md font-bold tracking-wider uppercase">Stats</span>
                        </a>
                    @endif

                    <!-- Inventory Manager Link (Admin only) -->
                    @if(auth()->user()->role === 'admin')
                        <a href="/admin/inventory" 
                           class="flex items-center justify-between px-4 py-3.5 rounded-xl text-sm font-bold transition-all duration-200 group {{ request()->is('admin/inventory*') ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900' }}">
                            <div class="flex items-center gap-4">
                                <span class="text-lg group-hover:scale-110 transition-transform">📦</span>
                                <span>المخازن والجرد</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span x-show="lowStockCount > 0" class="bg-rose-500 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center font-bold" x-text="lowStockCount" style="display: none;"></span>
                                <span class="text-[9px] {{ request()->is('admin/inventory*') ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }} px-2 py-0.5 rounded-md font-bold tracking-wider uppercase">Stock</span>
                            </div>
                        </a>
                    @endif

                    <!-- Order Sales History Link (Admin & Cashier only) -->
                    @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                        <a href="/admin/orders" 
                           class="flex items-center justify-between px-4 py-3.5 rounded-xl text-sm font-bold transition-all duration-200 group {{ request()->is('admin/orders') ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900' }}">
                            <div class="flex items-center gap-4">
                                <span class="text-lg group-hover:scale-110 transition-transform">📜</span>
                                <span>الفواتير اليومية</span>
                            </div>
                            <span class="text-[9px] {{ request()->is('admin/orders') ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }} px-2 py-0.5 rounded-md font-bold tracking-wider uppercase">Today</span>
                        </a>

                        <a href="/admin/orders/archive" 
                           class="flex items-center justify-between px-4 py-3.5 rounded-xl text-sm font-bold transition-all duration-200 group {{ request()->is('admin/orders/archive') ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900' }}">
                            <div class="flex items-center gap-4">
                                <span class="text-lg group-hover:scale-110 transition-transform">🗄️</span>
                                <span>أرشيف الفواتير</span>
                            </div>
                            <span class="text-[9px] {{ request()->is('admin/orders/archive') ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }} px-2 py-0.5 rounded-md font-bold tracking-wider uppercase">Archive</span>
                        </a>
                    @endif
                @endif
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950 space-y-4 relative z-10 flex-shrink-0">
            <!-- Device Web Notification Enabler -->
            <template x-if="'Notification' in window">
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-3 rounded-[16px] flex items-center justify-between gap-2 shadow-sm">
                    <div class="flex flex-col gap-0.5 text-right">
                        <span class="text-[10px] font-bold text-slate-800 dark:text-slate-300">تنبيهات نواقص المخزون</span>
                        <span class="text-[8px] text-slate-500" x-text="notificationPermission === 'granted' ? 'مفعلة على هذا الجهاز' : 'انقر لتلقي الإشعارات'"></span>
                    </div>
                    <button @click="requestPermission()" 
                            class="p-2 rounded-xl text-[10px] transition-all duration-300 font-bold"
                            :class="notificationPermission === 'granted' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm'">
                        <span x-text="notificationPermission === 'granted' ? '🔔 نشط' : '🔔 تفعيل'"></span>
                    </button>
                </div>
            </template>

            <!-- Logged-in Profile Box -->
            @if(auth()->check())
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-3.5 rounded-[16px] space-y-4 shadow-sm">
                    <div class="flex items-center gap-3 text-right">
                        <!-- Modern clean avatar placeholder -->
                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 font-bold flex-shrink-0">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-grow">
                            <span class="font-bold text-sm text-slate-900 dark:text-white block truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-slate-500 block truncate" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <!-- Role badge -->
                        <div class="flex-1 text-[10px] font-bold uppercase py-2 rounded-xl flex items-center justify-center border
                            @if(auth()->user()->role === 'admin') bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 border-amber-200 dark:border-amber-500/20
                            @elseif(auth()->user()->role === 'chef') bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 border-emerald-200 dark:border-emerald-500/20
                            @else bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-500 border-blue-200 dark:border-blue-500/20 @endif">
                            @if(auth()->user()->role === 'admin') 👑 المدير
                            @elseif(auth()->user()->role === 'chef') 👨‍🍳 الطاهي
                            @else 👨‍💼 الكاشير @endif
                        </div>

                        <form action="/logout" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 py-2 rounded-xl text-[10px] font-bold transition-colors flex items-center justify-center gap-1.5">
                                خروج
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between px-1">
                <div class="flex flex-col text-right">
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 font-bold">حالة النظام</span>
                    <span class="text-[8px] text-slate-400 dark:text-slate-500">سيرفر الفرع المحلي (نشط)</span>
                </div>
                <div class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-50 dark:bg-emerald-500/10">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                </div>
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
