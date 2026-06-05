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
    <title>المدينة KDS - شاشة عرض المطبخ</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@350;400;650;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        /* Grid height adjustment */
        .kds-grid {
            height: calc(100vh - 80px);
        }
        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
    </style>
</head>
<body class="h-screen overflow-hidden flex relative page-animate" x-data="kdsApp()">

    <!-- Decorative Glow Circles -->
    <div class="absolute top-10 right-10 w-[500px] h-[500px] bg-amber-500/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-10 left-10 w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- Unified left navigation sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-grow flex flex-col overflow-hidden h-screen relative z-10">

        <!-- Top Header -->
        <header class="glass-header px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-3 flex-shrink-0 text-right">
            <!-- Mobile Header Row -->
            <div class="flex items-center justify-between w-full md:w-auto">
                <div class="flex items-center gap-3">
                    <!-- Mobile Sidebar Toggle -->
                    <button @click="$dispatch('toggle-sidebar')" class="lg:hidden p-2 text-slate-700 hover:text-slate-900 focus:outline-none text-xl leading-none">
                        ☰
                    </button>
                    <div class="w-10 h-10 rounded-[16px] bg-gradient-to-tr from-amber-500 via-orange-500 to-red-500 flex items-center justify-center text-xl shadow-md shadow-orange-550/10">
                        🍳
                    </div>
                    <div>
                        <h1 class="text-sm font-black leading-none text-slate-900">شاشة عرض المطبخ (KDS)</h1>
                        <span class="text-[9px] text-amber-600 font-extrabold uppercase tracking-wider block mt-0.5">طابور الطهي والتحضير للوجبات</span>
                    </div>
                </div>
                <!-- Compact refresh button for mobile -->
                <button @click="window.location.reload()" class="md:hidden p-2 text-slate-600 hover:text-slate-800 focus:outline-none text-sm font-bold">
                    🔄
                </button>
            </div>

            <!-- Actions row -->
            <div class="flex items-center gap-2 w-full md:w-auto justify-between md:justify-end">
                <!-- Active order counter -->
                <div class="text-[10px] md:text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3.5 py-2 rounded-2xl text-slate-700 dark:text-slate-350 shadow-sm font-bold flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>قيد العمليات: <span class="font-black text-amber-600 dark:text-amber-400" x-text="orders.length"></span></span>
                </div>
                
                <div class="flex items-center gap-2">
                    <!-- Sound Toggle Widget -->
                    <button @click="soundEnabled = !soundEnabled; localStorage.setItem('soundEnabled', soundEnabled)" 
                            class="bg-white hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-250 font-black text-xs px-4 py-2.5 rounded-2xl flex items-center justify-center gap-2 transition-all shadow-sm">
                        <span x-text="soundEnabled ? '🔊' : '🔇'"></span>
                        <span class="hidden sm:inline" x-text="soundEnabled ? 'تنبيه الجرس: تفعيل' : 'تنبيه الجرس: كتم'"></span>
                    </button>
                    <button @click="window.location.reload()" class="hidden md:block bg-white hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-xs font-black px-4 py-2.5 rounded-2xl text-slate-700 dark:text-slate-250 transition-colors shadow-sm">
                        تحديث القائمة
                    </button>
                    <a href="/pos" class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-slate-950 text-[10px] md:text-xs font-black px-4 py-2.5 md:px-5 md:py-3 rounded-2xl transition-all shadow-lg shadow-orange-550/15 hover:shadow-orange-550/25 active:scale-95">
              <!-- KDS Kanban Board Area -->
        <main class="flex-grow p-6 overflow-hidden flex flex-col lg:flex-row gap-6 h-full min-h-0" dir="rtl" x-data="{ draggedOrderId: null, dragOverColumn: null }">
            
            <!-- Lane 1: Cooking / Preparation -->
            <div class="flex-1 flex flex-col bg-slate-100/60 dark:bg-slate-900/40 rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-4 h-full overflow-hidden transition-all duration-300"
                 @dragover.prevent="dragOverColumn = 1"
                 @dragleave="dragOverColumn = null"
                 @drop="handleDrop('cooking'); dragOverColumn = null"
                 :class="dragOverColumn === 1 ? 'ring-4 ring-amber-500/20 border-amber-500/40 bg-amber-500/[0.02]' : ''">
                <div class="flex justify-between items-center mb-4 px-2">
                    <h2 class="font-black text-xs text-slate-850 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500 shadow-md shadow-orange-500/20"></span>
                        تحت التحضير (Cooking)
                    </h2>
                    <span class="bg-amber-500/10 text-amber-600 dark:text-amber-400 font-extrabold text-[10px] px-2.5 py-1 rounded-full border border-amber-500/10" 
                          x-text="orders.filter(o => o.status === 'pending' || o.status === 'cooking').length"></span>
                </div>
                <div class="flex-grow overflow-y-auto space-y-4 pr-1 pl-1 min-h-0">
                    <template x-for="order in orders.filter(o => o.status === 'pending' || o.status === 'cooking')" :key="order.id">
                        <div x-data="{ checkedItems: [] }"
                             draggable="true"
                             @dragstart="draggedOrderId = order.id"
                             class="bg-white/90 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-[28px] flex flex-col justify-between overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 card-animate cursor-grab active:cursor-grabbing"
                             :class="getBorderClass(order)">
                            
                            <!-- Top time indicator strip -->
                            <div class="h-2 w-full" :class="getStripClass(order)"></div>

                            <!-- Card Header -->
                            <div class="p-4 border-b flex justify-between items-start text-right border-slate-200 dark:border-slate-800">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-black text-slate-800 dark:text-slate-105" x-text="'#' + order.id.substring(0, 8).toUpperCase()"></span>
                                        <span class="text-[8px] px-2 py-0.5 rounded-lg font-black uppercase tracking-wider border border-amber-250 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-550/10 text-amber-700 dark:text-amber-400"
                                              x-text="order.status === 'cooking' ? 'تحت التحضير' : 'قيد الانتظار'"></span>
                                        <span class="text-[8px] px-2 py-0.5 rounded-lg font-black uppercase tracking-wider border flex items-center gap-1"
                                              :class="getOrderTypeBadgeClass(order.notes)">
                                            <span x-text="getOrderTypeIcon(order.notes)"></span>
                                            <span x-text="getOrderType(order.notes)"></span>
                                        </span>
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-extrabold block mt-1" x-text="'الفرع: ' + order.location.name"></span>
                                </div>
                                
                                <!-- Cooking Timer Display -->
                                <div class="text-left" dir="ltr">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs">⏱️</span>
                                        <span class="font-mono font-black text-xs block tracking-wider" :class="getElapsedSeconds(order) > 600 ? 'text-rose-600 animate-pulse' : (getElapsedSeconds(order) > 300 ? 'text-amber-600' : 'text-emerald-600')" x-text="getElapsedTime(order)"></span>
                                    </div>
                                    <span class="text-[7px] text-slate-450 dark:text-slate-500 uppercase tracking-widest font-black block text-left mt-0.5">الوقت المنقضي</span>
                                </div>
                            </div>

                            <!-- Card Body: Interactive Item Checklist -->
                            <div class="flex-grow p-4 bg-slate-50/50 dark:bg-slate-900/20 space-y-2.5">
                                <template x-for="item in order.items" :key="item.id">
                                    <div @click="checkedItems.includes(item.id) ? checkedItems = checkedItems.filter(id => id !== item.id) : checkedItems.push(item.id)"
                                         class="flex justify-between items-center text-xs cursor-pointer bg-white dark:bg-slate-950 hover:bg-slate-100/60 dark:hover:bg-slate-900/60 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-slate-350 dark:hover:border-slate-700 transition-all group shadow-sm" dir="rtl">
                                        <div class="min-w-0 flex items-center gap-2.5">
                                            <span class="w-5 h-5 rounded-lg border flex items-center justify-center text-[10px] transition-all"
                                                  :class="checkedItems.includes(item.id) ? 'bg-emerald-500 border-emerald-500 text-white font-black shadow-sm' : 'border-slate-300 dark:border-slate-750 bg-white dark:bg-slate-950 group-hover:border-slate-400 text-transparent'">
                                                ✓
                                            </span>
                                            <span class="font-black text-amber-655" x-text="item.quantity + 'x'"></span>
                                            <span class="text-slate-700 dark:text-slate-300 font-bold transition-all mr-1" :class="checkedItems.includes(item.id) ? 'line-through text-slate-400 dark:text-slate-500 font-normal' : 'group-hover:text-amber-650'" x-text="item.product.name"></span>
                                        </div>
                                    </div>
                                </template>

                                <!-- Order Notes Display -->
                                <template x-if="cleanNotes(order.notes)">
                                    <div class="mt-3 p-3 bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/25 dark:border-amber-500/30 text-amber-850 dark:text-amber-400 rounded-2xl text-xs space-y-1 font-semibold text-right">
                                        <div class="text-[8px] uppercase tracking-wider text-amber-700 dark:text-amber-555 font-black flex items-center gap-1">
                                            <span>📝</span> ملاحظات خاصة بالتحضير
                                        </div>
                                        <div class="text-slate-855 dark:text-slate-200 mt-1 font-bold text-[10px]" x-text="cleanNotes(order.notes)"></div>
                                    </div>
                                </template>
                            </div>

                            <!-- Card Action Footer -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-900/60 border-t flex gap-2 border-slate-200 dark:border-slate-800" :class="getBorderClass(order)">
                                <!-- Transition pending to cooking -->
                                <button x-show="order.status === 'pending'" @click="updateStatus(order, 'cooking')"
                                        class="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-slate-950 font-black text-xs py-3 rounded-xl transition-all shadow-md shadow-orange-550/15">
                                    بدء الطهي / التحضير
                                </button>
                                <!-- Transition cooking to ready -->
                                <button x-show="order.status === 'cooking'" @click="updateStatus(order, 'ready')"
                                        class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-black text-xs py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10">
                                    تجهيز الوجبة (جاهز)
                                </button>
                            </div>
                        </div>
                    </template>
                    <template x-if="orders.filter(o => o.status === 'pending' || o.status === 'cooking').length === 0">
                        <div class="h-40 flex flex-col items-center justify-center text-slate-400 dark:text-slate-655 gap-2 py-12">
                            <span class="text-3xl">🎉</span>
                            <span class="text-[10px] font-black">لا يوجد طلبات تحت التحضير</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Lane 2: Ready for Pickup -->
            <div class="flex-1 flex flex-col bg-slate-100/60 dark:bg-slate-900/40 rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-4 h-full overflow-hidden transition-all duration-300"
                 @dragover.prevent="dragOverColumn = 2"
                 @dragleave="dragOverColumn = null"
                 @drop="handleDrop('ready'); dragOverColumn = null"
                 :class="dragOverColumn === 2 ? 'ring-4 ring-emerald-500/20 border-emerald-500/40 bg-emerald-500/[0.02]' : ''">
                <div class="flex justify-between items-center mb-4 px-2">
                    <h2 class="font-black text-xs text-slate-850 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 shadow-md shadow-emerald-500/20 animate-pulse"></span>
                        جاهز للتسليم (Ready)
                    </h2>
                    <span class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-extrabold text-[10px] px-2.5 py-1 rounded-full border border-emerald-500/10" 
                          x-text="orders.filter(o => o.status === 'ready').length"></span>
                </div>
                <div class="flex-grow overflow-y-auto space-y-4 pr-1 pl-1 min-h-0">
                    <template x-for="order in orders.filter(o => o.status === 'ready')" :key="order.id">
                        <div x-data="{ checkedItems: [] }"
                             draggable="true"
                             @dragstart="draggedOrderId = order.id"
                             class="bg-white/90 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-[28px] flex flex-col justify-between overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 card-animate cursor-grab active:cursor-grabbing"
                             :class="getBorderClass(order)">
                            
                            <!-- Top time indicator strip -->
                            <div class="h-2 w-full" :class="getStripClass(order)"></div>

                            <!-- Card Header -->
                            <div class="p-4 border-b flex justify-between items-start text-right border-slate-200 dark:border-slate-800">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-black text-slate-800 dark:text-slate-105" x-text="'#' + order.id.substring(0, 8).toUpperCase()"></span>
                                        <span class="text-[8px] px-2 py-0.5 rounded-lg font-black uppercase tracking-wider border border-emerald-250 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-555/10 text-emerald-700 dark:text-emerald-400">جاهز للتسليم</span>
                                        <span class="text-[8px] px-2 py-0.5 rounded-lg font-black uppercase tracking-wider border flex items-center gap-1"
                                              :class="getOrderTypeBadgeClass(order.notes)">
                                            <span x-text="getOrderTypeIcon(order.notes)"></span>
                                            <span x-text="getOrderType(order.notes)"></span>
                                        </span>
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-extrabold block mt-1" x-text="'الفرع: ' + order.location.name"></span>
                                </div>
                                
                                <!-- Cooking Timer Display -->
                                <div class="text-left" dir="ltr">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs">⏱️</span>
                                        <span class="font-mono font-black text-xs block tracking-wider" :class="getElapsedSeconds(order) > 600 ? 'text-rose-600 animate-pulse' : (getElapsedSeconds(order) > 300 ? 'text-amber-600' : 'text-emerald-600')" x-text="getElapsedTime(order)"></span>
                                    </div>
                                    <span class="text-[7px] text-slate-450 dark:text-slate-500 uppercase tracking-widest font-black block text-left mt-0.5">الوقت المنقضي</span>
                                </div>
                            </div>

                            <!-- Card Body: Items List -->
                            <div class="flex-grow p-4 bg-slate-50/50 dark:bg-slate-900/20 space-y-2.5">
                                <template x-for="item in order.items" :key="item.id">
                                    <div class="flex justify-between items-center text-xs bg-white dark:bg-slate-950 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm" dir="rtl">
                                        <div class="min-w-0 flex items-center gap-2.5">
                                            <span class="font-black text-emerald-650">✓</span>
                                            <span class="font-black text-amber-650" x-text="item.quantity + 'x'"></span>
                                            <span class="text-slate-700 dark:text-slate-350 font-bold transition-all mr-1" x-text="item.product.name"></span>
                                        </div>
                                    </div>
                                </template>

                                <!-- Order Notes Display -->
                                <template x-if="cleanNotes(order.notes)">
                                    <div class="mt-3 p-3 bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/25 dark:border-amber-500/30 text-amber-850 dark:text-amber-400 rounded-2xl text-xs space-y-1 font-semibold text-right">
                                        <div class="text-[8px] uppercase tracking-wider text-amber-700 dark:text-amber-500 font-black flex items-center gap-1">
                                            <span>📝</span> ملاحظات خاصة بالتحضير
                                        </div>
                                        <div class="text-slate-850 dark:text-slate-200 mt-1 font-bold text-[10px]" x-text="cleanNotes(order.notes)"></div>
                                    </div>
                                </template>
                            </div>

                            <!-- Card Action Footer -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-900/60 border-t flex gap-2 border-slate-200 dark:border-slate-800" :class="getBorderClass(order)">
                                <!-- Transition ready to completed -->
                                <button @click="updateStatus(order, 'completed')"
                                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-xs py-3 rounded-xl transition-all shadow-md shadow-blue-500/10">
                                    تسليم وإتمام الطلب (Serve)
                                </button>
                            </div>
                        </div>
                    </template>
                    <template x-if="orders.filter(o => o.status === 'ready').length === 0">
                        <div class="h-40 flex flex-col items-center justify-center text-slate-400 dark:text-slate-655 gap-2 py-12">
                            <span class="text-3xl">🕒</span>
                            <span class="text-[10px] font-black">لا يوجد وجبات جاهزة للتسليم</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Lane 3: Served / Completed (Session History) -->
            <div class="flex-1 flex flex-col bg-slate-100/60 dark:bg-slate-900/40 rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-4 h-full overflow-hidden transition-all duration-300"
                 @dragover.prevent="dragOverColumn = 3"
                 @dragleave="dragOverColumn = null"
                 @drop="handleDrop('completed'); dragOverColumn = null"
                 :class="dragOverColumn === 3 ? 'ring-4 ring-blue-500/20 border-blue-500/40 bg-blue-500/[0.02]' : ''">
                <div class="flex justify-between items-center mb-4 px-2">
                    <h2 class="font-black text-xs text-slate-850 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500 shadow-md shadow-blue-500/20"></span>
                        تم التسليم (Served)
                    </h2>
                    <span class="bg-blue-500/10 text-blue-600 dark:text-blue-400 font-extrabold text-[10px] px-2.5 py-1 rounded-full border border-blue-500/10" 
                          x-text="completedOrders.length"></span>
                </div>
                <div class="flex-grow overflow-y-auto space-y-4 pr-1 pl-1 min-h-0">
                    <template x-for="order in completedOrders" :key="order.id">
                        <div draggable="true"
                             @dragstart="draggedOrderId = order.id"
                             class="bg-white/90 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-850 rounded-[28px] flex flex-col justify-between overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 card-animate border-slate-200 dark:border-slate-800 opacity-75 cursor-grab active:cursor-grabbing">
                            
                            <!-- Card Header -->
                            <div class="p-4 border-b flex justify-between items-start text-right border-slate-100 dark:border-slate-800">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-black text-slate-505 dark:text-slate-400 line-through" x-text="'#' + order.id.substring(0, 8).toUpperCase()"></span>
                                        <span class="text-[8px] px-2 py-0.5 rounded-lg font-black uppercase tracking-wider border border-blue-100 dark:border-blue-550/20 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">تم التسليم</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 mt-1 block" x-text="'تم التسليم: ' + new Date(order.completed_at).toLocaleTimeString('ar-LY')"></span>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 bg-slate-50/30 dark:bg-slate-900/10 space-y-2">
                                <template x-for="item in order.items" :key="item.id">
                                    <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-slate-400">
                                        <span class="font-bold line-through" x-text="item.product.name"></span>
                                        <span class="font-bold" x-text="item.quantity + 'x'"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Card Footer: Revert Button -->
                            <div class="p-3 bg-slate-50/50 dark:bg-slate-900/60 border-t flex gap-2 border-slate-100 dark:border-slate-800">
                                <button @click="updateStatus(order, 'ready')"
                                        class="w-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-250 font-black text-[10px] py-2.5 rounded-xl transition-all border border-slate-200 dark:border-slate-700">
                                    ↺ إرجاع لقائمة الجاهز
                                </button>
                            </div>
                        </div>
                    </template>
                    <template x-if="completedOrders.length === 0">
                        <div class="h-40 flex flex-col items-center justify-center text-slate-400 dark:text-slate-655 gap-2 py-12">
                            <span class="text-3xl">🍽️</span>
                            <span class="text-[10px] font-black text-center px-4">لم يتم تسليم أي وجبة في هذه الجلسة</span>
                        </div>
                    </template>
                </div>
            </div>

        </main>

        <!-- Script Block for KDS Polling and Timers -->
        <script>
            function kdsApp() {
                return {
                    orders: @json($orders),
                    completedOrders: [],
                    currentTime: Date.now(),
                    soundEnabled: localStorage.getItem('soundEnabled') !== 'false',
                    
                    init() {
                        // Update current timestamp every second to drive reactive timers
                        setInterval(() => {
                            this.currentTime = Date.now();
                        }, 1000);

                        // Polling for new orders every 10 seconds
                        setInterval(() => {
                            this.pollNewOrders();
                        }, 10000);
                    },

                    playDoubleChime() {
                        if (!this.soundEnabled) return;
                        try {
                            const AudioContext = window.AudioContext || window.webkitAudioContext;
                            if (!AudioContext) return;
                            const ctx = new AudioContext();
                            
                            const playChime = (freq, time, duration) => {
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(freq, time);
                                
                                gain.gain.setValueAtTime(0.15, time);
                                gain.gain.exponentialRampToValueAtTime(0.001, time + duration);
                                
                                osc.start(time);
                                osc.stop(time + duration);
                            };
                            
                            const now = ctx.currentTime;
                            // High double chime ding: E6 (1318.51Hz) and G6 (1567.98Hz)
                            playChime(1318.51, now, 0.4);
                            playChime(1567.98, now + 0.15, 0.5);
                        } catch (e) {
                            console.error("Chime synthesis failed", e);
                        }
                    },

                    pollNewOrders() {
                        fetch('/kds')
                            .then(res => res.text())
                            .then(html => {
                                // Simple parsing of new orders json embedded in page from backend
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const scriptEl = doc.querySelector('script');
                                if (scriptEl) {
                                    // Match JSON signature
                                    const match = html.match(/orders":\s*(\[.*?\]),/s);
                                    if (match && match[1]) {
                                        try {
                                            const newOrders = JSON.parse(match[1]);
                                            
                                            // Check for new orders to trigger chime sound
                                            const newIds = newOrders.map(o => o.id);
                                            const oldIds = this.orders.map(o => o.id);
                                            const hasNew = newIds.some(id => !oldIds.includes(id));
                                            
                                            if (hasNew && oldIds.length > 0) {
                                                this.playDoubleChime();
                                            }
                                            
                                            this.orders = newOrders;
                                        } catch (e) {
                                            console.error("KDS refresh parsing failed", e);
                                        }
                                    }
                                }
                            });
                    },

                    handleDrop(targetStatus) {
                        if (!this.draggedOrderId) return;
                        
                        let order = this.orders.find(o => o.id === this.draggedOrderId);
                        if (!order) {
                            order = this.completedOrders.find(o => o.id === this.draggedOrderId);
                        }
                        
                        if (order) {
                            if (order.status !== targetStatus) {
                                this.updateStatus(order, targetStatus);
                            }
                        }
                        this.draggedOrderId = null;
                    },

                    getElapsedSeconds(order) {
                        const created = new Date(order.created_at).getTime();
                        return Math.max(0, Math.floor((this.currentTime - created) / 1000));
                    },

                    getElapsedTime(order) {
                        const secs = this.getElapsedSeconds(order);
                        const m = Math.floor(secs / 60);
                        const s = secs % 60;
                        return `${m} د ${s} ث`;
                    },

                    getStripClass(order) {
                        const secs = this.getElapsedSeconds(order);
                        if (secs < 300) {
                            return 'bg-gradient-to-r from-emerald-500 to-teal-500';
                        } else if (secs < 600) {
                            return 'bg-gradient-to-r from-amber-500 to-orange-500';
                        } else {
                            return 'bg-gradient-to-r from-rose-500 to-red-500 animate-pulse';
                        }
                    },

                    getBorderClass(order) {
                        const secs = this.getElapsedSeconds(order);
                        if (secs < 300) {
                            return 'border-slate-200 dark:border-slate-800';
                        } else if (secs < 600) {
                            return 'border-amber-500/30 shadow-[0_0_15px_rgba(245,158,11,0.05)]';
                        } else {
                            return 'border-rose-500/40 shadow-[0_0_20px_rgba(244,63,94,0.1)]';
                        }
                    },

                    updateStatus(order, newStatus) {
                        fetch(`/kds/orders/${order.id}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                if (newStatus === 'completed') {
                                    // Remove from orders and add to completedOrders locally
                                    order.status = 'completed';
                                    order.completed_at = new Date().toISOString();
                                    this.orders = this.orders.filter(o => o.id !== order.id);
                                    // Add to completed list (limit to 20 for memory)
                                    this.completedOrders = [order, ...this.completedOrders.slice(0, 19)];
                                } else if (newStatus === 'ready' && order.status === 'completed') {
                                    // Revert completed back to ready
                                    order.status = 'ready';
                                    this.completedOrders = this.completedOrders.filter(o => o.id !== order.id);
                                    this.orders = [...this.orders, order];
                                } else {
                                    // Update status locally
                                    order.status = newStatus;
                                }
                            }
                        });
                    },

                    getOrderType(notes) {
                        if (!notes) return 'سفري';
                        if (notes.includes('[محلي]')) return 'محلي';
                        if (notes.includes('[توصيل]')) return 'توصيل';
                        return 'سفري';
                    },

                    cleanNotes(notes) {
                        if (!notes) return '';
                        return notes.replace(/\[(محلي|سفري|توصيل)\]\s*/g, '');
                    },

                    getOrderTypeBadgeClass(notes) {
                        const type = this.getOrderType(notes);
                        if (type === 'محلي') return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
                        if (type === 'توصيل') return 'bg-rose-500/10 text-rose-550 border-rose-500/20';
                        return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
                    },

                    getOrderTypeIcon(notes) {
                        const type = this.getOrderType(notes);
                        if (type === 'محلي') return '🛋️';
                        if (type === 'توصيل') return '🚗';
                        return '🛍️';
                    }
                };
            }
        </script>
    </div>
</body>
</html>
