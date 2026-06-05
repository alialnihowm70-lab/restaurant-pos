<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>المدينة POS - تسجيل الدخول</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Compiled Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            background-image: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.03) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(241, 245, 249, 1) 0%, transparent 40%);
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
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden bg-slate-550 page-animate" x-data="loginApp()">

    <!-- Decorative Blurred Glows -->
    <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-amber-500/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-rose-500/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/5 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="max-w-md w-full space-y-8 relative z-10">
        
        <!-- Branding Logo & Header -->
        <div class="text-center space-y-4">
            <div class="w-18 h-18 rounded-[24px] bg-gradient-to-tr from-amber-500 via-orange-500 to-red-500 flex items-center justify-center font-black text-slate-950 shadow-2xl shadow-orange-500/20 text-3xl tracking-wider mx-auto animate-pulse">
                M
            </div>
            <div class="space-y-1">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">منظومة المدينة لإدارة المطاعم</h1>
                <span class="text-xs text-amber-600 font-extrabold tracking-widest block uppercase">بوابة تسجيل دخول الموظفين</span>
            </div>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white/80 backdrop-blur-xl border border-white/60 rounded-[32px] p-8 shadow-2xl shadow-slate-200/50 space-y-6 hover:shadow-slate-200/80 transition-all duration-500">
            
            @if($errors->any())
                <div class="bg-red-50/80 backdrop-blur-sm border border-red-100 text-red-700 p-4 rounded-2xl text-xs font-bold space-y-1">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="/login" method="POST" class="space-y-4" id="loginForm">
                @csrf
                
                <!-- Email Input -->
                <div class="space-y-1.5 text-right">
                    <label class="text-[10px] font-bold text-slate-550 uppercase tracking-wider block">البريد الإلكتروني</label>
                    <input type="email" name="email" required x-model="email" placeholder="مثال: cashier@pos.ly"
                           class="w-full bg-white/50 border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3.5 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5 text-right">
                    <label class="text-[10px] font-bold text-slate-550 uppercase tracking-wider block">كلمة المرور</label>
                    <input type="password" name="password" required x-model="password" placeholder="••••••••"
                           class="w-full bg-white/50 border border-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl px-4 py-3.5 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right font-mono shadow-sm" />
                </div>

                <!-- Remember Me & Reset Link -->
                <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 uppercase tracking-wider" dir="rtl">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="accent-amber-500 rounded cursor-pointer" />
                        <span class="mr-1">تذكرني على هذا الجهاز</span>
                    </label>
                    <span class="text-amber-600 hover:text-amber-700 cursor-pointer transition-colors">هل نسيت كلمة المرور؟</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black py-4 rounded-2xl transition-all duration-300 shadow-xl shadow-amber-500/20 hover:shadow-amber-500/30 hover:scale-[1.01] active:scale-[0.99] text-xs tracking-wider uppercase mt-4">
                    دخول إلى النظام (Login)
                </button>
            </form>
        </div>

        <!-- Quick Demo Accounts Selector (Phase 8 premium detail) -->
        <div class="space-y-4">
            <div class="text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
                الدخول التجريبي السريع • Quick Access Demo Accounts
            </div>
            
            <div class="grid grid-cols-3 gap-3">
                <!-- Admin Card -->
                <div @click="quickLogin('admin@pos.ly', 'admin123')"
                     class="bg-white/80 backdrop-blur-md hover:bg-amber-50/10 border border-slate-200 hover:border-amber-500/40 p-4 rounded-2xl cursor-pointer text-center space-y-1.5 transition-all duration-300 group shadow-sm hover:shadow-md hover:-translate-y-0.5">
                    <span class="text-2xl group-hover:scale-110 transition-transform block">📊</span>
                    <span class="text-[10px] font-extrabold text-slate-800 block">مدير النظام</span>
                    <span class="text-[8px] text-slate-400 font-bold block">Admin</span>
                </div>

                <!-- Cashier Card -->
                <div @click="quickLogin('cashier@pos.ly', 'cashier123')"
                     class="bg-white/80 backdrop-blur-md hover:bg-amber-50/10 border border-slate-200 hover:border-amber-500/40 p-4 rounded-2xl cursor-pointer text-center space-y-1.5 transition-all duration-300 group shadow-sm hover:shadow-md hover:-translate-y-0.5">
                    <span class="text-2xl group-hover:scale-110 transition-transform block">🛒</span>
                    <span class="text-[10px] font-extrabold text-slate-800 block">كاشير الصالة</span>
                    <span class="text-[8px] text-slate-400 font-bold block">Cashier</span>
                </div>

                <!-- Chef Card -->
                <div @click="quickLogin('chef@pos.ly', 'chef123')"
                     class="bg-white/80 backdrop-blur-md hover:bg-amber-50/10 border border-slate-200 hover:border-amber-500/40 p-4 rounded-2xl cursor-pointer text-center space-y-1.5 transition-all duration-300 group shadow-sm hover:shadow-md hover:-translate-y-0.5">
                    <span class="text-2xl group-hover:scale-110 transition-transform block">🍳</span>
                    <span class="text-[10px] font-extrabold text-slate-800 block">طاهي المطبخ</span>
                    <span class="text-[8px] text-slate-400 font-bold block">Chef</span>
                </div>
            </div>
        </div>

    </div>

    <script>
        function loginApp() {
            return {
                email: '',
                password: '',
                
                quickLogin(demoEmail, demoPassword) {
                    this.email = demoEmail;
                    this.password = demoPassword;
                    // Submit the form automatically after a slight delay for visual feedback
                    setTimeout(() => {
                        document.getElementById('loginForm').submit();
                    }, 200);
                }
            };
        }
    </script>

</body>
</html>
