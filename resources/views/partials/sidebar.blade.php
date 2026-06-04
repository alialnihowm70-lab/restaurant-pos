<!-- Premium Arabic-English Shared Navigation Sidebar -->
<div x-data="sidebarNotificationApp()" class="contents">
    <aside class="fixed lg:sticky top-0 right-0 h-screen z-50 w-72 bg-white border-l border-slate-200 flex flex-col justify-between flex-shrink-0 transition-transform duration-300 transform lg:transform-none"
           :class="isOpen ? 'translate-x-0 shadow-2xl' : 'translate-x-full lg:translate-x-0'">
    <div class="p-6 space-y-8">
        <!-- Logo Header -->
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 via-orange-500 to-red-500 flex items-center justify-center font-black text-slate-950 shadow-lg shadow-amber-500/20 text-xl tracking-wider">
                M
            </div>
            <div>
                <h1 class="text-sm font-extrabold tracking-tight text-slate-800 uppercase">Al-Madina POS</h1>
                <span class="text-[9px] text-amber-600 font-bold uppercase tracking-widest block mt-0.5">منظومة المدينة المتكاملة</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-2">
            @if(auth()->check())
                <!-- POS Cashier Link (Admin & Cashier only) -->
                @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                    <a href="/pos" 
                       class="flex items-center justify-between px-4 py-3.5 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('pos') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 shadow-lg shadow-amber-500/15' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <div class="flex items-center gap-3">
                            <span class="text-lg group-hover:scale-110 transition-transform">🛒</span>
                            <div class="flex flex-col">
                                <span>شاشة الكاشير (POS)</span>
                                <span class="text-[9px] {{ request()->is('pos') ? 'text-slate-850 font-medium' : 'text-slate-400 font-normal' }}">نقاط بيع وتسجيل الفواتير</span>
                            </div>
                        </div>
                        <span class="text-[10px] bg-slate-950/10 px-2 py-0.5 rounded font-black text-slate-800" x-show="{{ request()->is('pos') ? 'true' : 'false' }}">نشط</span>
                    </a>
                @endif

                <!-- Kitchen Board KDS Link (Admin & Chef only) -->
                @if(in_array(auth()->user()->role, ['admin', 'chef']))
                    <a href="/kds" 
                       class="flex items-center justify-between px-4 py-3.5 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('kds') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 shadow-lg shadow-amber-500/15' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <div class="flex items-center gap-3">
                            <span class="text-lg group-hover:scale-110 transition-transform">🍳</span>
                            <div class="flex flex-col">
                                <span>شاشة المطبخ (KDS)</span>
                                <span class="text-[9px] {{ request()->is('kds') ? 'text-slate-850 font-medium' : 'text-slate-400 font-normal' }}">مراقبة وتحضير الطلبات</span>
                            </div>
                        </div>
                        <span class="text-[10px] bg-slate-950/10 px-2 py-0.5 rounded font-black text-slate-800" x-show="{{ request()->is('kds') ? 'true' : 'false' }}">نشط</span>
                    </a>
                @endif

                <!-- Dashboard Statistics (Admin only) -->
                @if(auth()->user()->role === 'admin')
                    <a href="/admin" 
                       class="flex items-center justify-between px-4 py-3.5 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('admin') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 shadow-lg shadow-amber-500/15' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <div class="flex items-center gap-3">
                            <span class="text-lg group-hover:scale-110 transition-transform">📊</span>
                            <div class="flex flex-col">
                                <span>لوحة الإدارة والتحليلات</span>
                                <span class="text-[9px] {{ request()->is('admin') ? 'text-slate-850 font-medium' : 'text-slate-400 font-normal' }}">إحصائيات الإيرادات والموظفين</span>
                            </div>
                        </div>
                        <span class="text-[10px] bg-slate-950/10 px-2 py-0.5 rounded font-black text-slate-800" x-show="{{ request()->is('admin') ? 'true' : 'false' }}">نشط</span>
                    </a>
                @endif

                <!-- Inventory Manager Link (Admin only) -->
                @if(auth()->user()->role === 'admin')
                    <a href="/admin/inventory" 
                       class="flex items-center justify-between px-4 py-3.5 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('admin/inventory*') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 shadow-lg shadow-amber-500/15' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <div class="flex items-center gap-3">
                            <span class="text-lg group-hover:scale-110 transition-transform">📦</span>
                            <div class="flex flex-col">
                                <span>المخازن ومطابقة الجرد</span>
                                <span class="text-[9px] {{ request()->is('admin/inventory*') ? 'text-slate-850 font-medium' : 'text-slate-400 font-normal' }}">المنتجات ومواد الوصفات الأساسية</span>
                            </div>
                        </div>
                        <!-- Warning count badge & Active text -->
                        <div class="flex items-center gap-1.5">
                            <span x-show="lowStockCount > 0" class="bg-red-500 text-white text-[9px] w-5 h-5 rounded-full flex items-center justify-center font-black animate-pulse" x-text="lowStockCount" style="display: none;"></span>
                            <span class="text-[10px] bg-slate-950/10 px-2 py-0.5 rounded font-black text-slate-800" x-show="{{ request()->is('admin/inventory*') ? 'true' : 'false' }}">نشط</span>
                        </div>
                    </a>
                @endif

                <!-- Order Sales History Link (Admin & Cashier only) -->
                @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                    <a href="/admin/orders" 
                       class="flex items-center justify-between px-4 py-3.5 rounded-2xl text-xs font-bold transition-all duration-300 group {{ request()->is('admin/orders*') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 shadow-lg shadow-amber-500/15' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <div class="flex items-center gap-3">
                            <span class="text-lg group-hover:scale-110 transition-transform">📜</span>
                            <div class="flex flex-col">
                                <span>سجل الفواتير والمبيعات</span>
                                <span class="text-[9px] {{ request()->is('admin/orders*') ? 'text-slate-850 font-medium' : 'text-slate-400 font-normal' }}">أرشيف العمليات وطباعة الفواتير</span>
                            </div>
                        </div>
                        <span class="text-[10px] bg-slate-950/10 px-2 py-0.5 rounded font-black text-slate-800" x-show="{{ request()->is('admin/orders*') ? 'true' : 'false' }}">نشط</span>
                    </a>
                @endif
            @endif
        </nav>
    </div>

    <!-- Sidebar Footer -->
    <div class="p-6 border-t border-slate-200 bg-slate-50/50 space-y-4">
        <!-- Device Web Notification Enabler -->
        <template x-if="'Notification' in window">
            <div class="bg-white border border-slate-200 p-2.5 rounded-xl flex items-center justify-between gap-2 shadow-sm">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[9px] font-extrabold uppercase text-slate-550">تنبيهات نواقص المخزون</span>
                    <span class="text-[8px] text-slate-400" x-text="notificationPermission === 'granted' ? 'مفعلة على الجهاز' : 'غير مفعلة حالياً'"></span>
                </div>
                <button @click="requestPermission()" 
                        class="p-2 rounded-lg text-[10px] transition-all duration-300 font-bold"
                        :class="notificationPermission === 'granted' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-md shadow-amber-500/10'">
                    <span x-text="notificationPermission === 'granted' ? '🔔 نشط' : '🔔 تفعيل'"></span>
                </button>
            </div>
        </template>

        <!-- Logged-in Profile Box (Phase 8 premium detail) -->
        @if(auth()->check())
            <div class="bg-white border border-slate-200 p-3 rounded-2xl space-y-3 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-grow">
                        <span class="font-extrabold text-[10px] text-slate-800 block truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                        <span class="text-[8px] text-slate-400 block truncate" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                    </div>
                    <!-- Role badge -->
                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-lg flex-shrink-0
                        @if(auth()->user()->role === 'admin') bg-red-100 text-red-800 border border-red-200
                        @elseif(auth()->user()->role === 'chef') bg-emerald-100 text-emerald-800 border border-emerald-200
                        @else bg-blue-100 text-blue-800 border border-blue-200 @endif">
                        @if(auth()->user()->role === 'admin') المدير
                        @elseif(auth()->user()->role === 'chef') الطاهي
                        @else الكاشير @endif
                    </span>
                </div>

                <form action="/logout" method="POST" class="w-full">
                    @csrf
                    <button type="submit" class="w-full bg-red-50 hover:bg-red-600 text-red-650 hover:text-white border border-red-100 hover:border-red-600 py-2 rounded-xl text-[10px] font-bold transition-all duration-300 flex items-center justify-center gap-1.5 shadow-sm">
                        <span>🚪</span> LOGOUT (تسجيل الخروج)
                    </button>
                </form>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[10px] text-slate-700 font-bold uppercase tracking-wider">سيرفر الفرع المحلي</span>
                <span class="text-[9px] text-slate-400 font-medium">عقدة طرابلس، ليبيا</span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50 animate-pulse"></div>
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
         class="fixed inset-0 bg-slate-900/40 z-40 lg:hidden"
         style="display: none;">
    </div>

    <!-- Floating Mobile Menu Button -->
    <button @click="isOpen = !isOpen" 
            class="fixed bottom-6 right-6 z-50 p-4 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-full shadow-2xl lg:hidden focus:outline-none flex items-center justify-center font-bold text-xl transition-all duration-300 w-14 h-14"
            aria-label="Toggle Navigation Menu">
        <!-- Burger / Close Icon -->
        <span x-show="!isOpen">☰</span>
        <span x-show="isOpen" style="display: none;">✕</span>
    </button>
</div>

<script>
    function sidebarNotificationApp() {
        return {
            isOpen: false,
            lowStockCount: 0,
            notificationPermission: 'default',

            init() {
                if ('Notification' in window) {
                    this.notificationPermission = Notification.permission;
                }
                
                // Perform initial check
                this.checkLowStock();
                
                // Poll stock alerts every 30 seconds
                setInterval(() => {
                    this.checkLowStock();
                }, 30000);
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
