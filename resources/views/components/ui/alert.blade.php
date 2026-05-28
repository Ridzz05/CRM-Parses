@props(['variant' => 'info'])

@php
    $variants = [
        'info' => 'border-[color-mix(in_srgb,var(--app-info)_40%,var(--app-border))] bg-[color-mix(in_srgb,var(--app-info)_12%,var(--app-surface))] text-[var(--app-text)]',
        'success' => 'border-[color-mix(in_srgb,var(--app-success)_45%,var(--app-border))] bg-[color-mix(in_srgb,var(--app-success)_14%,var(--app-surface))] text-[var(--app-text)]',
        'danger' => 'border-[color-mix(in_srgb,var(--app-danger)_45%,var(--app-border))] bg-[color-mix(in_srgb,var(--app-danger)_12%,var(--app-surface))] text-[var(--app-text)]',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-md border px-4 py-3 text-sm '.$variants[$variant]]) }}>
    {{ $slot }}
</div>
