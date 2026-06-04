<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدينة POS - تسجيل الدخول</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6" x-data="loginApp()">

    <div class="max-w-md w-full space-y-8">
        
        <!-- Branding Logo & Header -->
        <div class="text-center space-y-3">
            <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-amber-500 via-orange-500 to-red-500 flex items-center justify-center font-black text-slate-950 shadow-xl shadow-amber-500/20 text-3xl tracking-wider mx-auto animate-bounce">
                M
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">منظومة المدينة لإدارة المطاعم</h1>
                <span class="text-xs text-amber-600 font-bold tracking-widest block mt-1">بوابة تسجيل دخول الموظفين</span>
            </div>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-8 shadow-xl space-y-6">
            
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="/login" method="POST" class="space-y-4" id="loginForm">
                @csrf
                
                <!-- Email Input -->
                <div class="space-y-1.5 text-right">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">البريد الإلكتروني</label>
                    <input type="email" name="email" required x-model="email" placeholder="مثال: cashier@pos.ly"
                           class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3.5 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right" />
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5 text-right">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">كلمة المرور</label>
                    <input type="password" name="password" required x-model="password" placeholder="••••••••"
                           class="w-full bg-slate-50 border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 rounded-xl px-4 py-3.5 text-sm text-slate-800 focus:outline-none transition-all duration-300 text-right font-mono" />
                </div>

                <!-- Remember Me & Reset Link -->
                <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 uppercase tracking-wider" dir="rtl">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="accent-amber-500 rounded" />
                        <span class="mr-1">تذكرني على هذا الجهاز</span>
                    </label>
                    <span class="text-amber-600 hover:text-amber-700 cursor-pointer">هل نسيت كلمة المرور؟</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black py-4 rounded-xl transition-all shadow-lg shadow-amber-500/10 text-xs tracking-wider uppercase mt-4">
                    دخول إلى النظام (Login)
                </button>
            </form>
        </div>

        <!-- Quick Demo Accounts Selector (Phase 8 premium detail) -->
        <div class="space-y-4">
            <div class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                الدخول التجريبي السريع • Quick Access Demo Accounts
            </div>
            
            <div class="grid grid-cols-3 gap-3">
                <!-- Admin Card -->
                <div @click="quickLogin('admin@pos.ly', 'admin123')"
                     class="bg-white hover:bg-amber-50/10 border border-slate-200 hover:border-amber-500/50 p-3 rounded-2xl cursor-pointer text-center space-y-1 transition-all duration-300 group shadow-sm">
                    <span class="text-2xl group-hover:scale-110 transition-transform block">📊</span>
                    <span class="text-[10px] font-bold text-slate-800 block">مدير النظام</span>
                    <span class="text-[8px] text-slate-400 block">Admin</span>
                </div>

                <!-- Cashier Card -->
                <div @click="quickLogin('cashier@pos.ly', 'cashier123')"
                     class="bg-white hover:bg-amber-50/10 border border-slate-200 hover:border-amber-500/50 p-3 rounded-2xl cursor-pointer text-center space-y-1 transition-all duration-300 group shadow-sm">
                    <span class="text-2xl group-hover:scale-110 transition-transform block">🛒</span>
                    <span class="text-[10px] font-bold text-slate-800 block">كاشير الصالة</span>
                    <span class="text-[8px] text-slate-400 block">Cashier</span>
                </div>

                <!-- Chef Card -->
                <div @click="quickLogin('chef@pos.ly', 'chef123')"
                     class="bg-white hover:bg-amber-50/10 border border-slate-200 hover:border-amber-500/50 p-3 rounded-2xl cursor-pointer text-center space-y-1 transition-all duration-300 group shadow-sm">
                    <span class="text-2xl group-hover:scale-110 transition-transform block">🍳</span>
                    <span class="text-[10px] font-bold text-slate-800 block">طاهي المطبخ</span>
                    <span class="text-[8px] text-slate-400 block">Chef</span>
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
