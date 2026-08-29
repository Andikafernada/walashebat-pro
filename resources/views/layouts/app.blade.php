@php
    // 1. Cek parameter query pemicu manual (?layout=web atau ?layout=pwa)
    if (request()->has('layout')) {
        $layoutMode = request()->query('layout') === 'pwa' ? 'pwa' : 'web';
        session(['user_layout_mode' => $layoutMode]);
    } else {
        $layoutMode = session('user_layout_mode', null);
        
        // 2. Jika belum ada preferensi tersimpan, deteksi otomatis dari User-Agent
        if (!$layoutMode) {
            $agent = request()->header('User-Agent', '');
            $isMobileAgent = (bool) preg_match('/(android|bb\d+|meego).+mobile|iphone|ipad|ipod|blackberry|opera mini|iemobile|mobile/i', $agent);
            $layoutMode = $isMobileAgent ? 'pwa' : 'web';
        }
    }
@endphp

@if ($layoutMode === 'pwa')
    @include('layouts.pwa')
@else
    @include('layouts.web')
@endif
