<footer class="bg-brand-dark border-t border-brand-border mt-20">
    <div class="max-w-5xl mx-auto px-6 py-10">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaj3" class="w-8 h-8 rounded-lg object-cover">
                <span class="font-bungee text-white text-lg">Shaj3</span>
            </a>
            <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-400">
                <a href="{{ route('public.contact') }}" class="hover:text-brand-accent transition-colors">Contact Us</a>
                <a href="{{ route('public.privacy-policy') }}" class="hover:text-brand-accent transition-colors">Privacy Policy</a>
                <a href="{{ route('public.pages', 'terms-and-conditions') }}" class="hover:text-brand-accent transition-colors">Terms &amp; Conditions</a>
                <a href="{{ route('public.account-deletion') }}" class="hover:text-brand-accent transition-colors">Delete Account</a>
                <a href="{{ route('public.pages', 'faq') }}" class="hover:text-brand-accent transition-colors">FAQ</a>
            </nav>
        </div>
        <p class="text-center md:text-left text-sm text-slate-600 mt-6">&copy; {{ date('Y') }} Shaj3. All rights reserved.</p>
    </div>
</footer>
