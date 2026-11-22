@props(['dark' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <svg viewBox="0 0 32 32" class="h-7 w-7 shrink-0" aria-hidden="true">
        <rect width="32" height="32" rx="9" fill="#ff5a36"/>
        <rect x="7" y="9" width="18" height="12" rx="2.5" fill="none" stroke="#fff" stroke-width="2.4"/>
        <circle cx="16" cy="25" r="2" fill="#fff"/>
    </svg>
    <span class="text-[21px] font-extrabold tracking-tight {{ $dark ? 'text-white' : 'text-ink' }}">signo</span>
</span>
