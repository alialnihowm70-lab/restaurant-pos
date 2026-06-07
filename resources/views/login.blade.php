<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>alnihowm - بيلو سماش كيق | تسجيل الدخول</title>
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
            background-image: radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(249, 115, 22, 0.04) 0%, transparent 40%);
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
    <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-emerald-500/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-orange-500/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/5 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="max-w-md w-full space-y-8 relative z-10">
        
        <!-- Branding Logo & Header -->
        <div class="text-center space-y-4">
            <div class="w-18 h-18 rounded-[24px] bg-gradient-to-tr from-emerald-500 via-green-500 to-orange-500 flex items-center justify-center font-black text-white shadow-2xl shadow-emerald-500/20 text-3xl tracking-wider mx-auto animate-pulse">
                N
            </div>
            <div class="space-y-1">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">alnihowm</h1>
                <span class="text-xs text-emerald-700 font-extrabold tracking-widest block uppercase">بيلو سماش كيق | بوابة تسجيل دخول الموظفين</span>
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
                
                <!-- Login Identifier Input -->
                <div class="space-y-1.5 text-right">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">اسم الموظف أو البريد الإلكتروني</label>
                    <input type="text" name="login" required x-model="login" placeholder="مثال: أحمد علي أو cashier@pos.ly"
                           class="w-full bg-white/50 border border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl px-4 py-3.5 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right shadow-sm" />
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5 text-right">
                    <label class="text-[10px] font-bold text-slate-550 uppercase tracking-wider block">كلمة المرور</label>
                    <input type="password" name="password" required x-model="password" placeholder="••••••••"
                           class="w-full bg-white/50 border border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl px-4 py-3.5 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right font-mono shadow-sm" />
                </div>

                <!-- Remember Me & Reset Link -->
                <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 uppercase tracking-wider" dir="rtl">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="accent-emerald-500 rounded cursor-pointer" />
                        <span class="mr-1">تذكرني على هذا الجهاز</span>
                    </label>
                    <span class="text-emerald-700 hover:text-orange-600 cursor-pointer transition-colors">هل نسيت كلمة المرور؟</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 via-green-500 to-orange-500 hover:from-emerald-600 hover:via-green-600 hover:to-orange-600 text-white font-black py-4 rounded-2xl transition-all duration-300 shadow-xl shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-[1.01] active:scale-[0.99] text-xs tracking-wider uppercase mt-4">
                    دخول إلى النظام (Login)
                </button>
            </form>
        </div>

    </div>

    <script>
        function loginApp() {
            return {
                login: '',
                password: ''
            };
        }
    </script>

</body>
</html>
