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
    <title>المدينة KDS - شاشة عرض المطبخ</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@350;400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="manifest" href="/manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registered', reg))
                    .catch(err => console.log('SW error', err));
            });
        }
    </script>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }

        /* ── Scrollbar ── */
        .kds-lane::-webkit-scrollbar { width: 4px; }
        .kds-lane::-webkit-scrollbar-track { background: transparent; }
        .kds-lane::-webkit-scrollbar-thumb { background: rgba(148,163,184,.25); border-radius: 99px; }

        /* ── Lane headers ── */
        .lane-cooking  { border-color: rgba(251,191,36,.35); background: rgba(251,191,36,.04); }
        .lane-ready    { border-color: rgba(52,211,153,.35); background: rgba(52,211,153,.04); }
        .lane-done     { border-color: rgba(99,102,241,.30); background: rgba(99,102,241,.04); }

        .lane-hov-cooking  { --ring: rgba(251,191,36,.25); }
        .lane-hov-ready    { --ring: rgba(52,211,153,.25); }
        .lane-hov-done     { --ring: rgba(99,102,241,.25); }

        .drop-active { box-shadow: 0 0 0 3px var(--ring, rgba(255,255,255,.15)); }

        /* ── Card ── */
        .kds-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 20px;
            overflow: hidden;
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: grab;
        }
        .kds-card:active { cursor: grabbing; transform: scale(.98); }
        .kds-card:hover  { box-shadow: 0 8px 30px rgba(0,0,0,.35); }

        .card-strip { height: 4px; width: 100%; }
        .strip-green  { background: linear-gradient(90deg,#10b981,#34d399); }
        .strip-amber  { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
        .strip-red    { background: linear-gradient(90deg,#ef4444,#f87171); animation: pulse-red 1.2s infinite; }
        @keyframes pulse-red {
            0%,100% { opacity: 1; }
            50%      { opacity: .55; }
        }

        /* ── Item row ── */
        .item-row {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 12px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.06);
            cursor: pointer; transition: background .12s;
        }
        .item-row:hover { background: rgba(255,255,255,.08); }
        .item-row.checked { opacity: .5; }
        .item-row.checked .item-name { text-decoration: line-through; color: #64748b; }

        /* ── Checkbox ── */
        .cb { width:20px; height:20px; border-radius:7px; border:1.5px solid rgba(255,255,255,.2); flex-shrink:0; display:flex; align-items:center; justify-content:center; transition: all .12s; }
        .cb.active { background:#10b981; border-color:#10b981; color:#fff; font-weight:900; font-size:11px; }

        /* ── Empty state ── */
        .empty-state {
            flex: 1; display:flex; flex-direction:column; align-items:center; justify-content:center;
            opacity:.4; gap:10px;
        }
        .empty-state .emoji { font-size:36px; }
        .empty-state .label { font-size:11px; font-weight:800; color:#94a3b8; }

        /* ── Action buttons ── */
        .btn-cook {
            background: linear-gradient(135deg,#f59e0b,#d97706);
            color:#0f172a; font-weight:900; font-size:12px;
            padding: 10px 14px; border-radius:12px; width:100%;
            border:none; cursor:pointer; transition: filter .15s;
        }
        .btn-cook:hover { filter: brightness(1.1); }

        .btn-ready {
            background: linear-gradient(135deg,#10b981,#059669);
            color:#fff; font-weight:900; font-size:12px;
            padding: 10px 14px; border-radius:12px; width:100%;
            border:none; cursor:pointer; transition: filter .15s;
        }
        .btn-ready:hover { filter: brightness(1.1); }

        .btn-serve {
            background: linear-gradient(135deg,#6366f1,#4f46e5);
            color:#fff; font-weight:900; font-size:12px;
            padding: 10px 14px; border-radius:12px; width:100%;
            border:none; cursor:pointer; transition: filter .15s;
        }
        .btn-serve:hover { filter: brightness(1.1); }

        .btn-revert {
            background: rgba(255,255,255,.07);
            color:#94a3b8; font-weight:800; font-size:11px;
            padding: 8px 14px; border-radius:12px; width:100%;
            border:1px solid rgba(255,255,255,.08); cursor:pointer; transition: background .15s;
        }
        .btn-revert:hover { background: rgba(255,255,255,.12); color:#cbd5e1; }

        /* ── Timer ── */
        .timer-ok     { color:#34d399; }
        .timer-warn   { color:#fbbf24; }
        .timer-danger { color:#f87171; animation: blink 1s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* ── Badges ── */
        .badge-dinein    { background:rgba(99,102,241,.15); color:#a5b4fc; border:1px solid rgba(99,102,241,.25); }
        .badge-takeaway  { background:rgba(16,185,129,.12); color:#6ee7b7; border:1px solid rgba(16,185,129,.2); }
        .badge-delivery  { background:rgba(239,68,68,.12);  color:#fca5a5; border:1px solid rgba(239,68,68,.2); }
        .badge-pending   { background:rgba(251,191,36,.12); color:#fde68a; border:1px solid rgba(251,191,36,.2); }
        .badge-cooking   { background:rgba(249,115,22,.15); color:#fdba74; border:1px solid rgba(249,115,22,.25); }
        .badge-ready     { background:rgba(16,185,129,.15); color:#6ee7b7; border:1px solid rgba(16,185,129,.25); }
        .badge-done      { background:rgba(99,102,241,.15); color:#a5b4fc; border:1px solid rgba(99,102,241,.25); }

        /* ── Header ── */
        .kds-header {
            background: rgba(15,23,42,.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        /* ── Slide-in card ── */
        @keyframes cardIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        .card-in { animation: cardIn .25s cubic-bezier(.16,1,.3,1) both; }

        /* Lane label pill */
        .lane-pill-cooking { background:rgba(251,191,36,.12); color:#fbbf24; border:1px solid rgba(251,191,36,.2); }
        .lane-pill-ready   { background:rgba(52,211,153,.12);  color:#34d399; border:1px solid rgba(52,211,153,.2); }
        .lane-pill-done    { background:rgba(99,102,241,.12);  color:#a5b4fc; border:1px solid rgba(99,102,241,.2); }
    </style>
</head>
<body class="h-screen overflow-hidden flex" x-data="kdsApp()">

    <!-- Unified left navigation sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-grow flex flex-col overflow-hidden h-screen" x-data="{}">

        <!-- ──────────── Top Header ──────────── -->
        <header class="kds-header px-5 py-3 flex items-center justify-between gap-4 flex-shrink-0 z-20">

            <!-- Left: brand + mobile menu -->
            <div class="flex items-center gap-3">
                <button @click="$dispatch('toggle-sidebar')" class="lg:hidden w-8 h-8 flex items-center justify-center text-slate-400 hover:text-white rounded-lg transition-colors text-lg">☰</button>
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center text-lg shadow-lg shadow-orange-500/20 flex-shrink-0">🍳</div>
                <div>
                    <h1 class="text-sm font-black text-white leading-none">شاشة عرض المطبخ</h1>
                    <span class="text-[10px] text-amber-400/80 font-bold tracking-wide block mt-0.5">Kitchen Display System</span>
                </div>
            </div>

            <!-- Center: Live Stats -->
            <div class="hidden md:flex items-center gap-3">
                <!-- Cooking count -->
                <div class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 px-3.5 py-2 rounded-xl">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span class="text-[11px] text-amber-300 font-black">تحت التحضير: <span x-text="orders.filter(o=>o.status==='pending'||o.status==='cooking').length"></span></span>
                </div>
                <!-- Ready count -->
                <div class="flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 px-3.5 py-2 rounded-xl">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[11px] text-emerald-300 font-black">جاهزة: <span x-text="orders.filter(o=>o.status==='ready').length"></span></span>
                </div>
                <!-- Total active -->
                <div class="flex items-center gap-2 bg-white/5 border border-white/10 px-3.5 py-2 rounded-xl">
                    <span class="text-[11px] text-slate-300 font-black">إجمالي النشط: <span class="text-white" x-text="orders.length"></span></span>
                </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-2">
                <!-- Live clock -->
                <div class="hidden sm:flex items-center gap-2 bg-white/5 border border-white/8 px-3 py-2 rounded-xl">
                    <span class="text-[10px] text-slate-400 font-mono font-bold" x-text="new Date(currentTime).toLocaleTimeString('ar-SA', {hour:'2-digit',minute:'2-digit',second:'2-digit'})"></span>
                </div>
                <!-- Sound toggle -->
                <button @click="soundEnabled = !soundEnabled; localStorage.setItem('soundEnabled', soundEnabled)"
                        class="flex items-center gap-1.5 bg-white/5 hover:bg-white/10 border border-white/8 text-slate-300 hover:text-white font-bold text-[11px] px-3 py-2 rounded-xl transition-all">
                    <span x-text="soundEnabled ? '🔊' : '🔇'"></span>
                    <span class="hidden md:inline" x-text="soundEnabled ? 'الجرس: تفعيل' : 'الجرس: كتم'"></span>
                </button>
                <!-- Refresh -->
                <button @click="window.location.reload()"
                        class="hidden md:flex items-center gap-1.5 bg-white/5 hover:bg-white/10 border border-white/8 text-slate-300 hover:text-white font-bold text-[11px] px-3 py-2 rounded-xl transition-all">
                    🔄 تحديث
                </button>
                <!-- Go to POS -->
                <a href="/pos"
                   class="flex items-center gap-1.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black text-[11px] px-4 py-2 rounded-xl transition-all shadow-lg shadow-orange-500/20">
                    🧾 <span class="hidden sm:inline">كاشير</span>
                </a>
            </div>
        </header>

        <!-- ──────────── KDS Kanban Board ──────────── -->
        <main class="flex-grow p-4 lg:p-5 overflow-hidden flex gap-4 h-full min-h-0 dir-rtl"
              style="direction:rtl"
              x-data="{ draggedOrderId: null, dragOverColumn: null }">

            <!-- ═══ Lane 1: Cooking / Preparation ═══ -->
            <div class="flex-1 flex flex-col rounded-2xl border lane-cooking lane-hov-cooking transition-all duration-200 overflow-hidden"
                 @dragover.prevent="dragOverColumn = 1"
                 @dragleave="dragOverColumn = null"
                 @drop="handleDrop('cooking'); dragOverColumn = null"
                 :class="dragOverColumn === 1 ? 'drop-active' : ''">

                <!-- Lane Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-amber-500/15 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,.6)]"></div>
                        <span class="text-sm font-black text-amber-300">تحت التحضير</span>
                        <span class="text-[9px] text-amber-500/70 font-bold uppercase tracking-widest">Cooking</span>
                    </div>
                    <span class="lane-pill-cooking text-[10px] font-black px-2.5 py-1 rounded-full"
                          x-text="orders.filter(o=>o.status==='pending'||o.status==='cooking').length + ' طلب'"></span>
                </div>

                <!-- Lane Cards -->
                <div class="kds-lane flex-grow overflow-y-auto p-3 space-y-3 min-h-0">
                    <template x-for="order in orders.filter(o=>o.status==='pending'||o.status==='cooking')" :key="order.id">
                        <div x-data="{ checkedItems: [] }"
                             draggable="true"
                             @dragstart="draggedOrderId = order.id"
                             class="kds-card card-in">

                            <!-- Time strip -->
                            <div class="card-strip" :class="getStripClass(order)"></div>

                            <!-- Card Header -->
                            <div class="px-4 pt-3 pb-2 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-black text-white text-sm tracking-tight" x-text="order.invoice_number || '#' + order.id.substring(0,8).toUpperCase()"></span>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-lg"
                                              :class="order.status==='cooking' ? 'badge-cooking' : 'badge-pending'"
                                              x-text="order.status==='cooking' ? '🔥 جاري التحضير' : '⏳ انتظار'"></span>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-lg"
                                              :class="getOrderTypeBadgeClass(order.notes)">
                                            <span x-text="getOrderTypeIcon(order.notes)"></span>
                                            <span x-text="getOrderType(order.notes)"></span>
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-bold block mt-1" x-text="'📍 ' + order.location.name"></span>
                                </div>
                                <!-- Timer -->
                                <div class="text-left flex-shrink-0" dir="ltr">
                                    <div class="font-mono font-black text-sm"
                                         :class="getElapsedSeconds(order) > 600 ? 'timer-danger' : (getElapsedSeconds(order) > 300 ? 'timer-warn' : 'timer-ok')"
                                         x-text="getElapsedTime(order)"></div>
                                    <div class="text-[9px] text-slate-600 font-bold mt-0.5">⏱ منذ الطلب</div>
                                </div>
                            </div>

                            <!-- Items -->
                            <div class="px-3 pb-2 space-y-1.5">
                                <template x-for="item in order.items" :key="item.id">
                                    <div @click="checkedItems.includes(item.id) ? checkedItems=checkedItems.filter(i=>i!==item.id) : checkedItems.push(item.id)"
                                         class="item-row"
                                         :class="checkedItems.includes(item.id) ? 'checked' : ''">
                                        <div class="cb" :class="checkedItems.includes(item.id) ? 'active' : ''">
                                            <span x-show="checkedItems.includes(item.id)">✓</span>
                                        </div>
                                        <span class="text-amber-400 font-black text-xs" x-text="item.quantity + 'x'"></span>
                                        <span class="item-name text-slate-200 font-bold text-xs flex-grow" x-text="item.product.name"></span>
                                    </div>
                                </template>

                                <!-- Notes -->
                                <template x-if="cleanNotes(order.notes)">
                                    <div class="mt-2 p-2.5 rounded-xl bg-amber-500/8 border border-amber-500/15 text-right">
                                        <div class="text-[9px] text-amber-500 font-black uppercase tracking-wider mb-1">📝 ملاحظات</div>
                                        <div class="text-[11px] text-amber-200/80 font-bold" x-text="cleanNotes(order.notes)"></div>
                                    </div>
                                </template>
                            </div>

                            <!-- Action -->
                            <div class="px-3 pb-3">
                                <button x-show="order.status==='pending'" @click="updateStatus(order,'cooking')" class="btn-cook">
                                    🔥 بدء التحضير
                                </button>
                                <button x-show="order.status==='cooking'" @click="updateStatus(order,'ready')" class="btn-ready">
                                    ✅ جاهز للتسليم
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Empty -->
                    <template x-if="orders.filter(o=>o.status==='pending'||o.status==='cooking').length===0">
                        <div class="empty-state h-full">
                            <div class="emoji">🎉</div>
                            <div class="label">لا يوجد طلبات حالياً</div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ═══ Lane 2: Ready ═══ -->
            <div class="flex-1 flex flex-col rounded-2xl border lane-ready lane-hov-ready transition-all duration-200 overflow-hidden"
                 @dragover.prevent="dragOverColumn = 2"
                 @dragleave="dragOverColumn = null"
                 @drop="handleDrop('ready'); dragOverColumn = null"
                 :class="dragOverColumn === 2 ? 'drop-active' : ''">

                <!-- Lane Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-emerald-500/15 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,.6)] animate-pulse"></div>
                        <span class="text-sm font-black text-emerald-300">جاهز للتسليم</span>
                        <span class="text-[9px] text-emerald-500/70 font-bold uppercase tracking-widest">Ready</span>
                    </div>
                    <span class="lane-pill-ready text-[10px] font-black px-2.5 py-1 rounded-full"
                          x-text="orders.filter(o=>o.status==='ready').length + ' طلب'"></span>
                </div>

                <!-- Lane Cards -->
                <div class="kds-lane flex-grow overflow-y-auto p-3 space-y-3 min-h-0">
                    <template x-for="order in orders.filter(o=>o.status==='ready')" :key="order.id">
                        <div draggable="true"
                             @dragstart="draggedOrderId = order.id"
                             class="kds-card card-in" style="border-color:rgba(52,211,153,.15)">

                            <!-- Strip always green for ready -->
                            <div class="card-strip strip-green"></div>

                            <!-- Card Header -->
                            <div class="px-4 pt-3 pb-2 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-black text-white text-sm tracking-tight" x-text="order.invoice_number || '#' + order.id.substring(0,8).toUpperCase()"></span>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-lg badge-ready">✅ جاهز</span>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-lg"
                                              :class="getOrderTypeBadgeClass(order.notes)">
                                            <span x-text="getOrderTypeIcon(order.notes)"></span>
                                            <span x-text="getOrderType(order.notes)"></span>
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-bold block mt-1" x-text="'📍 ' + order.location.name"></span>
                                </div>
                                <div class="text-left flex-shrink-0" dir="ltr">
                                    <div class="font-mono font-black text-sm timer-ok" x-text="getElapsedTime(order)"></div>
                                    <div class="text-[9px] text-slate-600 font-bold mt-0.5">⏱ منذ الطلب</div>
                                </div>
                            </div>

                            <!-- Items (display only, no checkbox) -->
                            <div class="px-3 pb-2 space-y-1.5">
                                <template x-for="item in order.items" :key="item.id">
                                    <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-emerald-500/5 border border-emerald-500/10">
                                        <span class="text-emerald-400 font-black text-xs">✓</span>
                                        <span class="text-amber-400 font-black text-xs" x-text="item.quantity + 'x'"></span>
                                        <span class="text-slate-200 font-bold text-xs" x-text="item.product.name"></span>
                                    </div>
                                </template>

                                <template x-if="cleanNotes(order.notes)">
                                    <div class="mt-2 p-2.5 rounded-xl bg-amber-500/8 border border-amber-500/15 text-right">
                                        <div class="text-[9px] text-amber-500 font-black uppercase tracking-wider mb-1">📝 ملاحظات</div>
                                        <div class="text-[11px] text-amber-200/80 font-bold" x-text="cleanNotes(order.notes)"></div>
                                    </div>
                                </template>
                            </div>

                            <div class="px-3 pb-3">
                                <button @click="updateStatus(order,'completed')" class="btn-serve">
                                    🍽️ تسليم وإتمام الطلب
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="orders.filter(o=>o.status==='ready').length===0">
                        <div class="empty-state h-full">
                            <div class="emoji">🕒</div>
                            <div class="label">لا يوجد وجبات جاهزة</div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ═══ Lane 3: Served / Done ═══ -->
            <div class="flex-1 flex flex-col rounded-2xl border lane-done lane-hov-done transition-all duration-200 overflow-hidden"
                 @dragover.prevent="dragOverColumn = 3"
                 @dragleave="dragOverColumn = null"
                 @drop="handleDrop('completed'); dragOverColumn = null"
                 :class="dragOverColumn === 3 ? 'drop-active' : ''">

                <!-- Lane Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-indigo-500/15 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-indigo-400 shadow-[0_0_8px_rgba(99,102,241,.5)]"></div>
                        <span class="text-sm font-black text-indigo-300">تم التسليم</span>
                        <span class="text-[9px] text-indigo-500/70 font-bold uppercase tracking-widest">Served</span>
                    </div>
                    <span class="lane-pill-done text-[10px] font-black px-2.5 py-1 rounded-full"
                          x-text="completedOrders.length + ' طلب'"></span>
                </div>

                <!-- Lane Cards -->
                <div class="kds-lane flex-grow overflow-y-auto p-3 space-y-3 min-h-0">
                    <template x-for="order in completedOrders" :key="order.id">
                        <div draggable="true"
                             @dragstart="draggedOrderId = order.id"
                             class="kds-card card-in opacity-60 hover:opacity-90 transition-opacity">

                            <div class="card-strip" style="background:linear-gradient(90deg,#6366f1,#818cf8)"></div>

                            <div class="px-4 pt-3 pb-2 flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-black text-slate-400 text-sm line-through" x-text="order.invoice_number || '#' + order.id.substring(0,8).toUpperCase()"></span>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-lg badge-done">✅ تم التسليم</span>
                                    </div>
                                    <span class="text-[10px] text-slate-600 font-bold block mt-1"
                                          x-text="'🕐 ' + new Date(order.completed_at).toLocaleTimeString('ar-SA', {hour:'2-digit',minute:'2-digit'})"></span>
                                </div>
                            </div>

                            <div class="px-3 pb-2 space-y-1">
                                <template x-for="item in order.items" :key="item.id">
                                    <div class="flex items-center gap-2 text-[11px] text-slate-600">
                                        <span class="font-bold" x-text="item.quantity + 'x'"></span>
                                        <span class="line-through font-bold" x-text="item.product.name"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="px-3 pb-3">
                                <button @click="updateStatus(order,'ready')" class="btn-revert">↺ إرجاع لقائمة الجاهز</button>
                            </div>
                        </div>
                    </template>

                    <template x-if="completedOrders.length===0">
                        <div class="empty-state h-full">
                            <div class="emoji">🍽️</div>
                            <div class="label">لم يتم تسليم أي وجبة بعد</div>
                        </div>
                    </template>
                </div>
            </div>

        </main>
    </div>

    <!-- ──────────── KDS Alpine Script ──────────── -->
    <script>
        function kdsApp() {
            return {
                orders: @json($orders),
                completedOrders: [],
                currentTime: Date.now(),
                soundEnabled: localStorage.getItem('soundEnabled') !== 'false',
                kdsErrors: 0,

                init() {
                    setInterval(() => { this.currentTime = Date.now(); }, 1000);
                    // Adaptive polling: 4s normally, slows to 30s after 5 errors
                    const poll = () => {
                        this.pollNewOrders();
                        const delay = this.kdsErrors >= 5 ? 30000 : 4000;
                        setTimeout(poll, delay);
                    };
                    setTimeout(poll, 2000); // start after 2s
                },

                playDoubleChime() {
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
                            gain.gain.setValueAtTime(0.15, t);
                            gain.gain.exponentialRampToValueAtTime(0.001, t + dur);
                            osc.start(t); osc.stop(t + dur);
                        };
                        const now = ctx.currentTime;
                        play(1318.51, now, 0.4);
                        play(1567.98, now + 0.15, 0.5);
                    } catch(e) { console.error('Chime failed', e); }
                },

                pollNewOrders() {
                    fetch('/api/orders/active.json')
                        .then(r => {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            return r.json();
                        })
                        .then(data => {
                            const newOrders = data.orders || [];
                            const oldIds = this.orders.map(o => o.id);

                            // New orders just arrived
                            const brandNew = newOrders.filter(o => !oldIds.includes(o.id));
                            if (brandNew.length > 0 && oldIds.length > 0) {
                                this.playDoubleChime();
                            }

                            this.orders = newOrders;
                            this.kdsErrors = 0;
                        })
                        .catch(err => {
                            this.kdsErrors = (this.kdsErrors || 0) + 1;
                            console.warn('KDS poll error #' + this.kdsErrors, err);
                        });
                },

                handleDrop(targetStatus) {
                    if (!this.draggedOrderId) return;
                    let order = this.orders.find(o => o.id === this.draggedOrderId)
                              || this.completedOrders.find(o => o.id === this.draggedOrderId);
                    if (order && order.status !== targetStatus) {
                        this.updateStatus(order, targetStatus);
                    }
                    this.draggedOrderId = null;
                },

                getElapsedSeconds(order) {
                    return Math.max(0, Math.floor((this.currentTime - new Date(order.created_at).getTime()) / 1000));
                },

                getElapsedTime(order) {
                    const s = this.getElapsedSeconds(order);
                    return `${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;
                },

                getStripClass(order) {
                    const s = this.getElapsedSeconds(order);
                    if (s < 300) return 'strip-green';
                    if (s < 600) return 'strip-amber';
                    return 'strip-red';
                },

                updateStatus(order, newStatus) {
                    fetch(`/kds/orders/${order.id}/status`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ status: newStatus })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) return;
                        if (newStatus === 'completed') {
                            order.status = 'completed';
                            order.completed_at = new Date().toISOString();
                            this.orders = this.orders.filter(o => o.id !== order.id);
                            this.completedOrders = [order, ...this.completedOrders.slice(0, 19)];
                        } else if (newStatus === 'ready' && order.status === 'completed') {
                            order.status = 'ready';
                            this.completedOrders = this.completedOrders.filter(o => o.id !== order.id);
                            this.orders = [...this.orders, order];
                        } else {
                            order.status = newStatus;
                        }
                    });
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
                    if (t === 'في المطعم') return 'badge-dinein';
                    if (t === 'توصيل') return 'badge-delivery';
                    return 'badge-takeaway';
                },

                getOrderTypeIcon(notes) {
                    const t = this.getOrderType(notes);
                    if (t === 'في المطعم') return '🛋️ ';
                    if (t === 'توصيل') return '🚗 ';
                    return '🛍️ ';
                }
            };
        }
    </script>
</body>
</html>
