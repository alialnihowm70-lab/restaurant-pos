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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>alnihowm | Bello Smash - لوحة الطلبات النشطة</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Compiled Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="manifest" href="/manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
    <style>
        body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
        }
        .active-lane::-webkit-scrollbar {
            width: 4px;
        }
        .active-lane::-webkit-scrollbar-track {
            background: transparent;
        }
        .active-lane::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.25);
            border-radius: 99px;
        }
        .dark .active-lane::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.15);
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-animate {
            animation: pageFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="min-h-screen flex bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 relative overflow-hidden page-animate" x-data="activeOrdersApp()">

    <!-- Shared Navigation Sidebar -->
    @include('partials.sidebar')

    <!-- Main Workspace -->
    <div class="flex-grow flex flex-col h-screen overflow-hidden relative">
        
        <!-- Header Bar -->
        <header class="relative bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 px-5 py-3.5 flex flex-col lg:flex-row items-center justify-between gap-4 flex-shrink-0 z-20">
            <div class="flex items-center justify-between w-full lg:w-auto">
                <div class="flex items-center gap-3">
                    <button @click="$dispatch('toggle-sidebar')" class="lg:hidden w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-900 dark:hover:text-white rounded-lg transition-colors text-lg">☰</button>
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-lg shadow-lg shadow-orange-500/20 flex-shrink-0">⚡</div>
                    <div>
                        <h1 class="text-sm font-black text-slate-900 dark:text-white leading-none">تتبع الطلبات النشطة</h1>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block mt-1">المطبخ والصالة والسيارات والجاهز في الوقت الحقيقي</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="bg-amber-500/10 border border-amber-500/20 px-4 py-2 rounded-xl">
                        <span class="text-[11px] text-amber-600 dark:text-amber-400 font-black">
                            الطلبات النشطة: <span x-text="orders.length"></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Controls (Search + Printer IP) -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto justify-end">
                <div class="relative w-full sm:w-64">
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs">🔍</span>
                    <input type="text" x-model="searchQuery" placeholder="البحث برقم الفاتورة أو الملاحظات..." 
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-850 focus:border-amber-500 rounded-xl px-9 py-2 text-xs text-slate-800 dark:text-slate-200 focus:outline-none transition-all shadow-sm" />
                </div>

                <div class="hidden sm:flex items-center gap-2 bg-slate-150 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3.5 py-2 rounded-xl">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-black uppercase tracking-wider whitespace-nowrap">🖨️ IP:</span>
                    <input type="text" x-model="printerIp" placeholder="192.168.1.100"
                           class="w-28 bg-transparent text-xs font-mono text-slate-800 dark:text-slate-200 focus:outline-none text-center" dir="ltr" />
                </div>
            </div>
        </header>

        <!-- Lanes Board (Kanban Style) -->
        <main class="flex-grow p-4 lg:p-5 overflow-hidden flex flex-col md:flex-row gap-4 h-full min-h-0 text-right" dir="rtl">
            
            <!-- Lane 1: Pending (معلقة) -->
            <div class="flex-1 flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-850 rounded-[28px] overflow-hidden shadow-sm">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-950/20">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <h2 class="text-xs font-black text-slate-800 dark:text-slate-250">⏳ طلبات في الانتظار (معلقة)</h2>
                    </div>
                    <span class="bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 text-[10px] font-black px-2.5 py-1 rounded-lg" x-text="getFilteredOrdersByStatus('pending').length + ' طلب'"></span>
                </div>
                <!-- Scrollable Content -->
                <div class="active-lane flex-grow overflow-y-auto p-4 space-y-4">
                    <template x-for="order in getFilteredOrdersByStatus('pending')" :key="order.id">
                        <div class="bg-slate-50 dark:bg-slate-950 border border-slate-150 dark:border-slate-800/80 rounded-[22px] p-4.5 hover:border-amber-500/30 transition-all space-y-3 shadow-sm relative group">
                            <!-- Header Row -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-mono font-black text-[11px] text-slate-800 dark:text-slate-200" x-text="order.invoice_number || '#' + order.id.substring(0,8).toUpperCase()"></span>
                                    <div class="flex gap-1.5 mt-1 items-center flex-wrap">
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-md" :class="getOrderTypeBadgeClass(order.notes)">
                                            <span x-text="getOrderTypeIcon(order.notes)"></span>
                                            <span x-text="getOrderType(order.notes)"></span>
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-450 dark:text-slate-500" x-text="'📍 ' + order.location.name"></span>
                                    </div>
                                </div>
                                <span class="font-mono text-[9px] font-black text-slate-400" x-text="getElapsedTime(order)"></span>
                            </div>
                            <!-- Items Row -->
                            <div class="space-y-1 text-slate-650 dark:text-slate-350 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-2.5 rounded-xl text-[11px]">
                                <template x-for="item in order.items" :key="item.id">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-slate-700 dark:text-slate-300" x-text="item.product ? item.product.name : 'صنف غير معروف'"></span>
                                        <span class="font-black text-amber-600 dark:text-amber-400" x-text="item.quantity + 'x'"></span>
                                    </div>
                                </template>
                                <template x-if="cleanNotes(order.notes)">
                                    <div class="mt-2 text-[9px] text-rose-500 dark:text-rose-400 font-extrabold border-t border-slate-100 dark:border-slate-800/80 pt-1.5" x-text="'📝 ملاحظة: ' + cleanNotes(order.notes)"></div>
                                </template>
                            </div>
                            <!-- Actions Footer -->
                            <div class="flex justify-between items-center pt-2">
                                <span class="font-black text-slate-800 dark:text-slate-200 text-xs" x-text="parseFloat(order.total_amount).toFixed(2) + ' د.ل'"></span>
                                <div class="flex items-center gap-1.5">
                                    <button @click="cancelOrder(order.id)" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 text-[10px] font-black px-2.5 py-1.5 rounded-xl transition-all shadow-sm">إلغاء</button>
                                    <button @click="updateStatus(order.id, 'cooking')" class="bg-amber-500 hover:bg-amber-600 text-slate-950 text-[10px] font-black px-3.5 py-1.5 rounded-xl transition-all shadow shadow-amber-500/10">🔥 بدء التحضير</button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="getFilteredOrdersByStatus('pending').length === 0">
                        <div class="h-44 flex flex-col items-center justify-center text-slate-400 gap-2 opacity-50">
                            <span class="text-3xl">☕</span>
                            <span class="text-[10px] font-black">لا يوجد طلبات بالانتظار</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Lane 2: Cooking (قيد التحضير) -->
            <div class="flex-1 flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-850 rounded-[28px] overflow-hidden shadow-sm">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-950/20">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse"></span>
                        <h2 class="text-xs font-black text-slate-800 dark:text-slate-250">🔥 طلبات قيد التحضير بالمطبخ</h2>
                    </div>
                    <span class="bg-orange-100 dark:bg-orange-500/15 text-orange-700 dark:text-orange-400 text-[10px] font-black px-2.5 py-1 rounded-lg" x-text="getFilteredOrdersByStatus('cooking').length + ' طلب'"></span>
                </div>
                <!-- Scrollable Content -->
                <div class="active-lane flex-grow overflow-y-auto p-4 space-y-4">
                    <template x-for="order in getFilteredOrdersByStatus('cooking')" :key="order.id">
                        <div class="bg-slate-50 dark:bg-slate-950 border border-slate-150 dark:border-slate-800/80 rounded-[22px] p-4.5 hover:border-orange-500/30 transition-all space-y-3 shadow-sm relative group">
                            <!-- Header Row -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-mono font-black text-[11px] text-slate-800 dark:text-slate-200" x-text="order.invoice_number || '#' + order.id.substring(0,8).toUpperCase()"></span>
                                    <div class="flex gap-1.5 mt-1 items-center flex-wrap">
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-md" :class="getOrderTypeBadgeClass(order.notes)">
                                            <span x-text="getOrderTypeIcon(order.notes)"></span>
                                            <span x-text="getOrderType(order.notes)"></span>
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-450 dark:text-slate-500" x-text="'📍 ' + order.location.name"></span>
                                    </div>
                                </div>
                                <span class="font-mono text-[9px] font-black text-slate-450" x-text="getElapsedTime(order)"></span>
                            </div>
                            <!-- Items Row -->
                            <div class="space-y-1 text-slate-650 dark:text-slate-350 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-2.5 rounded-xl text-[11px]">
                                <template x-for="item in order.items" :key="item.id">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-slate-700 dark:text-slate-300" x-text="item.product ? item.product.name : 'صنف غير معروف'"></span>
                                        <span class="font-black text-amber-600 dark:text-amber-400" x-text="item.quantity + 'x'"></span>
                                    </div>
                                </template>
                                <template x-if="cleanNotes(order.notes)">
                                    <div class="mt-2 text-[9px] text-rose-500 dark:text-rose-400 font-extrabold border-t border-slate-100 dark:border-slate-800/80 pt-1.5" x-text="'📝 ملاحظة: ' + cleanNotes(order.notes)"></div>
                                </template>
                            </div>
                            <!-- Actions Footer -->
                            <div class="flex justify-between items-center pt-2">
                                <span class="font-black text-slate-800 dark:text-slate-200 text-xs" x-text="parseFloat(order.total_amount).toFixed(2) + ' د.ل'"></span>
                                <div class="flex items-center gap-1.5">
                                    <button @click="cancelOrder(order.id)" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 text-[10px] font-black px-2.5 py-1.5 rounded-xl transition-all shadow-sm">إلغاء</button>
                                    <button @click="updateStatus(order.id, 'ready')" class="bg-indigo-650 hover:bg-indigo-700 text-white text-[10px] font-black px-3.5 py-1.5 rounded-xl transition-all shadow shadow-indigo-500/10">🟢 جاهز للتسليم</button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="getFilteredOrdersByStatus('cooking').length === 0">
                        <div class="h-44 flex flex-col items-center justify-center text-slate-400 gap-2 opacity-50">
                            <span class="text-3xl">👨‍🍳</span>
                            <span class="text-[10px] font-black">لا يوجد وجبات قيد التحضير</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Lane 3: Ready (جاهزة) -->
            <div class="flex-1 flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-850 rounded-[28px] overflow-hidden shadow-sm">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-950/20">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)] animate-pulse"></span>
                        <h2 class="text-xs font-black text-slate-800 dark:text-slate-250">🟢 طلبات جاهزة (للتسليم)</h2>
                    </div>
                    <span class="bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 text-[10px] font-black px-2.5 py-1 rounded-lg" x-text="getFilteredOrdersByStatus('ready').length + ' طلب'"></span>
                </div>
                <!-- Scrollable Content -->
                <div class="active-lane flex-grow overflow-y-auto p-4 space-y-4">
                    <template x-for="order in getFilteredOrdersByStatus('ready')" :key="order.id">
                        <div class="bg-slate-50 dark:bg-slate-950 border border-slate-150 dark:border-slate-800/80 rounded-[22px] p-4.5 hover:border-emerald-500/30 transition-all space-y-3 shadow-sm relative group">
                            <!-- Header Row -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-mono font-black text-[11px] text-slate-800 dark:text-slate-200" x-text="order.invoice_number || '#' + order.id.substring(0,8).toUpperCase()"></span>
                                    <div class="flex gap-1.5 mt-1 items-center flex-wrap">
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-md" :class="getOrderTypeBadgeClass(order.notes)">
                                            <span x-text="getOrderTypeIcon(order.notes)"></span>
                                            <span x-text="getOrderType(order.notes)"></span>
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-450 dark:text-slate-500" x-text="'📍 ' + order.location.name"></span>
                                    </div>
                                </div>
                                <span class="font-mono text-[9px] font-black text-emerald-600 dark:text-emerald-400" x-text="getElapsedTime(order)"></span>
                            </div>
                            <!-- Items Row -->
                            <div class="space-y-1 text-slate-650 dark:text-slate-350 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-2.5 rounded-xl text-[11px]">
                                <template x-for="item in order.items" :key="item.id">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-slate-700 dark:text-slate-300" x-text="item.product ? item.product.name : 'صنف غير معروف'"></span>
                                        <span class="font-black text-amber-600 dark:text-amber-400" x-text="item.quantity + 'x'"></span>
                                    </div>
                                </template>
                                <template x-if="cleanNotes(order.notes)">
                                    <div class="mt-2 text-[9px] text-rose-500 dark:text-rose-400 font-extrabold border-t border-slate-100 dark:border-slate-800/80 pt-1.5" x-text="'📝 ملاحظة: ' + cleanNotes(order.notes)"></div>
                                </template>
                            </div>
                            <!-- Actions Footer -->
                            <div class="flex justify-between items-center pt-2">
                                <span class="font-black text-slate-800 dark:text-slate-200 text-xs" x-text="parseFloat(order.total_amount).toFixed(2) + ' د.ل'"></span>
                                <div class="flex items-center gap-1.5">
                                    <button @click="printReceiptDirectly(order.id)" class="bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-750 dark:text-slate-350 border border-slate-200 dark:border-slate-700 text-[10px] font-black px-2.5 py-1.5 rounded-xl transition-all shadow-sm">🖨️ طباعة</button>
                                    <button @click="updateStatus(order.id, 'completed')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black px-3.5 py-1.5 rounded-xl transition-all shadow shadow-emerald-500/10">🍽️ تسليم وإتمام</button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="getFilteredOrdersByStatus('ready').length === 0">
                        <div class="h-44 flex flex-col items-center justify-center text-slate-400 gap-2 opacity-50">
                            <span class="text-3xl">🛎️</span>
                            <span class="text-[10px] font-black">لا يوجد طلبات جاهزة للتسليم</span>
                        </div>
                    </template>
                </div>
            </div>

        </main>
    </div>

    <!-- Floating Toast Notifications -->
    <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[999] flex flex-col items-center gap-2 pointer-events-none" style="min-width:260px">
        <template x-for="toast in toasts" :key="toast.id">
            <div :class="toast.cls"
                 class="px-5 py-3 rounded-2xl text-xs font-black shadow-2xl flex items-center gap-2 animate-bounce pointer-events-auto"
                 style="animation: toastIn 0.3s cubic-bezier(0.16,1,0.3,1) both">
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>
    <style>
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(16px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>

    <!-- Active Orders Alpine Script -->
    <script>
        function activeOrdersApp() {
            return {
                orders: @json($orders),
                searchQuery: '',
                printerIp: localStorage.getItem('printerIp') || '',
                soundEnabled: localStorage.getItem('soundEnabled') !== 'false',
                currentTime: Date.now(),
                pollErrors: 0,
                toasts: [],

                init() {
                    this.$watch('printerIp', val => localStorage.setItem('printerIp', val));
                    setInterval(() => { this.currentTime = Date.now(); }, 1000);
                    // Adaptive polling: every 4s normally, slows to 30s after 5 consecutive errors
                    const poll = () => {
                        this.pollActiveOrders();
                        const delay = this.pollErrors >= 5 ? 30000 : 4000;
                        setTimeout(poll, delay);
                    };
                    setTimeout(poll, 4000);
                },

                getFilteredOrdersByStatus(status) {
                    return this.orders.filter(o => {
                        if (o.status !== status) return false;
                        if (!this.searchQuery) return true;
                        
                        const inv = (o.invoice_number || '').toLowerCase();
                        const id = o.id.toLowerCase();
                        const notes = (o.notes || '').toLowerCase();
                        const query = this.searchQuery.toLowerCase();
                        
                        return inv.includes(query) || id.includes(query) || notes.includes(query);
                    });
                },

                getElapsedSeconds(order) {
                    return Math.max(0, Math.floor((this.currentTime - new Date(order.created_at).getTime()) / 1000));
                },

                getElapsedTime(order) {
                    const s = this.getElapsedSeconds(order);
                    return `${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;
                },

                getOrderType(notes) {
                    if (!notes) return 'في سيارة';
                    if (notes.includes('[في المطعم') || notes.includes('[محلي')) return 'في المطعم';
                    if (notes.includes('[توصيل]')) return 'توصيل';
                    return 'في سيارة';
                },

                cleanNotes(notes) {
                    if (!notes) return '';
                    return notes.replace(/\[(محلي[^\]]*|في المطعم[^\]]*|سفري|في سيارة|توصيل)\]\s*/g, '').trim();
                },

                getOrderTypeBadgeClass(notes) {
                    const t = this.getOrderType(notes);
                    if (t === 'في المطعم') return 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20';
                    if (t === 'توصيل') return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20';
                    return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
                },

                getOrderTypeIcon(notes) {
                    const t = this.getOrderType(notes);
                    if (t === 'في المطعم') return '🛋️ ';
                    if (t === 'توصيل') return '🚗 ';
                    return '🛍️ ';
                },

                playChime() {
                    if (!this.soundEnabled) return;
                    try {
                        const AC = window.AudioContext || window.webkitAudioContext;
                        if (!AC) return;
                        const ctx = new AC();
                        const play = (freq, t, dur) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain); gain.connect(ctx.destination);
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(freq, t);
                            gain.gain.setValueAtTime(0.12, t);
                            gain.gain.exponentialRampToValueAtTime(0.001, t + dur);
                            osc.start(t); osc.stop(t + dur);
                        };
                        const now = ctx.currentTime;
                        play(660, now, 0.2);
                        play(880, now + 0.1, 0.3);
                    } catch(e) { console.error('Audio synthesis failed', e); }
                },

                // JSON polling — clean and reliable
                pollActiveOrders() {
                    fetch('/api/orders/active.json')
                        .then(r => {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            return r.json();
                        })
                        .then(data => {
                            const newOrders  = data.orders || [];
                            const oldIds     = this.orders.map(o => o.id);
                            const newIds     = newOrders.map(o => o.id);

                            // Detect brand-new orders
                            const brandNew = newOrders.filter(o => !oldIds.includes(o.id));
                            if (brandNew.length > 0 && oldIds.length > 0) {
                                this.playChime();
                                this.showToast(`🔔 ${brandNew.length} طلب جديد وصل!`, 'amber');
                            }

                            // Detect newly-ready orders (status changed to ready)
                            const newlyReady = newOrders.filter(o => {
                                const old = this.orders.find(x => x.id === o.id);
                                return o.status === 'ready' && old && old.status !== 'ready';
                            });
                            if (newlyReady.length > 0) {
                                this.playChime();
                                this.showToast(`✅ ${newlyReady.length} طلب جاهز للتسليم!`, 'emerald');
                            }

                            // Detect completed/cancelled (removed from active)
                            const removedCount = oldIds.filter(id => !newIds.includes(id)).length;
                            if (removedCount > 0 && oldIds.length > 0) {
                                this.showToast(`🍽️ ${removedCount} طلب تم إغلاقه`, 'slate');
                            }

                            this.orders = newOrders;
                            this.pollErrors = 0; // reset error counter on success
                        })
                        .catch(err => {
                            this.pollErrors = (this.pollErrors || 0) + 1;
                            console.warn('Polling error #' + this.pollErrors, err);
                            // After 5 consecutive errors, slow down to 30s intervals
                            if (this.pollErrors === 5) {
                                this.showToast('⚠️ تعذّر الاتصال بالخادم. جاري المحاولة...', 'rose');
                            }
                        });
                },

                // Floating toast notifications
                toasts: [],
                showToast(message, color = 'amber') {
                    const id = Date.now();
                    const colorMap = {
                        amber:   'bg-amber-500 text-slate-950',
                        emerald: 'bg-emerald-600 text-white',
                        rose:    'bg-rose-600 text-white',
                        slate:   'bg-slate-700 text-white',
                    };
                    this.toasts.push({ id, message, cls: colorMap[color] || colorMap.amber });
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
                },

                updateStatus(orderId, newStatus) {
                    fetch(`/pos/orders/${orderId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Update local list
                            if (newStatus === 'completed') {
                                this.orders = this.orders.filter(o => o.id !== orderId);
                            } else {
                                const order = this.orders.find(o => o.id === orderId);
                                if (order) order.status = newStatus;
                            }
                        } else {
                            alert('حدث خطأ أثناء تحديث حالة الطلب.');
                        }
                    });
                },

                cancelOrder(orderId) {
                    if (confirm('هل أنت متأكد من إلغاء هذا الطلب نهائياً؟')) {
                        this.updateStatus(orderId, 'cancelled');
                    }
                },

                printReceiptDirectly(orderId) {
                    if (!this.printerIp) {
                        alert('يرجى إدخال عنوان IP الخاص بالطابعة أولاً!');
                        return;
                    }
                    fetch(`/pos/orders/${orderId}/print`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ ip: this.printerIp })
                    })
                    .then(r => r.json())
                    .then(d => alert(d.success ? 'تم إرسال أمر الطباعة!' : 'فشل الاتصال بالطابعة.'));
                }
            };
        }
    </script>
</body>
</html>
