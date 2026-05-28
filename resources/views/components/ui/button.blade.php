@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex min-h-9 items-center justify-center rounded-md px-3.5 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--app-bg)] disabled:pointer-events-none disabled:opacity-55';
    $variants = [
        'primary' => 'bg-[var(--app-primary)] text-[var(--app-primary-text)] hover:bg-[var(--app-primary-hover)] focus:ring-[var(--app-primary)]',
        'secondary' => 'border border-[color-mix(in_srgb,var(--app-border)_76%,transparent)] bg-transparent text-[var(--app-text)] hover:bg-[var(--app-surface-strong)] focus:ring-[var(--app-primary)]',
        'ghost' => 'text-[var(--app-text)] hover:bg-[var(--app-surface-strong)] focus:ring-[var(--app-primary)]',
        'danger' => 'bg-[var(--app-danger)] text-[var(--app-primary-text)] hover:opacity-90 focus:ring-[var(--app-danger)]',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base.' '.$variants[$variant]]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base.' '.$variants[$variant]]) }}>{{ $slot }}</button>
@endif
