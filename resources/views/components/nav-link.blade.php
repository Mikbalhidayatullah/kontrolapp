@props(['active' => false])
<a
    aria-current="{{ $active ? 'page' : false }}"
    class="{{ $active ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }} inline-flex items-center rounded-full px-4 py-2 text-sm font-medium transition"
    {{ $attributes }}
>{{ $slot }}</a>
