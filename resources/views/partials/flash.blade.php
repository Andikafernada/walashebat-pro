@if (session('success'))
    <div class="animate-slide-up mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4" role="alert" aria-live="polite">
        <div class="shrink-0 mt-0.5">
            <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="animate-slide-up mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4" role="alert" aria-live="assertive">
        <div class="shrink-0 mt-0.5">
            <svg class="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-rose-800">{{ session('error') }}</p>
    </div>
@endif

@if (session('warning'))
    <div class="animate-slide-up mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4" role="alert" aria-live="polite">
        <div class="shrink-0 mt-0.5">
            <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-amber-800">{{ session('warning') }}</p>
    </div>
@endif

@if (session('info'))
    <div class="animate-slide-up mb-6 flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4" role="alert" aria-live="polite">
        <div class="shrink-0 mt-0.5">
            <svg class="h-5 w-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-sky-800">{{ session('info') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="animate-slide-up mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4" role="alert" aria-live="assertive">
        <div class="flex items-start gap-3">
            <div class="shrink-0 mt-0.5">
                <svg class="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-rose-800">Terdapat kesalahan:</p>
                <ul class="mt-1.5 space-y-1 text-sm text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li class="flex items-start gap-2">
                            <span class="mt-1 shrink-0 h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
