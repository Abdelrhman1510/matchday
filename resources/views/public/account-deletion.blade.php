<!DOCTYPE html>
@php $isAr = app()->getLocale() === 'ar'; @endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How to Delete Your Account - Shaj3</title>
    <meta name="description" content="Step-by-step guide to delete your Shaj3 account permanently.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Bungee&family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-dark':   '#07021e',
                        'brand-main':   '#0c0628',
                        'brand-card':   '#110830',
                        'brand-border': '#1e164e',
                        'brand-accent': '#c8ff00',
                    },
                    fontFamily: {
                        sans:   ['Instrument Sans', 'sans-serif'],
                        bungee: ['Bungee', 'cursive'],
                        cairo:  ['Cairo', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        body { background-color: #0c0628; font-family: 'Instrument Sans', sans-serif; }
        html[dir="rtl"] body { font-family: 'Cairo', 'Instrument Sans', sans-serif; }

        /* Phone frame wrapper */
        .phone-frame {
            position: relative;
            border-radius: 2.5rem;
            border: 2px solid #1e164e;
            overflow: hidden;
            box-shadow: 0 0 0 6px #07021e, 0 25px 60px rgba(0,0,0,0.6), 0 0 40px rgba(200, 255, 0, 0.06);
            background: #07021e;
            max-width: 220px;
            margin: 0 auto;
        }
        .phone-frame img {
            display: block;
            width: 100%;
            height: auto;
        }

        /* Step connector line */
        .step-connector {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, #1e164e, #c8ff00, #1e164e);
            top: 0;
            z-index: 0;
        }

        /* Warning box */
        .warning-box {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 1rem;
        }
    </style>
</head>
<body class="min-h-screen antialiased">

    <!-- Header -->
    <header class="bg-brand-dark border-b border-brand-border sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaj3" class="w-9 h-9 rounded-lg object-cover">
                    <span class="text-2xl font-bungee text-white tracking-wide">Shaj3</span>
                </a>
                @include('public.partials.lang-switch')
            </div>
        </div>
    </header>

    <!-- Hero -->
    <div class="bg-brand-dark border-b border-brand-border">
        <div class="max-w-5xl mx-auto px-6 py-12">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>{{ $isAr ? 'الحساب' : 'Account' }}</span>
                <span>&rsaquo;</span>
                <span class="text-slate-400">{{ $isAr ? 'حذف الحساب' : 'Delete Account' }}</span>
            </div>

            <h1 class="text-3xl md:text-4xl font-bold text-white">{{ $isAr ? 'كيفية حذف حسابك' : 'How to Delete Your Account' }}</h1>
            <div class="mt-2 flex items-center gap-2 mb-6">
                <span class="inline-block w-8 h-0.5 bg-brand-accent rounded"></span>
                <span class="text-sm text-slate-500">{{ $isAr ? 'تطبيق شجع · ٣ خطوات بسيطة' : 'Shaj3 App · 3 simple steps' }}</span>
            </div>
            <p class="text-slate-400 max-w-2xl leading-relaxed">
                {{ $isAr ? 'يمكنك حذف حساب شجع نهائياً من داخل التطبيق في أي وقت. اتبع الخطوات التالية لإزالة حسابك وجميع البيانات المرتبطة به.' : 'You can permanently delete your Shaj3 account directly from the app at any time. Follow the steps below to remove your account and all associated data.' }}
            </p>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-6 py-12 space-y-16">

        <!-- Warning Banner -->
        <div class="warning-box p-5 flex gap-4">
            <div class="shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-red-400 mb-1">{{ $isAr ? 'هذا الإجراء نهائي ولا يمكن التراجع عنه' : 'This action is permanent and cannot be undone' }}</p>
                <p class="text-sm text-slate-400 leading-relaxed">
                    {{ $isAr ? 'سيؤدي حذف حسابك إلى إزالة ملفك الشخصي وسجل الحجوزات ونقاط الولاء وجميع بياناتك الشخصية من شجع نهائياً. إذا كنت مالك مقهى ولديك حجوزات نشطة، فلا يمكن حذف حسابك حتى تكتمل جميع الحجوزات أو تُلغى.' : 'Deleting your account will permanently remove your profile, booking history, loyalty points, and all personal data from Shaj3. If you are a café owner with active bookings, your account cannot be deleted until all bookings are completed or cancelled.' }}
                </p>
            </div>
        </div>

        <!-- Steps -->
        <div class="space-y-20">

            <!-- Step 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <!-- Text -->
                <div class="order-2 md:order-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-accent text-black font-bold text-base shrink-0">
                            1
                        </div>
                        <h2 class="text-xl font-bold text-white">{{ $isAr ? 'افتح ملفك الشخصي' : 'Open your Profile' }}</h2>
                    </div>
                    <p class="text-slate-400 leading-relaxed mb-4">
                        {{ $isAr ? 'اضغط على أيقونة الملف الشخصي في أسفل شريط التنقل لفتح صفحة ملفك الشخصي، ثم مرّر للأسفل واضغط على «الخصوصية والشروط».' : 'Tap the Profile icon at the bottom of the navigation bar to open your profile page. Then scroll down and tap Privacy & Terms.' }}
                    </p>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            {{ $isAr ? 'اضغط على تبويب «الملف الشخصي»' : 'Tap the Profile tab (bottom right)' }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            {{ $isAr ? 'مرّر للأسفل للعثور على «الخصوصية والشروط»' : 'Scroll down to find Privacy & Terms' }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            {{ $isAr ? 'اضغط عليها لفتح صفحة المعلومات القانونية' : 'Tap it to open the legal information page' }}
                        </div>
                    </div>
                </div>
                <!-- Screenshot -->
                <div class="order-1 md:order-2">
                    <div class="phone-frame">
                        <img src="{{ asset('images/account-deletion/step-1.png') }}" alt="Step 1: Profile screen - tap Privacy &amp; Terms">
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-brand-border"></div>
                <div class="flex items-center gap-1.5 text-xs text-slate-600 uppercase tracking-widest">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    {{ $isAr ? 'الخطوة التالية' : 'next step' }}
                </div>
                <div class="flex-1 h-px bg-brand-border"></div>
            </div>

            <!-- Step 2 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <!-- Screenshot -->
                <div>
                    <div class="phone-frame">
                        <img src="{{ asset('images/account-deletion/step-2.png') }}" alt="Step 2: Privacy &amp; Terms - tap Delete Account">
                    </div>
                </div>
                <!-- Text -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-accent text-black font-bold text-base shrink-0">
                            2
                        </div>
                        <h2 class="text-xl font-bold text-white">{{ $isAr ? 'اضغط على «حذف الحساب»' : 'Tap Delete Account' }}</h2>
                    </div>
                    <p class="text-slate-400 leading-relaxed mb-4">
                        {{ $isAr ? 'في شاشة «الخصوصية والشروط»، مرّر إلى الأسفل واضغط على خيار «حذف الحساب» المميّز باللون الأحمر.' : 'On the Privacy & Terms screen, scroll to the bottom and tap the Delete Account option highlighted in red.' }}
                    </p>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            {{ $isAr ? 'سترى روابط لسياسة الخصوصية والشروط والأحكام واستخدام البيانات وسياسة ملفات تعريف الارتباط' : 'You will see links to Privacy Policy, Terms & Conditions, Data Usage, and Cookie Policy' }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            {{ $isAr ? 'في الأسفل، اضغط على زر «حذف الحساب» الأحمر' : 'At the bottom, tap the red Delete Account button' }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            {{ $isAr ? 'ستظهر نافذة تأكيد' : 'A confirmation dialog will appear' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-brand-border"></div>
                <div class="flex items-center gap-1.5 text-xs text-slate-600 uppercase tracking-widest">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    {{ $isAr ? 'الخطوة التالية' : 'next step' }}
                </div>
                <div class="flex-1 h-px bg-brand-border"></div>
            </div>

            <!-- Step 3 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <!-- Text -->
                <div class="order-2 md:order-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-red-500 text-white font-bold text-base shrink-0">
                            3
                        </div>
                        <h2 class="text-xl font-bold text-white">{{ $isAr ? 'أكّد الحذف' : 'Confirm Deletion' }}</h2>
                    </div>
                    <p class="text-slate-400 leading-relaxed mb-4">
                        {{ $isAr ? 'ستظهر نافذة تأكيد تحذّرك بأن هذا الإجراء نهائي ولا يمكن التراجع عنه. اضغط على «نعم، احذف» لحذف حسابك نهائياً.' : 'A confirmation dialog will appear warning you that this action is permanent and cannot be undone. Tap Yes, Delete to permanently delete your account.' }}
                    </p>
                    <div class="flex flex-col gap-2 mb-5">
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            {{ $isAr ? 'اقرأ التحذير بعناية — ستفقد جميع بياناتك' : 'Read the warning carefully — all your data will be lost' }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            {{ $isAr ? 'اضغط على «إلغاء» للعودة بأمان' : 'Tap Cancel to go back safely' }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            {{ $isAr ? 'اضغط على «نعم، احذف» للتأكيد النهائي' : 'Tap Yes, Delete to confirm permanently' }}
                        </div>
                    </div>

                    <!-- What gets deleted -->
                    <div class="bg-brand-card border border-brand-border rounded-xl p-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ $isAr ? 'ما الذي سيُحذف' : 'What gets deleted' }}</p>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                {{ $isAr ? 'ملفك الشخصي ومعلوماتك الشخصية' : 'Your profile and personal information' }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                {{ $isAr ? 'سجل الحجوزات' : 'Booking history' }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                {{ $isAr ? 'نقاط الولاء وحالة المستوى' : 'Loyalty points & tier status' }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                {{ $isAr ? 'الإنجازات والتفضيلات' : 'Achievements & preferences' }}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Screenshot -->
                <div class="order-1 md:order-2">
                    <div class="phone-frame">
                        <img src="{{ asset('images/account-deletion/step-3.png') }}" alt="Step 3: Confirm account deletion dialog">
                    </div>
                </div>
            </div>

        </div>

        <!-- Note for cafe owners -->
        <div class="bg-brand-card border border-brand-border rounded-2xl p-6">
            <div class="flex gap-4">
                <div class="shrink-0">
                    <div class="w-10 h-10 rounded-full bg-brand-accent/10 border border-brand-accent/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="font-semibold text-white mb-2">{{ $isAr ? 'ملاحظة لأصحاب المقاهي' : 'Note for Café Owners' }}</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        {{ $isAr ? 'إذا كنت مسجّلاً كمالك مقهى، فلا يمكن حذف حسابك أثناء وجود حجوزات نشطة (معلّقة أو مؤكّدة أو تم تسجيل الدخول لها). يجب أن تنتظر اكتمال جميع الحجوزات أو تلغيها أولاً قبل حذف حسابك.' : 'If you are registered as a café owner, your account cannot be deleted while you have active bookings (pending, confirmed, or checked-in). You must wait for all bookings to complete or cancel them first before deleting your account.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Need help? -->
        <div class="text-center py-4">
            <p class="text-slate-500 text-sm mb-2">{{ $isAr ? 'تحتاج مساعدة أو تواجه صعوبة في حذف حسابك؟' : 'Need help or having trouble deleting your account?' }}</p>
            <a href="mailto:support@shaj3.sa" class="inline-flex items-center gap-2 text-brand-accent hover:text-white transition-colors font-medium text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                {{ $isAr ? 'تواصل معنا على support@shaj3.sa' : 'Contact us at support@shaj3.sa' }}
            </a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-brand-dark border-t border-brand-border mt-8">
        <div class="max-w-5xl mx-auto px-6 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaj3" class="w-7 h-7 rounded object-cover">
                    <span class="font-bungee text-white">Shaj3</span>
                </div>
                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} <a href="https://amalakalan.sa/" target="_blank" rel="noopener" class="hover:text-white transition-colors">Amalakalan</a>. {{ __('site.footer.rights') }}</p>
                @include('public.partials.social')
                <div class="flex items-center gap-4 text-sm text-slate-500">
                    <a href="{{ route('public.privacy-policy') }}" class="hover:text-white transition-colors">{{ __('site.footer.privacy') }}</a>
                    <a href="{{ route('public.pages', 'terms-and-conditions') }}" class="hover:text-white transition-colors">{{ __('site.footer.terms') }}</a>
                    <a href="{{ route('public.pages', 'faq') }}" class="hover:text-white transition-colors">{{ __('site.footer.faq') }}</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
